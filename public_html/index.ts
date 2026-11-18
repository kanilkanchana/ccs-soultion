/**
 * CCS Solutions — verified plate / password authentication
 * Supabase Edge Function (Deno) deployed with JWT verify OFF for password logins.
 * Check any supplied user JWT itself for the authenticated plate-link action.
 * Secrets: SUPABASE_URL, SUPABASE_ANON_KEY, SUPABASE_SERVICE_ROLE_KEY,
 * CCS_SITE_ORIGINS (comma-separated HTTPS origins allowed by CORS).
 */
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2';
const project = Deno.env.get('SUPABASE_URL') || '';
const anon = Deno.env.get('SUPABASE_ANON_KEY') || '';
const service = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') || '';
const allowed = (Deno.env.get('CCS_SITE_ORIGINS') || '').split(',').map(x => x.trim()).filter(Boolean);
const admin = createClient(project, service, { auth: { autoRefreshToken: false, persistSession: false } });
const authClient = createClient(project, anon, { auth: { autoRefreshToken: false, persistSession: false } });
const normalizePlate = (plate: unknown) => String(plate || '').trim().toUpperCase().replace(/[\s-]/g, '');
function respond(req: Request, result: unknown, status = 200) {
  const origin = req.headers.get('origin') || '';
  return new Response(JSON.stringify(result), { status, headers: {
    'Content-Type':'application/json', 'Cache-Control':'no-store', 'Vary':'Origin',
    ...(allowed.includes(origin) ? {'Access-Control-Allow-Origin':origin} : {}),
    'Access-Control-Allow-Headers':'authorization, content-type, apikey',
    'Access-Control-Allow-Methods':'POST, OPTIONS'
  }});
}
function invalid(req: Request, status = 401) { return respond(req, {message:'Sign-in failed. Please check the details or contact CCS Solutions.'}, status); }
async function hash(value: string) {
  const bytes = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(value));
  return [...new Uint8Array(bytes)].map(x => x.toString(16).padStart(2,'0')).join('');
}
Deno.serve(async req => {
  if (req.method === 'OPTIONS') return respond(req, {}, 204);
  if (req.method !== 'POST') return respond(req, {message:'Unsupported method'}, 405);
  const origin = req.headers.get('origin');
  if (origin && !allowed.includes(origin)) return invalid(req, 403);
  if (!project || !anon || !service) return respond(req,{message:'Authentication is not configured on the server.'},503);
  try {
    if (Number(req.headers.get('content-length') || '0') > 8192) return invalid(req,413);
    const body = await req.json();
    if (body.action === 'link') {
      // Submission is NOT approval: a trusted CCS staff member must verify registration/ownership.
      const jwt = (req.headers.get('authorization') || '').replace(/^Bearer\s+/i, '');
      if (!jwt || jwt === anon) return invalid(req);
      const { data: { user }, error } = await admin.auth.getUser(jwt);
      if (error || !user?.id) return invalid(req);
      const plate = normalizePlate(body.plate);
      if (!/^[A-Z0-9]{3,12}$/.test(plate)) return respond(req,{message:'Enter a valid vehicle registration number.'},400);
      const {data:existing, error: readError} = await admin.from('ccs_customer_plates').select('user_id,verified_at').eq('plate_normalized',plate).maybeSingle();
      if (readError) return respond(req,{message:'Unable to submit registration.'},500);
      if (existing && existing.user_id !== user.id) return respond(req,{message:'Please contact CCS to verify this registration.'},409);
      if (!existing) {
        const {error: insertError} = await admin.from('ccs_customer_plates').insert({plate_normalized:plate,user_id:user.id});
        if (insertError) return respond(req,{message:'Unable to submit registration.'},500);
      }
      return respond(req,{message:existing?.verified_at ? 'Vehicle number is already verified.' : 'Submitted. CCS staff must verify ownership before vehicle-number sign-in is enabled.'});
    }
    if (body.action !== 'login') return invalid(req, 400);
    const plate = normalizePlate(body.plate);
    const password = String(body.password || '');
    if (!/^[A-Z0-9]{3,12}$/.test(plate) || password.length < 8 || password.length > 100) return invalid(req);
    const ip = (req.headers.get('cf-connecting-ip') || req.headers.get('x-forwarded-for') || 'unknown').split(',')[0].trim();
    const {data:permitted, error:limitError} = await admin.rpc('ccs_plate_rate_limit', {p_hash:await hash(ip+'|'+plate)});
    if (limitError || !permitted) return invalid(req,429);
    const { data:vehicle, error:plateError } = await admin.from('ccs_customer_plates').select('user_id').eq('plate_normalized',plate).not('verified_at','is',null).maybeSingle();
    if (plateError || !vehicle) return invalid(req);
    const {data:account, error:userError} = await admin.auth.admin.getUserById(vehicle.user_id);
    if (userError || !account?.user?.email) return invalid(req);
    const {data:signedIn,error:authError} = await authClient.auth.signInWithPassword({ email:account.user.email,password });
    if (authError || !signedIn.session) return invalid(req);
    const s = signedIn.session;
    return respond(req,{ access_token:s.access_token,refresh_token:s.refresh_token,expires_in:s.expires_in,token_type:'bearer' });
  } catch {
    return invalid(req,400);
  }
});
