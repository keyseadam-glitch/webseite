// Screenshots alt (http://zmk-gruenenplan.de) gegen neu (http://localhost:8080), Seite für Seite,
// als Einzelbilder und als Nebeneinander-Bild. Reiner Lesezugriff auf die alte Seite.
// Aufruf:  node joomla-tools/screenshots.mjs [--nur-neu]
// Braucht: npm i -g playwright && npx playwright install chromium   (auf dem Mac)
import { chromium } from 'playwright';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const nurNeu = process.argv.includes('--nur-neu');
const ALT = process.env.ALT_URL || 'http://zmk-gruenenplan.de';
const NEU = process.env.NEU_URL || 'http://localhost:8080';
const out = new URL('../docs/screenshots/', import.meta.url).pathname;
mkdirSync(out, { recursive: true });

// Seitenliste aus quelle/inhalte.json (Menü flach)
const data = JSON.parse(readFileSync(new URL('../quelle/inhalte.json', import.meta.url), 'utf8'));
const flat = (items, prefix = '') => items.flatMap(it => [
  ...(it.artikel ? [{ name: (prefix + it.alias), altPfad: it.altPfad, neuPfad: it.home ? '/' : '/index.php/' + prefix + it.alias }] : []),
  ...(it.children ? flat(it.children, prefix + it.alias + '/') : []),
]);
const seiten = flat(data.menu);

const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH || undefined });
const ctx = await browser.newContext({ viewport: { width: 1024, height: 768 }, deviceScaleFactor: 1 });
const page = await ctx.newPage();
const shot = async (url, file) => {
  try {
    await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
    await page.screenshot({ path: file, fullPage: true });
    return true;
  } catch (e) { console.log(`  FEHLER ${url}: ${e.message.split('\n')[0]}`); return false; }
};
const rows = [];
for (const s of seiten) {
  const safe = s.name.replace(/\//g, '__');
  console.log(s.name);
  const neu = await shot(NEU + s.neuPfad, `${out}${safe}.neu.png`);
  let alt = false;
  if (!nurNeu) alt = await shot(ALT + (s.altPfad || s.neuPfad), `${out}${safe}.alt.png`);
  rows.push({ name: s.name, alt, neu, safe });
}
await browser.close();
// Übersicht als HTML (alt links, neu rechts)
const html = `<!doctype html><meta charset="utf-8"><title>ZMK Grünenplan – alt gegen neu</title>
<style>body{font-family:sans-serif;margin:16px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:6px;vertical-align:top;width:50%}img{width:100%;border:1px solid #ddd}.fehlt{color:#c00}</style>
<h1>ZMK Grünenplan – alt (links) gegen neu (rechts)</h1>
${rows.map(r => `<h2>${r.name}</h2><table><tr><th>alt: ${ALT}</th><th>neu: ${NEU}</th></tr><tr>
<td>${r.alt ? `<img src="${r.safe}.alt.png">` : '<p class="fehlt">Alt-Screenshot fehlt (Seite nicht erreichbar oder --nur-neu)</p>'}</td>
<td>${r.neu ? `<img src="${r.safe}.neu.png">` : '<p class="fehlt">Neu-Screenshot fehlt</p>'}</td></tr></table>`).join('\n')}`;
writeFileSync(`${out}vergleich.html`, html);
console.log(`Fertig: ${out}vergleich.html`);
