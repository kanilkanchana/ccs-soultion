<script>
(function(){
'use strict';
const switcher=document.getElementById('ccsInputSwitch'),picker=document.getElementById('ccsInputMode');
if(!switcher||!picker)return;
let mode=picker.value, composition=null;
const vowels={a:['අ',''],aa:['ආ','ා'],A:['ඇ','ැ'],Aa:['ඈ','ෑ'],AA:['ඈ','ෑ'],i:['ඉ','ි'],ii:['ඊ','ී'],u:['උ','ු'],uu:['ඌ','ූ'],R:['ඍ','ෘ'],Ru:['ඎ','ෲ'],Lu:['ඏ','ෟ'],e:['එ','ෙ'],ee:['ඒ','ේ'],ai:['ඓ','ෛ'],o:['ඔ','ො'],oo:['ඕ','ෝ'],au:['ඖ','ෞ'],ou:['ඖ','ෞ']};
const consonants={k:'ක',kh:'ඛ',g:'ග',gh:'ඝ',zg:'ඟ',chh:'ඡ',ch:'ච',j:'ජ',J:'ඣ',zj:'ඦ',zk:'ඥ',zh:'ඤ',T:'ඨ',t:'ට',D:'ඪ',d:'ඩ',zd:'ඬ',thh:'ථ',th:'ත',dhh:'ධ',dh:'ද',zdh:'ඳ',n:'න',N:'ණ',p:'ප',ph:'ඵ',b:'බ',bh:'භ',B:'ඹ',m:'ම',y:'ය',r:'ර',l:'ල',L:'ළ',w:'ව',v:'ව',sh:'ශ',Sh:'ෂ',S:'ෂ',s:'ස',h:'හ',f:'ෆ'};
const vowelKeys=Object.keys(vowels).sort((a,b)=>b.length-a.length),consKeys=Object.keys(consonants).sort((a,b)=>b.length-a.length);
const find=(keys,text,i)=>keys.find(key=>text.startsWith(key,i));
function transliterate(text){
 let out='',i=0;
 while(i<text.length){
  if(text.startsWith('Lu',i)){out+='ඏ';i+=2;continue;}
  const con=find(consKeys,text,i);
  if(con){
   let letter=consonants[con];i+=con.length;
   if(text.startsWith('ruu',i)){out+=letter+'ෲ';i+=3;continue;}
   if(text.startsWith('ru',i)){out+=letter+'ෘ';i+=2;continue;}
   if(i<text.length&&(text[i]==='y'||text[i]==='r'||text[i]==='R')){
    const join=text[i],next=find(vowelKeys,text,i+1);
    if(next){i++;if(join==='R')letter='ර්‍'+letter;else letter+='්‍'+(join==='y'?'ය':'ර');}
   }
   const vowel=find(vowelKeys,text,i);
   if(vowel){out+=letter+vowels[vowel][1];i+=vowel.length;}else out+=letter+'්';
   continue;
  }
  const vowel=find(vowelKeys,text,i);
  if(vowel){out+=vowels[vowel][0];i+=vowel.length;continue;}
  if(text.startsWith('zn',i)){out+='ං';i+=2;continue;}
  if(text[i]==='x'){out+='ං';i++;continue;}if(text[i]==='H'||text[i]==='X'){out+='ඃ';i++;continue;}
  out+=text[i++];
 }
 return out.normalize('NFC');
}
const plain={q:'ු',w:'අ',e:'ැ',r:'ර',t:'එ',y:'හ',u:'ම',i:'ස',o:'ද',p:'ච','[':'ඤ',']':';',a:'්',s:'ි',d:'ා',f:'ෙ',g:'ට',h:'ය',j:'ව',k:'න',l:'ක',';':'ත',"'":'.',z:"'",x:'ං',c:'ජ',v:'ඩ',b:'ඉ',n:'බ',m:'ප',',':'ල','.':'ග','/':'/'};
const shifted={Q:'ූ',W:'උ',E:'ෑ',R:'ඍ',T:'ඔ',Y:'ශ',U:'ඹ',I:'ෂ',O:'ධ',P:'ඡ','{':'ඥ','}':':',A:'ෟ',S:'ී',D:'ෘ',F:'ෆ',G:'ඨ',H:'්‍ය',J:'ළු',K:'ණ',L:'ඛ',':':'ථ','"':',',Z:'"',X:'ඃ',C:'ඣ',V:'ඪ',B:'ඊ',N:'භ',M:'ඵ','<':'ළ','>':'ඝ','?':'?','|':'\u200c','\\':'\u200d'};
const altgr={KeyO:'ඳ',KeyX:'ඞ',KeyC:'ඦ',KeyV:'ඬ',Comma:'ඏ',Period:'ඟ'};
function eligible(el){
 if(!el||el.disabled||el.readOnly||el.isContentEditable||!['INPUT','TEXTAREA'].includes(el.tagName))return false;
 const type=(el.getAttribute('type')||'text').toLowerCase();if(!['text','search'].includes(type)&&el.tagName!=='TEXTAREA')return false;
 const name=(el.name||'').replace(/\[.*$/,'').toLowerCase(), id=(el.id||'').toLowerCase();
 if(/serial|barcode|sku|password|secret|token|email|phone|mobile|whatsapp|nic|registration|plate|invoice|job_number|price|amount|mileage|qty|quantity|code|reference|request|username/.test(name+' '+id))return false;
 if(el.closest('[data-input-english]'))return false;
 if(el.dataset.sinhalaInput==='true'||['openjobpicker','jobpickersearch'].includes(id))return true;
 const allowed=['name','customer_name','technician_name','supplier','make','model','generation','colour','category','condition','service_application','service_type','complaint','diagnosis','recommended_work','approved_work','parts_notes','warranty_notes','notes','address','credit_address','problem','findings','decision_notes','terms','description'];
 if(allowed.includes(name))return true;
 // Code/scanner fields can share a generic q name; keep stock and POS lookup untouched.
 if(name==='q'&&!/^\/ccs\/(stock|pos|warranties|customer-access)/.test(location.pathname))return true;
 return false;
}
function emit(el){el.dispatchEvent(new Event('input',{bubbles:true}));}
function reset(){composition=null;}
function insert(el,text){
 let start=el.selectionStart,end=el.selectionEnd;
 if(start===null||end===null)return false;
 if(el.maxLength>0 && el.value.length-(end-start)+text.length>el.maxLength)return false;
 // Pre-base kombuwa is typed before the consonant, but Unicode stores it after it.
 if(start===end&&start>0&&/[\u0d9a-\u0dc6]/u.test(text[0])&&el.value.slice(start-1,start)==='ෙ'){
  el.setRangeText((text+'ෙ').normalize('NFC'),start-1,end,'end');
 }else if(start===end&&start>0&&/[\u0dcf-\u0ddf]/u.test(text[0])&&/[\u0d9a-\u0dc6\u0dd9-\u0ddb]/u.test(el.value.slice(start-1,start))){
  el.setRangeText((el.value.slice(start-1,start)+text).normalize('NFC'),start-1,end,'end');
 }else el.setRangeText(text,start,end,'end');
 emit(el);return true;
}
document.addEventListener('beforeinput',function(event){
 const el=event.target;if(mode==='english'||!eligible(el)||event.isComposing){reset();return;}
 if(mode==='phonetic'&&event.inputType==='deleteContentBackward'&&composition&&composition.el===el&&el.selectionStart===el.selectionEnd&&el.selectionStart===composition.end&&el.value.slice(composition.start,composition.end)===composition.output){
  event.preventDefault();composition.raw=composition.raw.slice(0,-1);const output=transliterate(composition.raw);el.setRangeText(output,composition.start,composition.end,'end');composition.end=composition.start+output.length;composition.output=output;if(!composition.raw)reset();emit(el);return;
 }
 if(event.inputType!=='insertText'||typeof event.data!=='string'||event.data.length!==1){reset();return;}
 if(mode==='phonetic'){
  if(!/[A-Za-z]/.test(event.data)){reset();return;}
  const start=el.selectionStart,end=el.selectionEnd;if(start===null||end===null)return;
  if(!composition||composition.el!==el||start!==end||start!==composition.end||el.value.slice(composition.start,composition.end)!==composition.output)composition={el,start,end,raw:'',output:el.value.slice(start,end)};
  composition.raw+=event.data;const output=transliterate(composition.raw);event.preventDefault();if(el.maxLength>0 && el.value.length-(composition.end-composition.start)+output.length>el.maxLength){reset();return;}el.setRangeText(output,composition.start,composition.end,'end');composition.end=composition.start+output.length;composition.output=output;emit(el);return;
 }
 reset();const mapped=plain[event.data]??shifted[event.data];if(mapped!==undefined&&el.selectionStart!==null){event.preventDefault();insert(el,mapped);}
});
document.addEventListener('keydown',function(event){
 if(mode!=='wijesekara'||!eligible(event.target)||event.isComposing||!event.getModifierState||!event.getModifierState('AltGraph'))return;
 const mapped=altgr[event.code];if(mapped){event.preventDefault();reset();insert(event.target,mapped);}
});
document.addEventListener('focusin',reset);document.addEventListener('compositionstart',reset);document.addEventListener('pointerdown',function(e){if(e.target!==composition?.el)reset();});
switcher.dataset.enhanced='true';
picker.addEventListener('change',async function(){
 const old=mode,desired=picker.value,status=document.getElementById('ccsInputStatus');picker.disabled=true;status.textContent='';
 try{const body=new FormData(switcher);body.set('input_method',desired);const response=await fetch(switcher.action,{method:'POST',body,credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});if(!response.ok)throw new Error('save');const data=await response.json();if(data.input_method!==desired)throw new Error('save');mode=desired;reset();document.querySelectorAll('[data-input-setting]').forEach(el=>{el.value=desired});}
 catch(error){picker.value=old;status.textContent=window.ccsText?window.ccsText('Could not save input method. Try again.'):'Could not save input method. Try again.';}
 finally{picker.disabled=false;document.dispatchEvent(new Event('ccs-input-mode-changed'));}
});
})();
</script>
