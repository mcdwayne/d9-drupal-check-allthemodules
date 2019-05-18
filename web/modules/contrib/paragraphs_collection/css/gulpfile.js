/**
 * @file
 * Provides Gulp configurations and tasks for compiling paragraphs_collection
 * CSS files from SASS files.
 *
 * We are mostly reusing configurations from paragraphs module, creating new
 * tasks for submodules and making sure we create nice gulp task groups so
 * developer UX is nice.
 */

'use strict';

/**
 * Helper function to add standard submodule tasks to gulp.
 */
var addModuleTasks = function(name, path, gulp, plugins, options) {
  gulp.task(name + ':sass', function() {
    return gulp.src(path + '/*.scss')
      .pipe(plugins.sass({
        outputStyle: 'expanded',
        includePaths: options.sassIncludePaths
      }))
      .pipe(plugins.postcss(options.postcssOptions))
      .pipe(gulp.dest(path));
  });

  gulp.task(name + ':sass:lint', function() {
    return gulp.src(path + '/*.scss')
      .pipe(plugins.postcss(options.processors, {syntax: plugins.syntax_scss}));
  });

  // Add name task for this submodule that runs all submodule tasks.
  gulp.task(name, [name + ':sass', name + ':sass:lint']);
};

// Load gulp and needed lower level libs.
var gulp = require('gulp'),
  yaml   = require('js-yaml'),
  fs     = require('fs');

// Load gulp options first from this module.
// @note - Be sure to define proper paragraphsPath relative path first in
// gulp-options.yml. Most of the time default provided path is OK.
var options = yaml.safeLoad(fs.readFileSync('./gulp-options.yml', 'utf8'));

// Merge with gulp options from paragraphs base module.
var paragraphsOptions = yaml.safeLoad(fs.readFileSync(options.paragraphsPath + '/gulp-options.yml', 'utf8'));
options = Object.assign({}, paragraphsOptions, options);

// Lazy load the rest of gulp plugins.
var plugins = require('gulp-load-plugins')(options.gulpLoadPlugins);

// Load default gulp tasks from paragraphs base module.
require(options.paragraphsPath + '/gulp-tasks.js')(gulp, plugins, options);

// Keep a track of all tasks in separated group context.
var allTasks = new Map();
allTasks.set('sass', []);
allTasks.set('sass:lint', []);

// Create tasks for all submodules.
for (var name in options.submodules) {
  addModuleTasks(name, options.submodules[name], gulp, plugins, options);
  // Add task names to all tasks so we can create defaults later.
  allTasks.get('sass').push(name + ':sass');
  allTasks.get('sass:lint').push(name + ':sass:lint');
}

// Override default tasks.
gulp.task('sass', allTasks.get('sass'));
gulp.task('sass:lint', allTasks.get('sass:lint'));
