/**
 * Gulp task to convert the status-messages.scss into status-messages.css.
 */

/**
 * plugins
 */
var gulp = require('gulp');
var sass = require('gulp-sass');

/**
 * configfile
 */

var config = {
  sass: {
    src: 'css/better-status-messages.scss',
    dest: 'css',
    outputStyle: 'expanded'
  }

};

/**
 * Tasks
 */
gulp.task('sass', function () {
  'use strict';
  return gulp.src(config.sass.src)
    .pipe(sass({outputStyle: config.sass.outputStyle}).on('error', sass.logError))
    .pipe(gulp.dest(config.sass.dest));
});
