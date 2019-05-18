var config = {
  src: path + 'assets/**/*.svg',
  dest: path + 'assets/dist/',
  settings: {
    shape: {
      dimension: { // Set maximum dimensions
        maxWidth: 50,
        maxHeight: 50
      },
      spacing: {
        padding: 0
      },
      dest: 'individual'
    },
    mode: {
      view: {
        bust: false,
        render: {
          scss: true
        }
      },
      symbol: true
    }
  }
};

// Create spriter instance (see below for `config` examples)
var spriter = new SVGSpriter(config);

// Add SVG source files — the manual way ...
spriter.add('assets/svg-1.svg', null, fs.readFileSync('assets/svg-1.svg', {encoding: 'utf-8'}));
spriter.add('assets/svg-2.svg', null, fs.readFileSync('assets/svg-2.svg', {encoding: 'utf-8'}));

// Compile the sprite
spriter.compile(function (error, result) {
  'use strict';

  /* Write `result` files to disk (or do whatever with them ...) */
  for (var mode in result) {
    for (var resource in result[mode]) {
      mkdirp.sync(path.dirname(result[mode][resource].path));
      fs.writeFileSync(result[mode][resource].path, result[mode][resource].contents);
    }
  }
});
