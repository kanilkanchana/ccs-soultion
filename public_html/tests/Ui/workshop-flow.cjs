// Executes the real POS picker and quick-job vehicle picker against a small DOM mock.
// This checks identity/mileage handoff and async selection behavior, not browser rendering.
const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');
class Node{
 constructor(tag='div'){this.tag=tag;this.children=[];this.listeners={};this.dataset={};this.attributes={};this.value='';this.textContent='';this.hidden=true;this.open=false;this.disabled=false;this.style={};}
 get options(){return this.children;}get selectedOptions(){return this.children.filter(n=>String(n.value)===String(this.value));}
 append(...nodes){this.children.push(...nodes);}replaceChildren(...nodes){this.children=[...nodes];}addEventListener(k,fn){(this.listeners[k]??=[]).push(fn);}dispatchEvent(e){for(const fn of this.listeners[e.type]??[])fn(e);}setAttribute(k,v){this.attributes[k]=v;}focus(){}showModal(){this.open=true;}close(){this.open=false;}querySelector(){return null;}click(){this.dispatchEvent({type:'click',target:this});}
}
const flush=()=>new Promise(r=>setImmediate(r));
function dom(){const nodes={};const get=id=>nodes[id]??=new Node();return{nodes,get,document:{getElementById:get,createElement:t=>new Node(t),addEventListener(){},querySelector(){return new Node('form');}}};}
function script(file,start,end){const s=fs.readFileSync(path.resolve(__dirname,'../../resources/views/ccs/'+file),'utf8');const a=s.indexOf(start);assert(a>=0);const b=s.indexOf(end,a);assert(b>a);return s.slice(a,b).replace(/\{\{[\s\S]*?\}\}/g,'"/search"');}
(async()=>{
 const d=dom(),{get}=d;const pick=get('jobPick'),option=new Node('option');option.value='1';option.textContent='J1 · OLD-001 · Original owner';pick.append(option);pick.value='1';
 let allow=false,prompts=0,changes=0,results=[];get('postLines').querySelector=()=>({});pick.addEventListener('change',()=>changes++);
 const context={document:d.document,window:{ccsText:x=>x},confirm:()=>{prompts++;return allow;},URLSearchParams,Event:class{constructor(type){this.type=type;}},setTimeout:()=>1,clearTimeout(){},fetch:async()=>({ok:true,json:async()=>({data:results})})};
 vm.runInNewContext(script('pos.blade.php',"(()=>{\n const $=id=>document.getElementById(id),jobDialog=",' function syncManual()')+'})();',context);
 assert.equal(get('selectedJobText').textContent,option.textContent);
 get('openJobPicker').value='New owner';get('openJobPicker').dispatchEvent({type:'input'});assert.equal(pick.value,'');assert.equal(get('restoreJobSelection').hidden,false);assert.match(get('selectedJobText').textContent,/Select a search result/);
 get('restoreJobSelection').click();assert.equal(pick.value,'1');assert.equal(get('openJobPicker').value,option.textContent);
 results=[{id:2,job_number:'J2',registration_number:'NEW-002',customer_name:'New owner',customer_code:'C2',customer_id:2,make:'Toyota',model:'Prius'}];
 const enter=()=>get('openJobPicker').dispatchEvent({type:'keydown',key:'Enter',preventDefault(){}});
 get('openJobPicker').value='NEW-002';get('openJobPicker').dispatchEvent({type:'input'});enter();await flush();assert.equal(prompts,1);assert.equal(pick.value,'1','Declining cart transfer must restore confirmed identity');
 allow=true;get('openJobPicker').value='NEW-002';get('openJobPicker').dispatchEvent({type:'input'});enter();await flush();assert.equal(pick.value,'2');assert.equal(get('restoreJobSelection').hidden,true);assert(changes>=4);assert.match(get('selectedJobText').textContent,/NEW-002/);
 // Same-name customers remain a deliberate choice, never an automatic first match.
 results=[{...results[0],customer_name:'Same name'},{...results[0],id:3,customer_id:3,customer_name:'Same name',customer_code:'C3',registration_number:'OTHER-003'}];
 get('openJobPicker').value='Same name';get('openJobPicker').dispatchEvent({type:'input'});enter();await flush();assert.equal(pick.value,'');assert.equal(get('jobPickerDialog').open,true);assert.equal(get('jobPickerResults').children.length,2);
 get('jobPickerResults').children[1].click();assert.equal(pick.value,'3');assert.match(get('selectedJobText').textContent,/OTHER-003/);
 // An older async result must not undo an explicit Keep previous job action.
 let resolve;context.fetch=()=>new Promise(r=>resolve=r);get('openJobPicker').value='Searching';get('openJobPicker').dispatchEvent({type:'input'});enter();get('restoreJobSelection').click();resolve({ok:true,json:async()=>({data:results})});await flush();assert.equal(pick.value,'3');assert.equal(get('inlineJobDropdown').hidden,true);
 const j=dom(),v=new Node('option');v.value='7';v.textContent='TEST-007 · Saved owner';v.dataset={plate:'TEST-007',customerCode:'C7',phone:'0770000007',mileage:'100000'};j.get('rj_vehicle').append(v);j.get('rj_vehicle').value='7';
 const form=new Node('form');j.document.querySelector=()=>form;
 let vehicle={id:8,registration_number:'TEST-008',customer_name:'Other owner',customer_code:'C8',phone:'0770000008',make:'Honda',model:'Fit',current_mileage_km:0};
 const c={document:j.document,window:{ccsText:x=>x},URLSearchParams,fetch:async()=>({ok:true,json:async()=>({data:[vehicle],auto_select:true})})};
 vm.runInNewContext(script('jobs.blade.php',"(()=>{\n const $=id=>document.getElementById(id),query=$('rj_vehicle_query')",'</script>'),c);
 assert.equal(j.get('rj_vehicle_query').value,'TEST-007');assert.match(j.get('rj_vehicle_selected').textContent,/C7/);assert.match(j.get('rj_mileage_reference').textContent,/100000/);assert.equal(j.get('rj_mileage').value,'','Old mileage is a reference, not a new service measurement');
 j.get('rj_mileage').value='123456';j.get('rj_vehicle_query').value='TEST-008';j.get('rj_vehicle_query').dispatchEvent({type:'input'});assert.equal(j.get('rj_mileage').value,'');j.get('rj_vehicle_search').click();await flush();assert.equal(j.get('rj_vehicle').value,'8');assert.match(j.get('rj_vehicle_selected').textContent,/C8/);assert.match(j.get('rj_mileage_reference').textContent,/: 0 km/);assert.equal(j.get('rj_mileage').value,'');
 console.log('PASS: actual POS pending-search identity blocking, previous-job restore, confirmed cart transfer, duplicate-name choice, stale async response, quick-job identity and mileage isolation.');
})().catch(e=>{console.error(e);process.exitCode=1;});
