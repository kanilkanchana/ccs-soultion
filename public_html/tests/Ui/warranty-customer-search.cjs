// Execute the actual warranty customer-search code; no browser rendering claimed.
const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');
class Node{constructor(){this.value='';this.textContent='';this.hidden=true;this.children=[];this.listeners={};this.style={};this.attributes={};}get options(){return this.children;}append(n){this.children.push(n);}replaceChildren(){this.children=[];}setAttribute(k,v){this.attributes[k]=v;}addEventListener(k,fn){this.listeners[k]=fn;}querySelector(){return this.children[0];}focus(){}click(){this.listeners.click();}}
const nodes={},get=id=>nodes[id]??=new Node();let submitted='';const cardFilter={value:'old serial filter'};
const select=get('warrantyCustomer');select.value='1';select.form={querySelector:()=>cardFilter,requestSubmit(){submitted=select.value;}};
for(const [id,label]of [['','All Customers'],['1','Kasun Perera · CCS-C000001 · 0771111111'],['8','Nimal Jayawardena · CCS-C000008 · 0770000000'],['9','Nimal Jayawardena · CCS-C000009 · 0772222222']]){const n=new Node();n.value=id;n.textContent=label;select.append(n);}
const source=fs.readFileSync(path.resolve(__dirname,'../../resources/views/ccs/warranties.blade.php'),'utf8');const a=source.indexOf("const $=id=>document.getElementById(id),customer=$('warrantyCustomer')"),b=source.indexOf("$('openPackages').onclick",a);assert(a>=0&&b>a);vm.runInNewContext(source.slice(a,b),{document:{getElementById:get,createElement:()=>new Node()},window:{ccsText:x=>x}});
const query=get('customerFilterText'),results=get('warrantyCustomerResults');const type=value=>{query.value=value;query.listeners.input();};
type('Nimal');assert.equal(results.children.length,2);assert(!results.children.some(n=>n.textContent.includes('Kasun')));assert.equal(submitted,'');results.children[1].click();assert.equal(submitted,'9');assert.equal(cardFilter.value,'');
type('ccs-c000008');assert.equal(results.children.length,1);query.listeners.keydown({key:'Enter',preventDefault(){}});assert.equal(submitted,'8');
type('077 222 2222');assert.equal(results.children.length,1);assert.match(results.children[0].textContent,/C000009/);
type('unknown');assert.equal(results.children.length,0);assert.match(get('warrantyCustomerSearchStatus').textContent,/No matching/);
type('');assert(results.hidden);assert.equal(get('warrantyCustomerSearchStatus').textContent,'');
console.log('PASS: warranty search visibly filters names/IDs/formatted phones, keeps same-name customers distinct, selects clicked ID and clears old card filter.');
