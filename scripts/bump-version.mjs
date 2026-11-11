#!/usr/bin/env node
/**
 * Bump the theme version.
 *
 *   node scripts/bump-version.mjs patch   # 1.1.3 -> 1.1.4
 *   node scripts/bump-version.mjs minor   # 1.1.3 -> 1.2.0
 *   node scripts/bump-version.mjs minor --changelog "Added X; Fixed Y"
 *
 * style.css `Version:` is the source of truth; readme.txt `Stable tag:` is
 * kept in sync. With --changelog, the semicolon-separated changes become a
 * new `= X.Y.Z =` section at the top of readme.txt's `== Changelog ==`.
 * Prints the new version on stdout (and writes old/new to $GITHUB_OUTPUT
 * when run in GitHub Actions).
 */
import { readFileSync, writeFileSync, appendFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

export const BUMP_TYPES = ['patch', 'minor'];

const VERSION_RE = /^(\s*\*?\s*Version:[ \t]*)(\S+)/m;
const STABLE_TAG_RE = /^(Stable tag:[ \t]*)(\S+)/m;

/**
 * @param {string} version e.g. "1.1.3"
 * @param {'patch'|'minor'} type
 * @returns {string}
 */
export function bumpVersion(version, type) {
  const match = /^(\d+)\.(\d+)\.(\d+)$/.exec(version);
  if (!match) {
    throw new Error(`Unsupported version "${version}" — expected MAJOR.MINOR.PATCH.`);
  }

  const [major, minor, patch] = match.slice(1).map(Number);

  switch (type) {
    case 'patch':
      return `${major}.${minor}.${patch + 1}`;
    case 'minor':
      return `${major}.${minor + 1}.0`;
    default:
      throw new Error(`Unknown bump type "${type}" — use one of: ${BUMP_TYPES.join(', ')}.`);
  }
}

/**
 * @param {string} css style.css contents
 * @returns {string}
 */
export function readThemeVersion(css) {
  const match = VERSION_RE.exec(css);
  if (!match) throw new Error('No "Version:" header found in style.css.');
  return match[2];
}

/**
 * Replace the version in style.css and the stable tag in readme.txt.
 *
 * @param {{ css: string, readme: string }} files
 * @param {string} next
 * @returns {{ css: string, readme: string }}
 */
export function applyVersion({ css, readme }, next) {
  if (!STABLE_TAG_RE.test(readme)) throw new Error('No "Stable tag:" line found in readme.txt.');

  return {
    css: css.replace(VERSION_RE, `$1${next}`),
    readme: readme.replace(STABLE_TAG_RE, `$1${next}`),
  };
}

/**
 * Turn "Added X; fixed Y ;; * Z" into ["Added X", "Fixed Y", "Z"].
 *
 * Entries are split on semicolons, trimmed, stripped of leading list
 * markers (`*`, `-`, `•`), collapsed to one line, and capitalized. Empty
 * entries are dropped.
 *
 * @param {string|undefined|null} text
 * @returns {string[]}
 */
export function parseChangelog(text) {
  if (!text) return [];

  return text
    .split(';')
    .map((entry) => entry.replace(/\s+/g, ' ').trim().replace(/^[*\-•]\s*/, '').trim())
    .filter(Boolean)
    .map((entry) => entry.charAt(0).toUpperCase() + entry.slice(1));
}

/**
 * Insert a `= version =` section at the top of readme.txt's changelog.
 *
 * @param {string} readme readme.txt contents
 * @param {string} version
 * @param {string[]} entries
 * @returns {string}
 */
export function addChangelogEntry(readme, version, entries) {
  if (entries.length === 0) return readme;

  const heading = /^== Changelog ==[ \t]*\r?\n/m.exec(readme);
  if (!heading) throw new Error('No "== Changelog ==" section found in readme.txt.');

  const escaped = version.replace(/\./g, '\\.');
  if (new RegExp(`^= ${escaped} =\\s*$`, 'm').test(readme)) {
    throw new Error(`readme.txt already has a changelog entry for ${version}.`);
  }

  const section = `= ${version} =\n${entries.map((e) => `* ${e}`).join('\n')}\n\n`;
  const insertAt = heading.index + heading[0].length;
  const rest = readme.slice(insertAt).replace(/^(\s*\r?\n)+/, '');

  return `${readme.slice(0, insertAt)}\n${section}${rest}`;
}

/**
 * @param {string[]} args argv after the script path
 * @returns {{ type: string|undefined, changelog: string }}
 */
export function parseArgs(args) {
  let type;
  let changelog = '';

  for (let i = 0; i < args.length; i++) {
    const arg = args[i];
    if (arg === '--changelog') {
      changelog = args[++i] ?? '';
    } else if (arg.startsWith('--changelog=')) {
      changelog = arg.slice('--changelog='.length);
    } else if (type === undefined) {
      type = arg;
    } else {
      throw new Error(`Unexpected argument "${arg}".`);
    }
  }

  return { type, changelog };
}

function main(argv) {
  let parsed;
  try {
    parsed = parseArgs(argv.slice(2));
  } catch (err) {
    console.error(err.message);
    process.exit(1);
  }

  const { type } = parsed;
  if (!BUMP_TYPES.includes(type)) {
    console.error(`Usage: node scripts/bump-version.mjs <${BUMP_TYPES.join('|')}> [--changelog "Change one; Change two"]`);
    process.exit(1);
  }

  const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
  const cssPath = path.join(root, 'style.css');
  const readmePath = path.join(root, 'readme.txt');

  const css = readFileSync(cssPath, 'utf8');
  const readme = readFileSync(readmePath, 'utf8');

  const current = readThemeVersion(css);
  const next = bumpVersion(current, type);
  const entries = parseChangelog(parsed.changelog);
  const updated = applyVersion({ css, readme }, next);
  updated.readme = addChangelogEntry(updated.readme, next, entries);

  writeFileSync(cssPath, updated.css);
  writeFileSync(readmePath, updated.readme);

  if (process.env.GITHUB_OUTPUT) {
    // Multiline output for the commit message body / run summary.
    const body = entries.map((e) => `* ${e}`).join('\n');
    const changelogOutput = body ? `changelog<<__ARTPRESS_EOF__\n${body}\n__ARTPRESS_EOF__\n` : 'changelog=\n';
    appendFileSync(process.env.GITHUB_OUTPUT, `old=${current}\nnew=${next}\n${changelogOutput}`);
  }

  console.error(`Bumped ${type}: ${current} -> ${next}${entries.length ? ` (${entries.length} changelog entries)` : ''}`);
  console.log(next);
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  main(process.argv);
}
