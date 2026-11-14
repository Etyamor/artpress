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
  // Plural strings need one msgstr per plural form of this language
  // (e.g. 3 for Ukrainian), not the 2 the .pot template carries.
  const nplurals = Number(/nplurals\s*=\s*(\d+)/.exec(poData.headers?.['Plural-Forms'] ?? '')?.[1] ?? 2);
  const pluralSlots = (entry) => (entry.msgid_plural
    ? { ...entry, msgstr: Array.from({ length: nplurals }, (_, i) => entry.msgstr?.[i] ?? '') }
    : entry);
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
        merged[context][msgid] = pluralSlots({
          ...existing,
          comments: { ...existing.comments, reference: potEntry.comments?.reference },
        });
      } else {
        // New string — add with empty translation
        merged[context][msgid] = pluralSlots(potEntry);
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
