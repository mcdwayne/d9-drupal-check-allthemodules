
var gulp = require('gulp');
var sass = require("gulp-sass");
var jscs = require("gulp-jscs");
var gulpFilter = require('gulp-filter');
var sourcemaps = require('gulp-sourcemaps');
var browserSync = require("browser-sync");
var reload = browserSync.reload;
var shell = require('gulp-shell');
var autoprefixer = require('autoprefixer');
var postcss = require('gulp-postcss');
var mqpacker = require('css-mqpacker');
var gulpif = require('gulp-if');
var argv = require('yargs').argv;
var nodeSassGlobbing = require('node-sass-globbing');
var cssGlobbing = require('gulp-css-globbing');
var cssMin = require('gulp-cssmin');
var uglify = require('gulp-uglify');

/**
 * @task css-stage-custom
 * Compile aggregation sass from /source to /stage.
 */
gulp.task('css-stage-custom', function () {
  return gulp.src(['source/sass-custom/*.scss'], {base: './source/sass-custom'})
    .pipe(sass({
      outputStyle: 'expanded',
      importer: nodeSassGlobbing,
      precision: 10,
      includePaths: [
        './node_modules/breakpoint-sass/stylesheets/',
        './node_modules/modularscale-sass/stylesheets/',
        './node_modules/compass-mixins/lib/'
      ],
      onError: function (err) {
        notify().write(err);
      }
    }))
  .pipe(postcss( [ autoprefixer({ browsers: ['last 2 versions', 'IE >= 10'] }), mqpacker ] ))
  .pipe(gulp.dest('stage/css-custom'));
});

/**
 * @task css-stage-contrib
 * Compile aggregation sass from /source to /stage.
 */
gulp.task('css-stage-contrib', ['css-stage-custom'],function () {
  return gulp.src(['source/sass-contrib/**/*.scss'], {base: './source/sass-contrib'})
    .pipe(sass({
      outputStyle: 'expanded',
      // importer: nodeSassGlobbing,
      precision: 10,
      includePaths: [
        './node_modules/breakpoint-sass/stylesheets/',
        './node_modules/modularscale-sass/stylesheets/',
        './node_modules/compass-mixins/lib/'
      ],
      onError: function (err) {
        notify().write(err);
      }
    }))
  .pipe(postcss( [ autoprefixer({ browsers: ['last 2 versions', 'IE >= 10'] }), mqpacker ] ))
  .pipe(gulp.dest('stage/css-contrib'));
});

/**
 * @task css-serve-custom
 * Compile css from /stage to /serve.
 */
gulp.task('css-serve-custom', ['css-stage-custom'], function() {
  return gulp.src(['stage/css-custom/**/*.css'], {base: './stage/css-custom'})
    .pipe(cssMin())
    .pipe(gulp.dest('serve/css-custom'));
});

/**
 * @task css-serve-contrib
 * Compile css from /stage to /serve.
 */
gulp.task('css-serve-contrib', ['css-stage-contrib'], function() {
  return gulp.src(['stage/css-contrib/**/*.css'], {base: './stage/css-contrib'})
    .pipe(cssMin())
    .pipe(gulp.dest('serve/css-contrib'));
});

/**
 * @task js-stage-custom
 * Compile custom js from /source to /stage.
 */
gulp.task('js-stage-custom', function () {
  return gulp.src(['source/js-custom/**/*.js'], {base: './source/js-custom'})
    .pipe(jscs({fix: true}))
    .pipe(jscs.reporter())
    .pipe(jscs.reporter('fail'))
    .pipe(gulp.dest('stage/js-custom'));
});

/**
 * @task js-stage-contrib
 * Copy contrib js from /source to /stage.
 */
gulp.task('js-stage-contrib', function () {
  return gulp.src(['source/js-contrib/**/*.js'], {base: './source/js-contrib'})
    .pipe(gulp.dest('stage/js-contrib'));
});

/**
 * @task js-serve-custom
 * Compile custom js from /stage to /serve.
 */
gulp.task('js-serve-custom', ['js-stage-custom'], function() {
  return gulp.src(['stage/js-custom/**/*.js'], {base: './stage/js-custom'})
    .pipe(jscs({fix: true}))
    .pipe(jscs.reporter())
    .pipe(jscs.reporter('fail'))
    .pipe(uglify())
    .pipe(gulp.dest('serve/js-custom'));
});

/**
 * @task js-serve-contrib
 * Copy contrib js from /stage to /serve.
 */
gulp.task('js-serve-contrib', ['js-stage-contrib'],  function () {
  return gulp.src(['stage/js-contrib/**/*.js'], {base: './stage/js-contrib'})
    .pipe(gulp.dest('serve/js-contrib'));
});

/**
 * @task watch
 * Watch sass and custom js in /source.
 */
gulp.task('default', ['css-stage-custom', 'css-serve-custom', 'css-stage-contrib', 'css-serve-contrib', 'js-stage-custom', 'js-serve-custom', 'js-stage-contrib', 'js-serve-contrib'], function () {
  gulp.watch('source/sass-*/**/*.scss', ['css-stage-custom', 'css-serve-custom', 'css-stage-contrib', 'css-serve-contrib']);
  gulp.watch('source/js-*/**/*.js', ['js-stage-custom', 'js-serve-custom', 'js-stage-contrib', 'js-serve-contrib']);
});
