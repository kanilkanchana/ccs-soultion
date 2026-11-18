const fs=require('node:fs');
const vm=require('node:vm');
const assert=require('node:assert/strict');
const source=fs.readFileSync('resources/views/ccs/claims.blade.php','utf8');
const match=source.match(/<script>\s*([\s\S]*?)\s*<\/script>/);
assert(match,'Claim modal script missing');
function run(retry){
 const listeners={};const dialog={id:'claim-details-2',open:false,showModal(){this.open=true},close(){this.open=false}};
 const opener={dataset:{openClaim:dialog.id},addEventListener(type,fn){listeners.open=fn}};
 const closer={addEventListener(type,fn){listeners.close=fn},closest(){return dialog}};
 const document={querySelectorAll(q){return q==='[data-open-claim]'?[opener]:q==='[data-close-claim]'?[closer]:[]},getElementById(id){return id===dialog.id?dialog:null}};
 let script=match[1].replace(/@if\(\$errors->any\(\) && old\('claim_dialog'\)\)/,'if ('+(retry?'true':'false')+') {').replace(/@json\('claim-details-'\.old\('claim_dialog'\)\)/,JSON.stringify(dialog.id)).replace('@endif','}');
 vm.runInNewContext(script,{document});
 assert.equal(dialog.open,retry,'Retry opens only the matching dialog');
 if(retry){listeners.close();assert.equal(dialog.open,false)}
 listeners.open();assert.equal(dialog.open,true);listeners.close();assert.equal(dialog.open,false);
}
run(false);run(true);
assert(!/@(?:endif|endforeach|endforelse)@/.test(source),'Adjacent Blade close directives must stay separated');
console.log('PASS: claim card opens/closes; validation retry reopens its dialog; Blade close boundaries are separated');
