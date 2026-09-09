import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import vm from 'node:vm';
const source=await readFile(new URL('../../service-worker.js',import.meta.url),'utf8');
const origin='https://www.yuvaclub.app';
const handlers={}; const stores=new Map([['yuva-club-demo-request-v2',new Map([[origin+'/portal.php/probe',{private:true}]])]]);
let offline=false, redirect=false, control='', claimed=false;
class Request { constructor(input,options={}) { Object.assign(this,typeof input==='string'?{url:new URL(input,origin).href,method:'GET',mode:'cors'}:input,options); } }
const cacheFor=name=>{if(!stores.has(name))stores.set(name,new Map());const entries=stores.get(name);return {put:async(r,v)=>entries.set(typeof r==='string'?new URL(r,origin).href:r.url,v),match:async r=>entries.get(typeof r==='string'?new URL(r,origin).href:r.url)}};
const caches={open:async name=>cacheFor(name),keys:async()=>[...stores.keys()],delete:async name=>stores.delete(name)};
vm.runInNewContext(source,{URL,Request,caches,self:{location:{origin},addEventListener:(name,fn)=>handlers[name]=fn,skipWaiting:async()=>{},clients:{claim:async()=>{assert.ok(!stores.has('yuva-club-demo-request-v2'));claimed=true}}},fetch:async request=>{
 if(offline)throw new Error('offline');
 return {ok:true,type:'basic',url:redirect?origin+'/portal.php':request.url,redirected:redirect,headers:{get:()=>control},clone(){return this}};
}});
async function lifecycle(name){let pending;handlers[name]({waitUntil:p=>pending=p});await pending;}
async function dispatch(path,mode='navigate'){let pending;handlers.fetch({request:new Request(origin+path,{mode}),respondWith:p=>pending=p});return pending?await pending:null;}
const privatePaths=['/portal.php','/parent.php','/admin.php','/my-growth.php'].flatMap(p=>[p,p+'/probe',p+'/asset.css',p+'?x=1']);
privatePaths.push('/student','/parent','/admin','/growth','/portal.PHP/probe','/portal%2ephp/probe','/private.html','/assets/unknown.js','/index.html?private=1');
for(const path of privatePaths)assert.equal(await dispatch(path),null,`Network-only: ${path}`);
await lifecycle('install');await lifecycle('activate');assert.ok(claimed);
const current=stores.get('yuva-club-public-only-v3');
for(const path of privatePaths)assert.ok(!current.has(origin+path),`No private cache write: ${path}`);
offline=true; // Simulate logout, disconnected network, and a previously poisoned old cache.
for(const path of privatePaths)assert.equal(await dispatch(path),null,`No offline private read after logout: ${path}`);
for(const path of ['/index.html','/assets/site.css?v=release-1.0.2-20260802','/assets/app.js?v=demo-request-v2','/assets/logo.png'])assert.ok(await dispatch(path),`Public offline asset: ${path}`);
offline=false;
for(const forbidden of ['private','no-store']){control=forbidden;current.delete(origin+'/index.html');await dispatch('/index.html');assert.ok(!current.has(origin+'/index.html'));}
control='';redirect=true;await dispatch('/index.html');assert.ok(!current.has(origin+'/index.html'),'Redirected private response is never cached');
redirect=false;await dispatch('/index.html');assert.ok(current.has(origin+'/index.html'));
console.log('PASS private PHP/path-info/aliases deny by default; old-cache purge; offline after logout; public assets; redirected/private responses');
