import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
  bumpVersion, readThemeVersion, applyVersion, parseChangelog, addChangelogEntry, parseArgs,
} from './bump-version.mjs';

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

// ----- Changelog -----

test('parses semicolon-separated changes', () => {
  assert.deepEqual(
    parseChangelog('Added category layouts; fixed share links ;;  * Updated   translations ; - removed X;'),
    ['Added category layouts', 'Fixed share links', 'Updated translations', 'Removed X'],
  );
});

test('empty changelog input yields no entries', () => {
  assert.deepEqual(parseChangelog(''), []);
  assert.deepEqual(parseChangelog(undefined), []);
  assert.deepEqual(parseChangelog(' ; ;  '), []);
});

test('multi-line input is flattened to single-line entries', () => {
  assert.deepEqual(parseChangelog('Line one\ncontinued; two'), ['Line one continued', 'Two']);
});

const CHANGELOG_README = `=== ArtPress ===

Stable tag: 1.0.5

== Changelog ==

= 1.0.5 =
* Added translations

= 1.0.4 =
* Added progress bar

== Copyright ==
`;

test('adds a new section at the top of the changelog', () => {
  const out = addChangelogEntry(CHANGELOG_README, '1.2.0', ['Added layouts', 'Fixed links']);

  assert.equal(out, CHANGELOG_README.replace(
    '== Changelog ==\n\n= 1.0.5 =',
    '== Changelog ==\n\n= 1.2.0 =\n* Added layouts\n* Fixed links\n\n= 1.0.5 =',
  ));
});

test('no entries leaves readme untouched', () => {
  assert.equal(addChangelogEntry(CHANGELOG_README, '1.2.0', []), CHANGELOG_README);
});

test('works with an empty changelog section', () => {
  const out = addChangelogEntry('== Changelog ==\n\n== Copyright ==\n', '1.0.0', ['Initial']);
  assert.equal(out, '== Changelog ==\n\n= 1.0.0 =\n* Initial\n\n== Copyright ==\n');
});

test('refuses duplicate versions and missing section', () => {
  assert.throws(() => addChangelogEntry(CHANGELOG_README, '1.0.5', ['X']), /already has a changelog entry/);
  assert.throws(() => addChangelogEntry('=== X ===', '1.0.0', ['X']), /No "== Changelog ==" section/);
});

test('version dots are not regex wildcards in the duplicate check', () => {
  assert.doesNotThrow(() => addChangelogEntry('== Changelog ==\n\n= 1x0x5 =\n* a\n', '1.0.5', ['b']));
});

test('parses CLI args', () => {
  assert.deepEqual(parseArgs(['minor']), { type: 'minor', changelog: '' });
  assert.deepEqual(parseArgs(['patch', '--changelog', 'a; b']), { type: 'patch', changelog: 'a; b' });
  assert.deepEqual(parseArgs(['--changelog=a;b', 'minor']), { type: 'minor', changelog: 'a;b' });
  assert.throws(() => parseArgs(['patch', 'minor']), /Unexpected argument/);
});
