import { readFileSync, writeFileSync, readdirSync } from 'node:fs';
import gettextParser from 'gettext-parser';

const dir = 'languages';
const poFiles = readdirSync(dir).filter((f) => f.endsWith('.po'));

if (poFiles.length === 0) {
  console.log('No .po files found in languages/');
  process.exit(0);
}

for (const file of poFiles) {
  const input = readFileSync(`${dir}/${file}`);
  const po = gettextParser.po.parse(input);
  const mo = gettextParser.mo.compile(po);
  const moFile = file.replace(/\.po$/, '.mo');
  writeFileSync(`${dir}/${moFile}`, mo);
  console.log(`Compiled: ${dir}/${moFile}`);
}
