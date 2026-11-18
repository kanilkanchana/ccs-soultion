// DOM-mock behavior checks for actual POS JavaScript. This does not render browser layout.
const fs=require('fs'),vm=require('vm'),assert=require('assert');
class Node {
 constructor(tag='div'){this.tagName=tag.toUpperCase();this.children=[];this.listeners={};this.attributes={};this.dataset={};this._value='';this.textContent='';this.disabled=false;this.open=false;this.hidden=false;this.style={};this.classList={add(){},remove(){},toggle(){}};}
 set value(v){this._value=String(v??'');}get value(){return this._value;}
 get options(){return this.children;}get selectedOptions(){return this.children.filter(n=>n.value===this.value);}get selectedIndex(){return this.children.findIndex(n=>n.value===this.value);}
 append(...nodes){this.children.push(...nodes);if(this.tagName==='SELECT'&&this.children.length===nodes.length&&nodes.length)this.value=nodes[0].value;}
 replaceChildren(...nodes){this.children=[];this._value='';this.append(...nodes);}
 addEventListener(name,fn){(this.listeners[name]??=[]).push(fn);}setAttribute(k,v){this.attributes[k]=v;}focus(){}scrollIntoView(){}setCustomValidity(v){this.validity=v;}reportValidity(){}querySelectorAll(){return [];}querySelector(){return null;}showModal(){this.open=true;}close(){this.open=false;}
 click(){if(this.disabled)return;const e={preventDefault(){},target:this};for(const fn of this.listeners.click??[])fn(e);if(this.onclick)this.onclick(e);}
}
const nodes={},get=id=>nodes[id]??=new Node(['jobPick','stockPart','stockUnit','entryType'].includes(id)?'select':'div');
const doc={getElementById:get,createElement:tag=>new Node(tag),querySelectorAll(){return [];},addEventListener(){}};
for(const [id,value]of Object.entries({entryType:'service',entryQty:'1',entryPrice:'0',discount:'0',givenMoney:'0',stockQuery:'',stockQuantity:'1',jobPick:'1'}))get(id).value=value;
get('manualEntry').open=true;
const products=[{id:1,name:'Real serialized part',sku:'P-1',has_serial:true,quantity_on_hand:1,selling_price_lkr:10000,item_year:'2018',condition:'Recon',supplier:'Supplier',units:[{id:7,serial_number:'0007'}],default_warranty_rule_id:5,warranty_package:'Battery 1 year'},{id:2,name:'Real ordinary part',sku:'P-2',has_serial:false,quantity_on_hand:5,selling_price_lkr:500,units:[],default_warranty_rule_id:null}];
const ctx={document:doc,window:{ccsText:x=>x},form:{requestSubmit(){}},confirm:()=>true,alert(){},fetch:async(url)=>({ok:true,json:async()=>String(url).includes('serial=')?({reading:null}):({data:products,enabled:true})}),URLSearchParams,setTimeout,clearTimeout};
let s=fs.readFileSync(require('path').resolve(__dirname,'../../resources/views/ccs/pos.blade.php'),'utf8');let start=s.indexOf("(()=>{  \n'use strict';");assert(start>=0);let end=s.indexOf('})();',start)+5;let code=s.slice(start,end).replace(/\{\{[\s\S]*?\}\}/g,'"/test"');vm.runInNewContext(code,ctx);
(async()=>{await new Promise(resolve=>setImmediate(resolve));
 assert.equal(get('stockCards').children.length,2);assert.equal(get('saveBtn').disabled,true);
 // Plain part adds directly, preserving IDs/metadata in the submitted arrays.
 get('stockCards').children[1].click();assert.equal(get('itemRows').children.length,1);assert.equal(get('subtotalText').textContent,'Rs. 500.00');assert.equal(get('saveBtn').disabled,false);
 let inputs=get('postLines').children;assert.equal(inputs.find(n=>n.name==='inventory_item_id[]').value,'2');
 const find=(n,label)=>{if(n.attributes['aria-label']===label)return n;for(const child of n.children){const found=find(child,label);if(found)return found;}};
 find(get('itemRows'),'Increase quantity of Real ordinary part').click();assert.equal(get('subtotalText').textContent,'Rs. 1,000.00');
 get('discount').value='100';get('givenMoney').value='400';get('discount').listeners.input[0]();assert.equal(get('grandText').textContent,'Rs. 900.00');assert.equal(get('changeText').textContent,'Rs. 500.00');
 // Serialized card asks for actual serial, never silently chooses a unit.
 get('stockCards').children[0].click();assert.equal(get('stockSelectDialog').open,true);assert.equal(get('stockUnit').value,'');assert.equal(get('itemRows').children.length,1);
 get('stockUnit').value='7';get('addStockPart').click();assert.equal(get('stockSelectDialog').open,false);assert.equal(get('itemRows').children.length,2);
 inputs=get('postLines').children;assert(inputs.some(n=>n.name==='stock_unit_id[]'&&n.value==='7'));assert(inputs.some(n=>n.name==='expected_warranty_rule_id[]'&&n.value==='5'));assert.equal(find(get('itemRows'),'Increase quantity of Real serialized part').disabled,true);
 // Manual metadata and leading-zero serial survive the compact cart.
 get('entryType').value='manual_part';get('entryDesc').value='Customer part';get('entryQty').value='1';get('entryPrice').value='50';get('entrySerial').value='000ABC';get('entryYear').value='2019';get('entryCondition').value='Used';get('entrySupplier').value='Owner';get('addItem').click();inputs=get('postLines').children;assert(inputs.some(n=>n.name==='manual_serial[]'&&n.value==='000ABC'));assert(inputs.some(n=>n.name==='item_year[]'&&n.value==='2019'));assert(inputs.some(n=>n.name==='item_supplier[]'&&n.value==='Owner'));
 get('clearItems').click();assert.equal(get('saveBtn').disabled,true);assert.equal(get('postLines').children.length,0);
 // Battery-module popup blocks incomplete readings and submits measured zero correctly.
 products[0].is_battery_module=true;get('stockCards').children[0].click();get('stockUnit').value='7';get('addStockPart').click();assert.equal(get('postLines').children.length,0);assert.equal(get('stockSelectDialog').open,true);
 get('stockModulePosition').value='1';get('stockModuleVoltage').value='0';get('addStockPart').click();assert.equal(get('itemRows').children.length,1);inputs=get('postLines').children;assert(inputs.some(n=>n.name==='module_voltage[]'&&n.value==='0'));assert(inputs.some(n=>n.name==='module_position[]'&&n.value==='1'));assert(inputs.some(n=>n.name==='is_battery_module[]'&&n.value==='1'));
 console.log('POS JS behavior passed: real cards, direct add, serial confirmation, quantity, discount/received/due, submitted metadata, warranty ID, clear state');
})().catch(e=>{console.error(e);process.exitCode=1;});
