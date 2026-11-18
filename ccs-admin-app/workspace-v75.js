(()=>{'use strict';
const main=document.querySelector('.wrap>main'),content=document.getElementById('ccsPageContent');
if(!main||!content||document.body.classList.contains('ccs-pos-page'))return;
const selector='.directory-scroll,.stock-scroll,.web-intake-table,.serial-records-scroll,.jobs-table-scroll,.finance-records-scroll,.table-wrap,.v68-inspector,.v68-card-detail,.settings-main';
let queued=false;
function fit(){queued=false;
 // Small screens retain natural flow inside the content area, so stacked forms stay reachable.
 document.body.classList.toggle('ccs-region-scroll',window.innerWidth>=761);
 const enabled=document.body.classList.contains('ccs-region-scroll');
 const bottom=content.getBoundingClientRect().bottom-12;
 function available(region){
  let reserve=0;
  for(let e=region;e&&e!==content;e=e.parentElement){
   const style=getComputedStyle(e);
   reserve+=(parseFloat(style.marginBottom)||0);
   if(e!==region)reserve+=(parseFloat(style.paddingBottom)||0)+(parseFloat(style.borderBottomWidth)||0);
  }
  return bottom-region.getBoundingClientRect().top-reserve;
 }
 const jobs=content.querySelector('.jobs-master');
 if(jobs){const height=available(jobs);const wide=window.innerWidth>1050&&height>=240;
 jobs.classList.toggle('v75-jobs-fit',enabled&&wide);jobs.style.setProperty('--v75-region-height',Math.max(240,height)+'px');
 const side=jobs.querySelector('.job-side');if(side){side.classList.toggle('v75-scroll-region',enabled&&!wide);side.style.setProperty('--v75-region-height','65dvh');}}
 const regions=[...content.querySelectorAll(selector)].filter(e=>!e.closest('dialog')&&!e.closest('[hidden]'));
 for(const region of regions){
 // Only the outer record/details region scrolls; no duplicate nested scrollbars.
 const nested=regions.some(other=>other!==region&&other.contains(region));
 const height=available(region);
 region.classList.toggle('v75-scroll-region',enabled&&!nested);
 region.style.setProperty('--v75-region-height',Math.max(180,height)+'px');
 }
}
function request(){if(!queued){queued=true;requestAnimationFrame(fit);}}
window.addEventListener('resize',request);window.addEventListener('load',request);
document.addEventListener('toggle',request,true);
// Page scripts may create record inspectors after this script starts.
const observer=new MutationObserver(records=>{if(records.some(r=>r.type==='childList'))request();});
observer.observe(content,{childList:true,subtree:true});
if(window.ResizeObserver){const resize=new ResizeObserver(request);resize.observe(main);const toolbar=main.querySelector('.v70-toolbar');if(toolbar)resize.observe(toolbar);}
request();
})();
