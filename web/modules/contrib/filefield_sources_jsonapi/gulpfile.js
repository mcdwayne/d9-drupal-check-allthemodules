'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');
var plumber = require('gulp-plumber');
var cssmin = require('gulp-cssmin');

gulp.task('sass', function () {
  gulp.src('./scss/*.scss')
    .pipe(plumber())
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'expanded', // libsass doesn't support expanded yet
      precision: 3
    }).on('error', sass.logError))
    .pipe(autoprefixer({
      browsers: ['last 3 version']
    }))
    .pipe(sourcemaps.write('.'))
    .pipe(cssmin())
    .pipe(gulp.dest('./css'));
});

gulp.task('watch', function () {
  gulp.watch('./scss/*.scss', ['sass']);
});

gulp.task('default', ['sass', 'watch']);
