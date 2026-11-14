#!/usr/bin/env node
/**
 * Generate info.json — the update manifest the theme's updater reads.
 *
 *   node scripts/release-info.mjs Etyamor/artpress > info.json
 *
 * The release workflow uploads it as an asset of every GitHub Release, so
 * https://github.com/<repo>/releases/latest/download/info.json always describes
 * the newest release (that URL is not rate-limited like the REST API).
 */
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { readThemeVersion } from './bump-version.mjs';

/**
 * Read a `Name: value` header from style.css / readme.txt.
 *
 * @param {string} text
 * @param {string} name
 * @returns {string}
 */
export function readHeader(text, name) {
  const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const match = new RegExp(`^[ \\t*]*${escaped}:[ \\t]*(.+?)[ \\t]*$`, 'm').exec(text);
  return match ? match[1] : '';
}

/**
 * The `* entry` lines of readme.txt's `= version =` changelog section.
 *
 * @param {string} readme
 * @param {string} version
 * @returns {string[]}
 */
export function readChangelog(readme, version) {
  const lines = readme.split(/\r?\n/);
  const start = lines.findIndex((line) => line.trim() === `= ${version} =`);
  if (start === -1) return [];

  const entries = [];
  for (const line of lines.slice(start + 1)) {
    if (/^=/.test(line.trim())) break;
    const entry = line.trim().replace(/^\*\s*/, '');
    if (line.trim().startsWith('*') && entry) entries.push(entry);
  }
  return entries;
}

/**
 * @param {{ css: string, readme: string, repo: string }} input
 * @returns {object}
 */
export function buildReleaseInfo({ css, readme, repo }) {
  if (!/^[\w.-]+\/[\w.-]+$/.test(repo)) {
    throw new Error(`Invalid repository "${repo}" — expected owner/name.`);
  }

  const version = readThemeVersion(css);
  const tag = `v${version}`;

  return {
    version,
    package: `https://github.com/${repo}/releases/download/${tag}/artpress-${version}.zip`,
    url: `https://github.com/${repo}/releases/tag/${tag}`,
    requires: readHeader(css, 'Requires at least'),
    requires_php: readHeader(css, 'Requires PHP'),
    tested: readHeader(css, 'Tested up to'),
    changelog: readChangelog(readme, version),
  };
}

function main(argv) {
  const repo = argv[2] || process.env.GITHUB_REPOSITORY;
  if (!repo) {
    console.error('Usage: node scripts/release-info.mjs <owner/repo>');
    process.exit(1);
  }

  const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
  const info = buildReleaseInfo({
    css: readFileSync(path.join(root, 'style.css'), 'utf8'),
    readme: readFileSync(path.join(root, 'readme.txt'), 'utf8'),
    repo,
  });

  console.log(JSON.stringify(info, null, 2));
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  main(process.argv);
}
