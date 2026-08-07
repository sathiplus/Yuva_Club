import assert from 'node:assert/strict';
import { readdir, readFile, stat } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const releaseVersion = 'release-1.0.2-20260802';
const publicPages = [
  'index.html',
  'programs.html',
  'challenges.html',
  'curriculum.html',
  'stories.html',
  'resources.html',
  'about.html',
  'partners.html',
  'faq.html',
  'app.html',
  'safety.html',
  'privacy.html',
  'terms.html',
  'contact.html',
  'offline.html',
];
const phpPages = [
  'registration.php',
  'portal-login.php',
  'parent-login.php',
  'admin-login.php',
  'leaderboard.php',
];
const topicNames = (await readdir(new URL('pages/', root)))
  .filter((name) => name.endsWith('.html'));
const auditedSources = [];

assert.ok(topicNames.length > 100, 'Topic page inventory is unexpectedly incomplete');

for (const name of publicPages) {
  const source = await readFile(new URL(name, root), 'utf8');
  auditedSources.push([name, source]);
  assert.ok(source.includes(`assets/site.css?v=${releaseVersion}`), `${name} lacks current base CSS`);
  assert.ok(source.includes(`assets/public-site.css?v=${releaseVersion}`), `${name} lacks current public design system`);
  assert.ok(source.includes(`assets/app.js?v=${releaseVersion}`), `${name} lacks current public behavior`);
  assert.ok(source.includes('class="public-skip-link"'), `${name} lacks skip navigation`);
  assert.ok(source.includes('id="main-content"'), `${name} lacks main skip target`);
  assert.ok(source.includes('class="site-header horizon-header"'), `${name} lacks shared public header`);
  assert.ok(source.includes('class="site-footer horizon-footer"'), `${name} lacks shared public footer`);
  assert.ok(source.includes('id="public-navigation"'), `${name} lacks shared navigation`);
  assert.match(source, /<html lang="en">/);
  assert.match(source, /<title>[^<]+<\/title>/);
}

for (const name of topicNames) {
  const source = await readFile(new URL(`pages/${name}`, root), 'utf8');
  auditedSources.push([`pages/${name}`, source]);
  assert.ok(source.includes(`../assets/site.css?v=${releaseVersion}`), `${name} lacks current base CSS`);
  assert.ok(source.includes(`../assets/public-site.css?v=${releaseVersion}`), `${name} lacks current public design system`);
  assert.ok(source.includes(`../assets/app.js?v=${releaseVersion}`), `${name} lacks current public behavior`);
  assert.ok(source.includes('class="public-skip-link"'), `${name} lacks skip navigation`);
  assert.ok(source.includes('id="main-content"'), `${name} lacks main skip target`);
  assert.ok(source.includes('class="site-header horizon-header"'), `${name} lacks shared public header`);
  assert.ok(source.includes('class="site-footer horizon-footer"'), `${name} lacks shared public footer`);
}

for (const name of phpPages) {
  const source = await readFile(new URL(name, root), 'utf8');
  auditedSources.push([name, source]);
  assert.ok(source.includes(`public-site.css?v=${releaseVersion}`), `${name} lacks current public design system`);
  assert.ok(source.includes('class="public-skip-link"'), `${name} lacks skip navigation`);
  assert.ok(source.includes('id="main-content"'), `${name} lacks main skip target`);
}

const expectedNavigation = [
  'index.html',
  'programs.html',
  'programs.html#how-it-works',
  'resources.html',
  'about.html',
  'registration.php',
  'portal-login.php',
  'parent-login.php',
  'admin-login.php',
];
for (const [name, source] of auditedSources.filter(([name]) => name.endsWith('.html'))) {
  const prefix = name.startsWith('pages/') ? '../' : '';
  for (const href of expectedNavigation) {
    assert.ok(source.includes(`href="${prefix}${href}"`), `${name} navigation misses ${href}`);
  }
}

const home = await readFile(new URL('index.html', root), 'utf8');
for (const href of [
  'index.html',
  'programs.html',
  'programs.html#how-it-works',
  'resources.html',
  'about.html',
  'portal-login.php',
  'parent-login.php',
  'admin-login.php',
  'registration.php',
]) {
  assert.ok(home.includes(`href="${href}"`), `Homepage navigation misses ${href}`);
}
for (const href of ['privacy.html', 'terms.html', 'safety.html', 'contact.html']) {
  assert.ok(home.includes(`href="${href}"`), `Homepage footer misses ${href}`);
}
for (const section of [
  'horizon-hero',
  'horizon-why',
  'horizon-pillars',
  'horizon-journey-intro',
  'platform-experience',
  'leadership-story',
  'parents-section',
  'start-free-section',
  'organizations-section',
  'trust-section',
  'horizon-final-cta',
]) {
  assert.ok(home.includes(section), `Homepage misses approved Project Horizon section: ${section}`);
}
assert.ok(home.includes('First two presentations free'));
assert.ok(home.includes('Request a School or Organization Demo'));
assert.ok(!home.includes('Pricing'), 'Homepage must not introduce pricing');

