// Include gulp.
var gulp = require('gulp');

// Include plugins.
var sass = require('gulp-sass');
var plumber = require('gulp-plumber');
var notify = require('gulp-notify');
var autoprefixer = require('gulp-autoprefixer');
var glob = require('gulp-sass-glob');

// Sass.
gulp.task('scss', function() {
  return gulp.src('assets/scss/*.scss')
    .pipe(glob())
    .pipe(plumber({
      errorHandler: function (error) {
        notify.onError({
          title:    "Gulp",
          subtitle: "Failure!",
          message:  "Error: <%= error.message %>",
          sound:    "Beep"
        }) (error);
        this.emit('end');
      }}))
    .pipe(sass({
      style: 'compressed',
      errLogToConsole: true
    }))
    .pipe(autoprefixer('last 2 versions', '> 1%', 'ie 9', 'ie 10'))
    .pipe(gulp.dest('assets/css'));
});

// Watch task
gulp.task('watch', ['scss'], function() {
  gulp.watch('assets/scss/**/*.scss', ['scss']);
});

// Default Task
gulp.task('default', ['watch']);
