/* SVG task */

/**
 * plugins
 */
var gulp = require('gulp');
var svgSprite = require('gulp-svg-sprite');
var sass = require('gulp-sass');
var svgmin = require('gulp-svgmin');

/**
 * configfile
 */

var config = {
  svg: {
    src: 'assets/src/icons/*.svg',
    dest: 'assets/dist/',
    settings: {
      shape: {
        dimension: { // Set maximum dimensions
          maxWidth: 30,
          maxHeight: 30
        },
        spacing: {
          padding: 0
        }
      },
      mode: {
        stack: {
          dest: '',
          sprite: 'sprites/admin-tabs.svg'
        }
      }
    }
  },
  sass: {
    src: 'assets/src/scss/admin-tabs.scss',
    dest: 'assets/dist/css',
    outputStyle: 'expanded'
  }

};

/**
 * Tasks
 */
gulp.task('svg-sprite', function () {
  'use strict';
  gulp.src(config.svg.src)
    .pipe(svgSprite(config.svg.settings))
    .pipe(gulp.dest(config.svg.dest));
});

gulp.task('sass', function () {
  'use strict';
  return gulp.src(config.sass.src)
    .pipe(sass({outputStyle: config.sass.outputStyle}).on('error', sass.logError))
    .pipe(gulp.dest(config.sass.dest));
});

gulp.task('svg-min', function () {
  'use strict';
  return gulp.src(config.svg.src)
      .pipe(svgmin())
      .pipe(gulp.dest('assets/dist/svg'));
});
