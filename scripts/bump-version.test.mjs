import { test } from 'node:test';
import assert from 'node:assert/strict';
import { bumpVersion, readThemeVersion, applyVersion } from './bump-version.mjs';

test('patch bumps the third digit', () => {
  assert.equal(bumpVersion('1.1.3', 'patch'), '1.1.4');
  assert.equal(bumpVersion('1.1.9', 'patch'), '1.1.10');
  assert.equal(bumpVersion('0.0.0', 'patch'), '0.0.1');
});

test('minor bumps the second digit and resets the third', () => {
  assert.equal(bumpVersion('1.1.3', 'minor'), '1.2.0');
  assert.equal(bumpVersion('1.9.12', 'minor'), '1.10.0');
  assert.equal(bumpVersion('2.0.0', 'minor'), '2.1.0');
});

test('rejects malformed versions and unknown bump types', () => {
  assert.throws(() => bumpVersion('1.1', 'patch'), /Unsupported version/);
  assert.throws(() => bumpVersion('1.1.3-beta', 'patch'), /Unsupported version/);
  assert.throws(() => bumpVersion('1.1.3', 'major'), /Unknown bump type/);
});

const CSS = `/*
Theme Name: ArtPress
Version: 1.1.3
Requires at least: 6.1
Requires PHP: 8.0
*/
`;

const README = `=== ArtPress ===

Requires at least: 6.1
Stable tag: 1.0.5
License: GPL
`;

test('reads the theme version from style.css', () => {
  assert.equal(readThemeVersion(CSS), '1.1.3');
  assert.throws(() => readThemeVersion('/* Theme Name: X */'), /No "Version:"/);
});

test('only the version header and stable tag change', () => {
  const out = applyVersion({ css: CSS, readme: README }, '1.2.0');

  assert.equal(out.css, CSS.replace('Version: 1.1.3', 'Version: 1.2.0'));
  assert.equal(out.readme, README.replace('Stable tag: 1.0.5', 'Stable tag: 1.2.0'));
  assert.match(out.css, /Requires at least: 6\.1/);
});

test('fails when readme.txt has no stable tag', () => {
  assert.throws(() => applyVersion({ css: CSS, readme: '=== X ===' }, '1.2.0'), /Stable tag/);
});
