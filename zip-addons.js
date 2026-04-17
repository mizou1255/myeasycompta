/**
 * zip-addons.js — Génère un ZIP de distribution pour chaque addon myEasyCompta.
 *
 * Usage :
 *   node zip-addons.js              → tous les addons
 *   node zip-addons.js payment sms  → addons spécifiques (dossiers partiels acceptés)
 *
 * Les ZIPs sont créés dans le dossier de chaque addon (ex: my-easy-compta-payment.zip)
 * ET dans dist/addons/ à la racine du plugin principal.
 */

'use strict';

const fs      = require('fs');
const path    = require('path');
const archiver = require('archiver');

// ── Répertoires ──────────────────────────────────────────────────────────────
const PLUGINS_DIR = path.resolve(__dirname, '..');
const DIST_DIR    = path.join(__dirname, 'dist', 'addons');

// ── Liste des dossiers addon (tous ceux qui commencent par my-easy-compta- ou myeasycompta-) ──
const ADDON_DIRS = fs.readdirSync(PLUGINS_DIR).filter(name => {
  if (name === 'my-easy-compta') return false; // plugin principal, pas un addon
  if (name === 'my-easy-compta-license-server') return false;
  if (!/^(my-easy-compta-|myeasycompta-)/.test(name)) return false;
  // Ignorer les dossiers backup versionné (ex: my-easy-compta-backup-v1.4.6)
  if (/-v\d+\.\d+/.test(name)) return false;
  const full = path.join(PLUGINS_DIR, name);
  return fs.statSync(full).isDirectory();
});

// ── Filtrage par argument CLI ─────────────────────────────────────────────────
const filters = process.argv.slice(2);
const targets = filters.length
  ? ADDON_DIRS.filter(name => filters.some(f => name.includes(f)))
  : ADDON_DIRS;

if (targets.length === 0) {
  console.error('Aucun addon trouvé pour :', filters.join(', '));
  process.exit(1);
}

// ── Exclusions globales ───────────────────────────────────────────────────────
const EXCLUDE_DIRS  = new Set(['node_modules', '.git', 'svn', 'tests', 'src', '.vscode', '.idea', 'dist', 'coverage']);
const EXCLUDE_FILES = new Set([
  'package.json', 'package-lock.json', 'yarn.lock',
  'vite.config.js', 'vite.config.ts',
  'webpack.config.js', 'rollup.config.js',
  'tailwind.config.js', 'postcss.config.js',
  'tsconfig.json', 'jsconfig.json',
  '.eslintrc', '.eslintrc.js', '.eslintrc.json', '.eslintignore',
  '.prettierrc', '.prettierignore',
  '.gitignore', '.gitattributes',
  'composer.lock',
  'README.md', 'readme.txt.bak',
  'CLAUDE.md', '*.code-workspace',
  'zip-gen.js',
  '.DS_Store', 'Thumbs.db',
]);
const EXCLUDE_EXTS  = new Set(['.map']);

function shouldExclude(filePath, rootDir) {
  const base = path.basename(filePath);
  const rel  = path.relative(rootDir, filePath);
  const stat = fs.lstatSync(filePath);

  if (stat.isDirectory()) {
    return EXCLUDE_DIRS.has(base);
  }

  if (EXCLUDE_FILES.has(base)) return true;
  if (EXCLUDE_EXTS.has(path.extname(base))) return true;

  // Exclure les ZIPs à la racine de l'addon (self-zip)
  if (!rel.includes(path.sep) && base.endsWith('.zip')) return true;

  return false;
}

// ── Utilitaire d'archivage ────────────────────────────────────────────────────
function zipAddon(addonDir, destPath) {
  return new Promise((resolve, reject) => {
    fs.mkdirSync(path.dirname(destPath), { recursive: true });

    const output  = fs.createWriteStream(destPath);
    const archive = archiver('zip', { zlib: { level: 9 } });

    output.on('close', () => resolve(archive.pointer()));
    archive.on('error', reject);
    archive.pipe(output);

    // Ajouter les fichiers récursivement dans un sous-dossier nommé comme le plugin
    const addonName = path.basename(addonDir);

    function addDir(dir) {
      for (const entry of fs.readdirSync(dir)) {
        const full = path.join(dir, entry);
        if (shouldExclude(full, addonDir)) continue;

        if (fs.lstatSync(full).isDirectory()) {
          addDir(full);
        } else {
          const rel = path.relative(addonDir, full);
          archive.file(full, { name: path.join(addonName, rel) });
        }
      }
    }

    addDir(addonDir);
    archive.finalize();
  });
}

// ── Exécution ─────────────────────────────────────────────────────────────────
(async () => {
  console.log(`\n📦 Génération de ${targets.length} ZIP(s)...\n`);

  let ok = 0, fail = 0;
  const results = [];

  for (const name of targets) {
    const addonDir  = path.join(PLUGINS_DIR, name);
    const zipName   = `${name}.zip`;
    const destLocal = path.join(addonDir, zipName);         // dans le dossier addon
    const destDist  = path.join(DIST_DIR, zipName);         // dans dist/addons/

    process.stdout.write(`  ${name} … `);
    try {
      const bytes = await zipAddon(addonDir, destDist);
      // Copie aussi dans le dossier de l'addon
      fs.copyFileSync(destDist, destLocal);

      const mb = (bytes / 1024 / 1024).toFixed(2);
      console.log(`✅  ${mb} MB`);
      results.push({ name, size: mb, path: destDist });
      ok++;
    } catch (err) {
      console.log(`❌  ${err.message}`);
      fail++;
    }
  }

  console.log(`\n─────────────────────────────────────────`);
  console.log(`✅  ${ok} réussi(s)  ❌  ${fail} échec(s)`);
  console.log(`📂  dist/addons/ → ${DIST_DIR}`);
  console.log(`─────────────────────────────────────────\n`);

  if (fail > 0) process.exit(1);
})();
