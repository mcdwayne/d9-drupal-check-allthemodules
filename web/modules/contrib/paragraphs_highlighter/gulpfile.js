"use strict";

/************************
 * SETUP
 ************************/

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');
var livereload = require('gulp-livereload');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var source = require('vinyl-source-stream');
var plumber = require('gulp-plumber');

/************************
 * CONFIGURATION
 ************************/

var autoReload = true;

var paths = {
  npmDir: './node_modules'
};

var includePaths = [
  // Add paths to any sass @imports that you will use from node_modules here
  // paths.npmDir + '/foundation-sites/scss'
];

var stylesSrc = [
  // add any component CSS here (ie - from npm packages)
  './sass/style.scss'
];

/************************
 * TASKS
 ************************/

gulp.task('styles', function() {
  gulp.src(stylesSrc)
    .pipe(sourcemaps.init())
    .pipe(sass({
      includePaths: includePaths
    }))

    // Catch any SCSS errors and prevent them from crashing gulp
    .on('error', function (error) {
      console.error(error);
      this.emit('end');
    })
    .pipe(autoprefixer('last 2 versions', '> 1%', 'ie 11'))
    .pipe(sourcemaps.write())
    .pipe(concat('paragraphs-highlighter.css'))
    .pipe(gulp.dest('./css/'))
    .pipe(livereload());
});

gulp.task('watch', function() {
  if (autoReload) {
    livereload.listen();
  }
  gulp.watch('./sass/**/*.scss', ['styles']);
});

gulp.task('default', ['styles']);
