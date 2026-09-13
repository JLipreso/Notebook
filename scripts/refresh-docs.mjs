#!/usr/bin/env node
/**
 * Regenerates the evergreen reference docs:
 *   documents/0000-00-00-000-Memory/002-Endpoints-Reference.md
 *   documents/0000-00-00-000-Memory/003-Env-Vars-Reference.md
 *
 * Run from anywhere:  node scripts/refresh-docs.mjs
 * (also exposed as the /refresh-docs skill)
 *
 * Endpoints: prefers `php artisan route:list --json` (exact, includes
 * middleware; needs PHP + vendor/). Falls back to statically parsing
 * backend/routes/api.php — reliable as long as the file follows the
 * CLAUDE.md §5 conventions (banner comments, one Route:: call per line,
 * prefix groups).
 *
 * Env vars: parses the COMMITTED .env.example templates only. Machine-local
 * .env / .env.prod files are deliberately never read, so the generated doc
 * is identical on every machine.
 *
 * Sources that don't exist yet are not an error — the script writes an
 * honest "not present yet" placeholder instead, so the file is never
 * silently stale. Nothing here commits: review the diff yourself.
 */
import { execSync } from 'node:child_process';
import { readFileSync, writeFileSync, readdirSync, existsSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const MEMORY = path.join(ROOT, 'documents', '0000-00-00-000-Memory');
const STAMP = new Date().toISOString().slice(0, 10);

const abs = (...p) => path.join(ROOT, ...p);
const header = (title, source) =>
  `# ${title}\n\n> **AUTO-GENERATED ${STAMP}** by \`scripts/refresh-docs.mjs\` from ${source} — do not edit by hand, rerun the script (or the \`/refresh-docs\` skill) instead.\n\n`;

const notYet = (title, what, next) =>
  header(title, 'nothing — the source does not exist yet') +
  `**${what}**\n\n${next}\n\nRerun \`node scripts/refresh-docs.mjs\` once it does; this placeholder will be replaced with the real table.\n`;

// ---------------------------------------------------------------- endpoints

function endpointsViaArtisan() {
  if (!existsSync(abs('backend', 'artisan')) || !existsSync(abs('backend', 'vendor'))) return null;
  try {
    const raw = execSync('php artisan route:list --json', {
      cwd: abs('backend'),
      stdio: ['ignore', 'pipe', 'ignore'],
      timeout: 60_000,
    }).toString();
    const routes = JSON.parse(raw)
      .filter((r) => r.uri.startsWith('api/') || r.uri === 'up')
      .map((r) => ({
        method: r.method.replace('|HEAD', ''),
        uri: '/' + r.uri,
        action: (r.action || '').replace('App\\Http\\Controllers\\', ''),
        middleware: (Array.isArray(r.middleware) ? r.middleware : [])
          .map((m) =>
            m
              .replace('Illuminate\\Auth\\Middleware\\Authenticate:sanctum', 'auth:sanctum')
              .replace('Illuminate\\Routing\\Middleware\\ThrottleRequests:', 'throttle:')
              .replace(/^App\\Http\\Middleware\\/, '')
          )
          .filter((m) => m !== 'api')
          .join(', '),
      }));
    return routes.length ? routes : null;
  } catch {
    return null;
  }
}

function endpointsViaStaticParse(src) {
  const out = []; // { section, method, uri, action }
  let section = 'PLATFORM';
  const prefixStack = []; // { prefix, depth }
  let depth = 0;

  const routeRe =
    /Route::(get|post|put|patch|delete|apiResource)\(\s*'([^']*)'\s*(?:,\s*(?:\[\s*)?\\?App\\Http\\Controllers\\([\w\\]+)::class(?:\s*,\s*'(\w+)')?)?/;
  const prefixRe = /Route::prefix\('([^']+)'\)/;
  // Banner comments: `// ==== NOTES (2026-09-12-002 Phase 3) ====` or `// NOTES`
  const bannerRe = /^\/\/\s*=*\s*([A-Z][A-Z0-9 &\/-]*[A-Z0-9])(?:\s*\(([^)]*)\))?\s*=*\s*$/;

  for (const line of src.split('\n')) {
    const banner = line.match(bannerRe);
    if (banner && /[A-Z]{3}/.test(banner[1])) {
      section = banner[2] ? `${banner[1].trim()} (${banner[2]})` : banner[1].trim();
    }

    const p = line.match(prefixRe);
    if (p && /->group\(\s*function\s*\(\)\s*\{/.test(line)) prefixStack.push({ prefix: p[1], depth });

    depth += (line.match(/\{/g) || []).length - (line.match(/\}/g) || []).length;
    while (prefixStack.length && depth <= prefixStack[prefixStack.length - 1].depth) prefixStack.pop();

    const m = line.match(routeRe);
    if (m) {
      const [, verb, uri, controller, method] = m;
      const prefix = prefixStack.map((s) => s.prefix).join('/');
      const full =
        ('/api/' + [prefix, uri].filter(Boolean).join('/')).replace(/\/{2,}/g, '/').replace(/(.)\/$/, '$1') || '/api';
      out.push({
        section,
        method: verb === 'apiResource' ? 'RESOURCE' : verb.toUpperCase(),
        uri: full,
        action: controller ? controller.split('\\').pop() + (method ? '@' + method : '') : '(closure)',
      });
    }
  }
  return out;
}

function writeEndpoints() {
  const target = path.join(MEMORY, '002-Endpoints-Reference.md');
  const routesFile = abs('backend', 'routes', 'api.php');
  let body;

  const artisan = endpointsViaArtisan();
  if (artisan) {
    body = header('Endpoints Reference', '`php artisan route:list --json`');
    body += '| Method | URI | Action | Middleware |\n|---|---|---|---|\n';
    for (const r of artisan) body += `| ${r.method} | \`${r.uri}\` | ${r.action} | ${r.middleware} |\n`;
    console.log(`wrote 002-Endpoints-Reference.md (${artisan.length} routes, via artisan)`);
  } else if (existsSync(routesFile)) {
    const routes = endpointsViaStaticParse(readFileSync(routesFile, 'utf8'));
    body = header(
      'Endpoints Reference',
      'a static parse of `backend/routes/api.php` (rerun with PHP + `vendor/` available for exact `route:list` output, including middleware)'
    );
    let current = '';
    for (const r of routes) {
      if (r.section !== current) {
        current = r.section;
        body += `\n## ${current}\n\n| Method | URI | Action |\n|---|---|---|\n`;
      }
      body += `| ${r.method} | \`${r.uri}\` | ${r.action} |\n`;
    }
    body +=
      '\n> `RESOURCE` rows are `Route::apiResource` (index/store/show/update/destroy). Request/response payloads are not in scope here — see the owning task folder under `documents/`.\n';
    console.log(`wrote 002-Endpoints-Reference.md (${routes.length} routes, via static parse)`);
  } else {
    body = notYet(
      'Endpoints Reference',
      'No backend yet — `backend/routes/api.php` does not exist.',
      'The Laravel install is step 4 in `.claude/memory/current-status.md`. Conventions the parser depends on are in [CLAUDE.md](../../CLAUDE.md) §5: one `Route::` call per line, section banner comments carrying the task ID.'
    );
    console.log('wrote 002-Endpoints-Reference.md (placeholder — no backend/routes/api.php)');
  }

  writeFileSync(target, body);
}

// ---------------------------------------------------------------- env vars

function envSources() {
  const sources = [];
  if (existsSync(abs('backend', '.env.example'))) {
    sources.push(['backend/.env.example', 'Backend — development defaults']);
  }
  if (existsSync(abs('apps'))) {
    // D-027 layout: apps/<role>/<form-factor>/.env.example (role folders hold no
    // app themselves); a flat apps/<name>/.env.example is still honored.
    for (const role of readdirSync(abs('apps'), { withFileTypes: true })) {
      if (!role.isDirectory()) continue;
      if (existsSync(abs('apps', role.name, '.env.example'))) {
        sources.push([
          `apps/${role.name}/.env.example`,
          `Frontend — ${role.name} (⚠ these values ship to the browser — never put a secret in a \`VITE_*\` var)`,
        ]);
        continue;
      }
      for (const form of readdirSync(abs('apps', role.name), { withFileTypes: true })) {
        if (!form.isDirectory()) continue;
        if (existsSync(abs('apps', role.name, form.name, '.env.example'))) {
          sources.push([
            `apps/${role.name}/${form.name}/.env.example`,
            `Frontend — ${role.name}/${form.name} (⚠ these values ship to the browser — never put a secret in a \`VITE_*\` var)`,
          ]);
        }
      }
    }
  }
  return sources;
}

function writeEnvVars() {
  const target = path.join(MEMORY, '003-Env-Vars-Reference.md');
  const sources = envSources();

  if (!sources.length) {
    writeFileSync(
      target,
      notYet(
        'Environment Variables Reference',
        'No `.env.example` templates exist yet (looked in `backend/` and every `apps/*`).',
        'Commit a `.env.example` alongside each app and the backend as they are scaffolded — that template, not anyone\'s local `.env`, is the source of truth this doc is built from.'
      )
    );
    console.log('wrote 003-Env-Vars-Reference.md (placeholder — no .env.example templates)');
    return;
  }

  let body = header('Environment Variables Reference', 'the committed `.env.example` files');
  body +=
    'Machine-local `.env` / `.env.prod` files are deliberately never parsed — this doc must be identical on every machine. Production values live in GitHub secrets; see [CLAUDE.md](../../CLAUDE.md) §9.\n';
  for (const [rel, title] of sources) {
    body += `\n## ${title}\n\n\`${rel}\`\n\n| Variable | Note (from file comments) |\n|---|---|\n`;
    let comment = '';
    for (const line of readFileSync(abs(rel), 'utf8').split('\n')) {
      const t = line.trim();
      if (t.startsWith('#')) {
        comment = t.replace(/^#+\s*/, '');
        continue;
      }
      const m = t.match(/^([A-Z][A-Z0-9_]*)=/);
      if (m) body += `| \`${m[1]}\` | ${comment.replace(/\|/g, '\\|')} |\n`;
      if (t === '') comment = '';
    }
  }
  writeFileSync(target, body);
  console.log(`wrote 003-Env-Vars-Reference.md (${sources.length} source file(s))`);
}

// ---------------------------------------------------------------- main

mkdirSync(MEMORY, { recursive: true });
writeEndpoints();
writeEnvVars();
console.log('done — review the diff, then commit alongside the change that made these stale.');
