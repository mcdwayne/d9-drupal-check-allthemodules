// @codingStandardsIgnoreFile
var gulp = require('gulp');
var sass = require('gulp-sass');
var $ = require('gulp-load-plugins')();
var csscomb = require('gulp-csscomb');
var eslint = require('gulp-eslint');
var autoprefixer = require('gulp-autoprefixer');
var merge = require('merge-stream');
var browserSync = require('browser-sync').create();
var reload = browserSync.reload;

var sassOptions = {
  outputStyle: 'expanded',
  includePaths: [
      process.cwd() + '/node_modules',
	]
};

gulp.task('sass', function () {
  return gulp.src('sass/**/*.{scss,sass}')
    .pipe($.sourcemaps.init())
    .pipe(sass(sassOptions).on('error', sass.logError))
    .pipe(autoprefixer())
    // Write sourcemaps
    .pipe($.sourcemaps.write('./'))
    .pipe(gulp.dest('css'))
    .pipe(browserSync.reload({
      stream: true
    }));
});

// gulp.task('csscomb', function () {
//   return merge();
// });

gulp.task('eslint', function () {

  return gulp.src(['js/*.js'])
    .pipe(reload({
      stream: true,
      once: true
    }))
    .pipe($.eslint())
    .pipe($.eslint.format())
    .pipe($.eslint.failAfterError());;
});

// Beautify JS
gulp.task('beautify', function() {
  gulp.src('js/*.js')
    .pipe($.beautify({indentSize: 2}))
    .pipe(gulp.dest('js'))
    .pipe($.notify({
      title: "JS Beautified",
      message: "JS files in the theme have been beautified.",
      onLast: true
    }));
});

// Run drush to clear the theme registry
gulp.task('cr', function() {
  return gulp.src('', {
    read: false
  })
    .pipe($.shell([
      'drush cr',
    ]))
    .pipe($.notify({
      title: "Drush",
      message: "Drupal CSS/JS caches rebuilt.",
      onLast: true
    }))
    .pipe(browserSync.reload({
      stream: true
    }));
});

// Run drush to clear the theme registry
gulp.task('phptask', function() {
  return gulp.src('', {
    read: false
  })
    .pipe($.notify({
      title: "PHP",
      message: "Change happened.",
      onLast: true
    }))
    .pipe(browserSync.reload({
      stream: true
    }));
});

// BrowserSync
gulp.task('browser-sync', function() {
  //watch files
  var files = [
    'css/*.css',
    'js/*.js'
  ];
  //initialize browsersync
  browserSync.init(files, {
    notify: false,
    open: false,
    proxy: "abtest.dd:8083" // BrowserSync proxy, change to match your local environment
  });
});

gulp.task('copy:js', function () {
  return NULL;
});

gulp.task('copy', ['copy:js', 'copy:css']);
gulp.task('lint', ['csscomb', 'eslint']);

gulp.task('watch', ['browser-sync', 'sass'], function () {
  // Run sass tasks hen a .scss file changes.
  gulp.watch("**/sass/*.scss", ['sass']);
  gulp.watch("**/sass/**/*.scss", ['sass']);

  //gulp.watch("**/src/**/*.php", ['phptask']);

  // Run js tasks when a .js file changes.
  gulp.watch("**/src/js/*.js", ['js']);

  // Rebuild Drupal cache when a .twig, .yml or .theme file changes.
  // gulp.watch(["**/templates/**/*.twig", "*.yml", "*.theme"], ['cr']);
});

gulp.task('compile', ['sass']);
gulp.task('default', ['compile']);