const programs = await readFile(new URL('programs.html', root), 'utf8');
for (const href of [
  'index.html',
  'programs.html',
  '#how-it-works',
  'resources.html',
  'about.html',
  'portal-login.php',
  'parent-login.php',
  'admin-login.php',
  'registration.php',
]) {
  assert.ok(programs.includes(`href="${href}"`), `Programs navigation misses ${href}`);
}
for (const section of [
  'programs-hero',
  'journey-choice',
  'how-it-works-section',
  'public-leadership-journey',
  'programs-final-cta',
]) {
  assert.ok(programs.includes(section), `Programs misses approved Project Horizon section: ${section}`);
}
for (const preservedContent of [
  'School YUVA',
  'College YUVA',
  'Ages 13–17',
  'Ages 18–21',
  'Explorer',
  'Speaker',
  'Leader',
  'Mentor',
]) {
  assert.ok(programs.includes(preservedContent), `Programs misses preserved content: ${preservedContent}`);
}
assert.ok(programs.includes('administrator, mentor, or judge'), 'Programs must preserve approved advancement');
assert.ok(!programs.includes('Pricing'), 'Programs must not introduce pricing');
for (const stage of ['Discover', 'Learn', 'Practice', 'Present', 'Reflect', 'Improve', 'Lead', 'Inspire']) {
  assert.ok(home.includes(`<strong>${stage}</strong>`), `Homepage misses official YUVA Journey stage: ${stage}`);
  assert.ok(programs.includes(`<p class="card-label">${stage}</p>`), `Programs misses official YUVA Journey stage: ${stage}`);
}
for (const statement of [
  'I’m discovering my voice.',
  'I’m learning to communicate with confidence.',
  'I’m learning to inspire others.',
  'I’m helping others grow.',
]) {
  assert.ok(programs.includes(statement), `Programs misses approved leadership statement: ${statement}`);
}
assert.ok(programs.includes('Leadership Isn’t Built Overnight'));
assert.ok(programs.includes('Request a School or Organization Demo'));

const milestoneThreeRoutes = {
  'about.html': ['Every voice carries the potential to lead.', 'Lead', 'Communicate', 'Think', 'Impact', 'Technology should empower people.'],
  'partners.html': ['Bring YUVA Club to Your Students', 'Request a School or Organization Demo', 'Request a demo', 'Launch the pilot', 'Review outcomes'],
  'resources.html': ['Leadership learning hub', 'Parent Resources', 'Organization Resources', 'Featured existing content', 'Trusted places to continue learning.'],
  'faq.html': ['What is YUVA Club?', 'What is included free?', 'Are students judged by AI?', 'How are leadership levels approved?', 'How is student safety and privacy handled?'],
};
for (const [name, requiredCopy] of Object.entries(milestoneThreeRoutes)) {
  const source = await readFile(new URL(name, root), 'utf8');
  for (const text of requiredCopy) {
    assert.ok(source.includes(text), `${name} misses approved Milestone 3 content: ${text}`);
  }
  for (const href of ['index.html', 'programs.html', 'programs.html#how-it-works', 'resources.html', 'about.html', 'portal-login.php', 'parent-login.php', 'admin-login.php', 'registration.php']) {
    assert.ok(source.includes(`href="${href}"`), `${name} misses approved public navigation link: ${href}`);
  }
}

const contact = await readFile(new URL('contact.html', root), 'utf8');
for (const contract of [
  'horizon-home horizon-contact',
  'Start with the right conversation.',
  'School or organization',
  'Verified public contact route',
  'public-login-menu',
]) {
  assert.ok(contact.includes(contract), `Contact page misses Horizon contract: ${contract}`);
}

const registration = await readFile(new URL('registration.php', root), 'utf8');
for (const contract of [
  'action="submit-registration.php"',
  'method="post"',
  '<?php echo csrf_field(); ?>',
  'name="form_name"',
  'name="student_first_name"',
  'name="student_last_name"',
  'name="date_of_birth"',
  'name="account_password"',
  'name="parent_email"',
  'name="agree_code"',
]) {
  assert.ok(registration.includes(contract), `Registration contract missing: ${contract}`);
}
for (const message of ['Registration is free', 'First two presentations', 'Practice Studio', 'Presentation Studio', 'Leadership Journey', 'Challenges', 'AI Mentor is not included']) {
  assert.ok(registration.includes(message), `Registration misses approved onboarding message: ${message}`);
}

