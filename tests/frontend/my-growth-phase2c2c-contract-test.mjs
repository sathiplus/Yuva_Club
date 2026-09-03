import assert from 'node:assert/strict';import{readFile}from'node:fs/promises';
const root=new URL('../../',import.meta.url);const[page,css,portal,parent,org]=await Promise.all(['my-growth.php','assets/my-growth.css','portal.php','parent.php','organization-admin.php'].map(p=>readFile(new URL(p,root),'utf8')));
for(const text of['My Growth','Your Skills','Next best action','Personal Bests','Benchmarks','Achievements','Recent comparable practice'])assert.ok(page.includes(text),`Missing ${text}`);
assert.ok(page.includes('Complete another compatible challenge')&&page.includes('No Personal Best yet'),'Honest sparse-data states required');
assert.ok(page.includes('private progress profile')&&parent.includes('linked-child-only')&&org.includes('Authorized same-organization summary'),'Privacy copy/contexts missing');
assert.ok(!page.includes('leaderboard')&&!page.includes('daily streak')&&!page.includes('YUVA Score'),'Forbidden growth gamification present');
assert.ok(portal.includes('href="my-growth.php"'),'Student entry point missing');assert.ok(css.includes('@media')&&css.includes('grid-template-columns'),'Responsive visual layout missing');
console.log('PASS Phase 2C.2C My Growth frontend/privacy contract');
