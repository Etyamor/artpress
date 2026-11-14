import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readHeader, readChangelog, buildReleaseInfo } from './release-info.mjs';

const CSS = `/*
Theme Name: ArtPress
Version: 1.2.0
Requires at least: 6.1
Tested up to: 6.7
Requires PHP: 8.0
*/
`;

const README = `=== ArtPress ===

Stable tag: 1.2.0

== Changelog ==

= 1.2.0 =
* Added updater
* Fixed links

= 1.1.0 =
* Old entry

== Copyright ==
`;

test('reads headers', () => {
  assert.equal(readHeader(CSS, 'Requires at least'), '6.1');
  assert.equal(readHeader(CSS, 'Requires PHP'), '8.0');
  assert.equal(readHeader(CSS, 'Missing'), '');
});

test('reads only the changelog of the given version', () => {
  assert.deepEqual(readChangelog(README, '1.2.0'), ['Added updater', 'Fixed links']);
  assert.deepEqual(readChangelog(README, '1.1.0'), ['Old entry']);
  assert.deepEqual(readChangelog(README, '9.9.9'), []);
});

test('builds the update manifest', () => {
  assert.deepEqual(buildReleaseInfo({ css: CSS, readme: README, repo: 'Etyamor/artpress' }), {
    version: '1.2.0',
    package: 'https://github.com/Etyamor/artpress/releases/download/v1.2.0/artpress-1.2.0.zip',
    url: 'https://github.com/Etyamor/artpress/releases/tag/v1.2.0',
    requires: '6.1',
    requires_php: '8.0',
    tested: '6.7',
    changelog: ['Added updater', 'Fixed links'],
  });
});

test('rejects a malformed repository name', () => {
  assert.throws(() => buildReleaseInfo({ css: CSS, readme: README, repo: 'nope' }), /Invalid repository/);
});