const portalLibrary = await readFile(new URL('portal-lib.php', root), 'utf8');
assert.ok(
  portalLibrary.includes('bool $horizonPublic = false'),
  'Public login presentation must be opt-in and preserve protected-page defaults'
);
assert.ok(portalLibrary.includes('horizon-public-login'));
for (const name of ['portal-login.php', 'parent-login.php', 'admin-login.php']) {
  const source = await readFile(new URL(name, root), 'utf8');
  assert.ok(
    source.includes("portal_header(") && /portal_header\([^;]+,\s*true\);/.test(source),
    `${name} does not opt into the approved public login presentation`
  );
  assert.ok(source.includes("portal_footer(false, true);"), `${name} lacks the approved public footer`);
}

const studentLogin = await readFile(new URL('portal-login.php', root), 'utf8');
for (const contract of [
  'Email Address *',
  'name="login_identifier"',
  'name="password"',
  'autocomplete="current-password"',
  'Forgot password?',
]) {
  assert.ok(studentLogin.includes(contract), `Student login restoration missing: ${contract}`);
}
assert.ok(
  !studentLogin.includes('name="date_of_birth"'),
  'Student login must not use date of birth as the normal credential'
);

for (const name of ['privacy.html', 'terms.html', 'contact.html']) {
  const source = await readFile(new URL(name, root), 'utf8');
  for (const href of ['index.html', 'safety.html', 'privacy.html', 'terms.html', 'contact.html']) {
    assert.ok(source.includes(`href="${href}"`), `${name} navigation misses ${href}`);
  }
}

const css = await readFile(new URL('assets/public-site.css', root), 'utf8');
assert.ok(css.includes('@media (max-width: 720px)'));
assert.ok(css.includes('@media (prefers-reduced-motion: reduce)'));
assert.ok(css.includes('.public-skip-link:focus'));
assert.ok(css.includes('.horizon-motion-ready [data-horizon-reveal]'));
assert.ok(!/https?:\/\//.test(css), 'Public CSS must not use external runtime dependencies');
for (const selector of [
  '.horizon-header',
  '.horizon-nav',
  '.public-menu-button',
  '.public-login-menu',
  '.horizon-hero',
  '.horizon-section-heading',
  '.button.primary',
  '.programs-hero',
  '.journey-choice-card',
  '.resource-hub-card',
  '.story-card',
  '.faq-groups',
  '.public-content-page',
  '.public-topic-page',
  '.horizon-public-login',
  '.horizon-footer',
]) {
  assert.ok(css.includes(selector), `Public design system misses required selector: ${selector}`);
}

for (const asset of [
  'assets/logo.png',
  'assets/yuva-symbol.png',
  'assets/logo-public.webp',
  'assets/yuva-symbol-public.webp',
  'assets/website-v3-hero.webp',
  'assets/public-site.css',
]) {
  assert.ok((await stat(new URL(asset, root))).isFile(), `Missing local asset: ${asset}`);
}

const appScript = await readFile(new URL('assets/app.js', root), 'utf8');
assert.ok(appScript.includes("prefers-reduced-motion: reduce"));
assert.ok(appScript.includes('IntersectionObserver'));

for (const [name, source] of auditedSources) {
  assert.ok(!source.includes('public-site.css?v=1'), `${name} references obsolete public stylesheet`);
  assert.ok(!source.includes('KarmaBro'), `${name} contains obsolete public branding`);
  assert.ok(!/karmabro\.com/i.test(source), `${name} contains an obsolete public domain`);
  const references = [...source.matchAll(/(?:href|src)="([^"]+)"/g)]
    .map((match) => match[1])
    .filter((value) =>
      value !== ''
      && !value.startsWith('#')
      && !value.startsWith('http:')
      && !value.startsWith('https:')
      && !value.startsWith('mailto:')
      && !value.startsWith('data:')
    );
  for (const reference of references) {
    const cleanReference = reference.split('#')[0].split('?')[0];
    if (cleanReference === '') continue;
    const target = new URL(cleanReference, new URL(name, root));
    assert.ok((await stat(target)).isFile(), `${name} has a broken local reference: ${reference}`);
  }
}

console.log(`PASS ${publicPages.length + phpPages.length + topicNames.length} public user-facing pages`);
console.log('PASS shared navigation, branding, and local Design System V1 assets');
console.log('PASS public links and local asset references');
console.log('PASS skip navigation, responsive behavior, and reduced motion');
console.log('PASS no external runtime CSS dependency');
