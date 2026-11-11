#!/usr/bin/env node
/**
 * Bump the theme version.
 *
 *   node scripts/bump-version.mjs patch   # 1.1.3 -> 1.1.4
 *   node scripts/bump-version.mjs minor   # 1.1.3 -> 1.2.0
 *
 * style.css `Version:` is the source of truth; readme.txt `Stable tag:` is
 * kept in sync. Prints the new version on stdout (and writes old/new to
 * $GITHUB_OUTPUT when run in GitHub Actions).
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

function main(argv) {
  const type = argv[2];
  if (!BUMP_TYPES.includes(type)) {
    console.error(`Usage: node scripts/bump-version.mjs <${BUMP_TYPES.join('|')}>`);
    process.exit(1);
  }

  const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
  const cssPath = path.join(root, 'style.css');
  const readmePath = path.join(root, 'readme.txt');

  const css = readFileSync(cssPath, 'utf8');
  const readme = readFileSync(readmePath, 'utf8');

  const current = readThemeVersion(css);
  const next = bumpVersion(current, type);
  const updated = applyVersion({ css, readme }, next);

  writeFileSync(cssPath, updated.css);
  writeFileSync(readmePath, updated.readme);

  if (process.env.GITHUB_OUTPUT) {
    appendFileSync(process.env.GITHUB_OUTPUT, `old=${current}\nnew=${next}\n`);
  }

  console.error(`Bumped ${type}: ${current} -> ${next}`);
  console.log(next);
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  main(process.argv);
}
