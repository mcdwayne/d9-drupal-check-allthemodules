/* SVG task */

/**
 * plugins
 */
var gulp = require('gulp');
var svgSprite = require('gulp-svg-sprite');

/**
 * configfile
 */

var config = {
  iconset1: {
    src: 'assets/src/icons--square/*.svg',
    dest: 'assets/dist/',
    settings: {
      shape: {
        dimension: { // Set maximum dimensions
          maxWidth: 50,
          maxHeight: 50
        },
        spacing: {
          padding: 0
        }
      },
      mode: {
        symbol: {
          dest: '',
          sprite: 'sprites/social-icons--square.svg'
        }
      }
    }
  },

  iconset2: {
    src: 'assets/src/icons--no-color/*.svg',
    dest: 'assets/dist/',
    settings: {
      shape: {
        dimension: { // Set maximum dimensions
          maxWidth: 50,
          maxHeight: 50
        },
        spacing: {
          padding: 0
        }
      },
      mode: {
        symbol: {
          dest: '',
          sprite: 'sprites/social-icons--no-color.svg'
        }
      }
    }
  }

};

/**
 * Tasks
 */
gulp.task('svg-sprite', function () {
  'use strict';
  gulp.src(config.iconset1.src)
    .pipe(svgSprite(config.iconset1.settings))
    .pipe(gulp.dest(config.iconset1.dest));
  gulp.src(config.iconset2.src)
    .pipe(svgSprite(config.iconset2.settings))
    .pipe(gulp.dest(config.iconset2.dest));
});
