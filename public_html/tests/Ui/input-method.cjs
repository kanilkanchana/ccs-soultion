const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const blade=fs.readFileSync('resources/views/ccs/partials/input-method-script.blade.php','utf8');
const js=blade.match(/<script>\s*([\s\S]*?)\s*<\/script>/)[1];
let handlers={},controls={},inputHandler;
const picker={value:'phonetic',disabled:false,addEventListener:(key,fn)=>inputHandler=fn};
const status={textContent:''},switcher={dataset:{},action:'/ccs/settings/input-method'};
let fetchSucceeds=true;
class Event {constructor(type,opts={}){this.type=type;this.bubbles=opts.bubbles}}
class FormData {constructor(){this.values={}}set(k,v){this.values[k]=v}}
const document={getElementById:id=>({ccsInputSwitch:switcher,ccsInputMode:picker,ccsInputStatus:status}[id]||null),addEventListener:(type,fn)=>handlers[type]=fn,querySelectorAll:()=>[],dispatchEvent:()=>true};
const context={document,location:{pathname:'/ccs/customers'},window:{},Event,FormData,fetch:async(url,options)=>({ok:fetchSucceeds,json:async()=>({input_method:options.body.values.input_method})})};
vm.runInNewContext(js,context);
function field(name,id='',type='text'){return {name,id,tagName:'INPUT',value:'',selectionStart:0,selectionEnd:0,maxLength:150,disabled:false,readOnly:false,isContentEditable:false,dataset:{},getAttribute:n=>n==='type'?type:null,closest:()=>null,setRangeText(txt,start,end){this.value=this.value.slice(0,start)+txt+this.value.slice(end);this.selectionStart=this.selectionEnd=start+txt.length},dispatchEvent(e){assert.equal(e.type,'input')}}}
function stroke(el,data,kind='insertText',composing=false){let prevented=false;const e={target:el,data,inputType:kind,isComposing:composing,preventDefault(){prevented=true}};handlers.beforeinput(e);if(!prevented&&kind==='insertText')el.setRangeText(data,el.selectionStart,el.selectionEnd);if(!prevented&&kind==='deleteContentBackward'&&el.selectionStart)el.setRangeText('',el.selectionStart-1,el.selectionEnd);return prevented}
function type(el,str){for(const ch of str)stroke(el,ch)}
for(const [roman,expected] of [['ka','ක'],['kA','කැ'],['kAa','කෑ'],['kii','කී'],['kru','කෘ'],['kruu','කෲ'],['kya','ක්‍ය'],['kra','ක්‍ර'],['kRa','ර්‍ක'],['nimal','නිමල්'],['kax','කං'],['kaH','කඃ'],['Lu','ඏ'],['Na','ණ']]){
 const el=field('name');handlers.focusin();type(el,roman);assert.equal(el.value,expected,roman);
}
// Every displayed guide combination must agree with the actual typing engine.
const guide=fs.readFileSync('resources/views/ccs/partials/phonetic-guide.blade.php','utf8');
for(const match of guide.matchAll(/\['([^']+)','([^']+)'\]/g)){
 for(const token of match[1].split(' / ')){
  const example=field('name');handlers.focusin();type(example,token);
  assert.equal(example.value,match[2],'Guide '+token);
 }
}
let el=field('name');handlers.focusin();type(el,'kAa');stroke(el,null,'deleteContentBackward');assert.equal(el.value,'කැ');stroke(el,null,'deleteContentBackward');assert.equal(el.value,'ක්');
let selected=field('name');handlers.focusin();selected.value='old';selected.selectionStart=0;selected.selectionEnd=3;type(selected,'ka');assert.equal(selected.value,'ක');
let limited=field('name');limited.maxLength=2;handlers.focusin();type(limited,'ka');assert.equal(limited.value,'ක');stroke(limited,'m');assert.equal(limited.value,'ක');
const serial=field('serial_number');handlers.focusin();type(serial,'ka123');assert.equal(serial.value,'ka123');
const phone=field('phone');handlers.focusin();type(phone,'abc123');assert.equal(phone.value,'abc123');
const password=field('password','','password');handlers.focusin();type(password,'ka');assert.equal(password.value,'ka');
const customer=field('q');handlers.focusin();type(customer,'nimal');assert.equal(customer.value,'නිමල්');
context.location.pathname='/ccs/stock';const stock=field('q');handlers.focusin();type(stock,'ka');assert.equal(stock.value,'ka');
context.location.pathname='/ccs/pos';const pickerInput=field('','openJobPicker');handlers.focusin();type(pickerInput,'ka');assert.equal(pickerInput.value,'ක');
const os=field('name');handlers.focusin();stroke(os,'ක','insertCompositionText',true);assert.equal(os.value,'');
(async()=>{
 picker.value='wijesekara';await inputHandler();assert.equal(picker.disabled,false);el=field('name');handlers.focusin();type(el,'fld');assert.equal(el.value,'කො','pre-base vowel order and NFC');
 const shift=field('name');handlers.focusin();type(shift,'LK');assert.equal(shift.value,'ඛණ');
 let prevent=false;handlers.keydown({target:shift,code:'KeyO',isComposing:false,getModifierState:key=>key==='AltGraph',preventDefault(){prevent=true}});assert(prevent);assert.equal(shift.value,'ඛණඳ');
 picker.value='english';await inputHandler();el=field('name');handlers.focusin();type(el,'nimal');assert.equal(el.value,'nimal');
 fetchSucceeds=false;picker.value='phonetic';await inputHandler();assert.equal(picker.value,'english');assert(status.textContent.includes('Could not save'));
 console.log('PASS: phonetic examples, edits, protected codes, native IME, Wijesekara composition, mode persistence and failed-save rollback');
})().catch(e=>{console.error(e);process.exitCode=1});
