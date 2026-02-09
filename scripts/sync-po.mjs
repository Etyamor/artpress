import { readFileSync, writeFileSync, readdirSync } from 'node:fs';
import gettextParser from 'gettext-parser';

const dir = 'languages';
const potData = gettextParser.po.parse(readFileSync(`${dir}/artpress.pot`));
const potTranslations = potData.translations;

const poFiles = readdirSync(dir).filter((f) => f.endsWith('.po'));

if (poFiles.length === 0) {
  console.log('No .po files found in languages/');
  process.exit(0);
}

for (const file of poFiles) {
  const poData = gettextParser.po.parse(readFileSync(`${dir}/${file}`));
  const poTranslations = poData.translations;
  const merged = {};
  let added = 0;
  let removed = 0;

  // Walk every context+msgid in the .pot template
  for (const [context, entries] of Object.entries(potTranslations)) {
    merged[context] = {};
    for (const [msgid, potEntry] of Object.entries(entries)) {
      // Empty msgid is the header — keep from .po
      if (msgid === '') {
        merged[context][msgid] = poTranslations[context]?.[msgid] || potEntry;
        continue;
      }
      const existing = poTranslations[context]?.[msgid];
      if (existing) {
        // Keep existing translation, update references from .pot
        merged[context][msgid] = {
          ...existing,
          comments: { ...existing.comments, reference: potEntry.comments?.reference },
        };
      } else {
        // New string — add with empty translation
        merged[context][msgid] = potEntry;
        added++;
      }
    }
  }

  // Count removed strings
  for (const [context, entries] of Object.entries(poTranslations)) {
    for (const msgid of Object.keys(entries)) {
      if (msgid !== '' && !potTranslations[context]?.[msgid]) {
        removed++;
      }
    }
  }

  poData.translations = merged;
  writeFileSync(`${dir}/${file}`, gettextParser.po.compile(poData));

  const parts = [];
  if (added) parts.push(`${added} added`);
  if (removed) parts.push(`${removed} removed`);
  const summary = parts.length ? ` (${parts.join(', ')})` : ' (up to date)';
  console.log(`Synced: ${dir}/${file}${summary}`);
}
