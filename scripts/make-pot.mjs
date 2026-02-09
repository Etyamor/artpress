import { WP_Pot } from 'wp-pot';

const wp = new WP_Pot({
  pot: {
    package: 'ArtPress',
    lastTranslator: 'Maksym Rutkovskyi',
  },
  php: {
    extensions: ['php'],
  },
  globOpts: {
    ignore: ['node_modules/**', 'vendor/**', 'dist/**'],
  },
});

wp.parse('**/*.php');
wp.writePot('languages/artpress.pot');

console.log('POT file generated: languages/artpress.pot');
