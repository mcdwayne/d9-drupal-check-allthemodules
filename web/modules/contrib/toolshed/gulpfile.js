const gulp = require('gulp');
const globBase = require('glob-base');
const autoprefixer = require('gulp-autoprefixer');
const babel = require('gulp-babel');
const minifyCss = require('gulp-clean-css');
const uglify = require('gulp-uglify');
const eslint = require('gulp-eslint');
const merge = require('merge-stream');
const postcss = require('gulp-postcss');
const reporter = require('postcss-reporter');
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const sassGlob = require('gulp-sass-glob');
const sassImporter = require('node-sass-globbing');
const sourcemaps = require('gulp-sourcemaps');
const stylelint = require('stylelint');
const scssSyntax = require('postcss-scss');
const path = require('path');
const logger = require('fancy-log');
const escapeRegex = require('escape-string-regexp');
const fs = require('fs');

const sassConfig = {
  src: 'sass/**/*.scss',
  dst: 'css',
  cwd: ['.', 'modules/toolshed_icons'],
  autoprefixer: {
    browsers: ['last 3 versions'],
    cascade: false,
  },
  compileOpts: {
    importer: sassImporter,
    includePaths: [path.join(__dirname, 'sass')],
    outputStyle: 'expanded',
    sourceComments: true,
    sourceMap: true,
    precision: 6,
  },
};

const jsConfig = {
  src: 'es6/**/*.js',
  dst: 'js',
  cwd: ['.', 'modules/toolshed_icons'],
  babel: {
    presets: ['env', 'react'],
    plugins: ['transform-object-rest-spread'],
    minified: false,
    compact: false,
  },
};

// =============================================
// Utility functions
// =============================================

/**
 * Convert a general glob string, into one aware of the current cwd.
 *
 * @param {string} src
 *   The source Glob string.
 * @param {string} cwd
 *   The current working directory path.
 *
 * @return {object}
 *   An object that contains the glob options as named values in an object.
 */
function resolveGlobOpts(src, cwd) {
  const globOpts = globBase(src);

  return {
    base: `${cwd}/${globOpts.base.replace(/^\/+|\/+$/g, '')}`,
    cwd,
  };
}

/**
 * Runs a callback (Gulp task) with a source glob, using different cwd contexts.
 *
 * @param {string[]} cwds
 *   An array of strings for each of the cwd's to apply the callback with.
 * @param {string} src
 *   The source glob string to use as the source glob.
 * @param {Function} callback
 *   The callback to apply for each of the globs with each of the cwds contexts.
 *
 * @return {Stream}
 *   Merged streams of all the Gulp task returned streams. Allows all the
 *   stream values from the applied callback functions to return a single
 *   watchable stream.
 */
function createAllCwdFunc(cwds, src, callback) {
  return () => {
    const reducer = (ds, cwd) => merge(ds, callback(src, cwd));
    return cwds.reduce(reducer, callback(src, cwds[0]));
  };
}

// =============================================
// Sass building functions
// =============================================

/**
 * Gulp task function for linting Sass files.
 *
 * @param {string} glob
 *   The string glob to use for finding files to lint.
 * @param {string} [cwd='.']
 *   The current working directory path.
 *
 * @return {Stream}
 *   A Gulp task stream to track completion of the task.
 */
function sassBuildLint(glob, cwd = '.') {
  return gulp.src(glob, resolveGlobOpts(sassConfig.src, cwd))
    .pipe(postcss(
      [
        stylelint(),
        reporter({ clearMessages: true, throwError: false }),
      ],
      { syntax: scssSyntax },
    ));
}

/**
 * Build Sass from the specified source glob and cwd context.
 *
 * @param {string} glob
 *   The string glob to use for finding the Sass files to build.
 * @param {string} [cwd='.']
 *   The current working directory path.
 *
 * @return {Stream}
 *   A Gulp task stream to track completion of the task.
 */
function sassBuildCompile(glob, cwd = '.') {
  const { compileOpts } = sassConfig;

  return gulp.src(glob, resolveGlobOpts(sassConfig.src, cwd))
    .pipe(sassGlob())
    .pipe(sass(compileOpts).on('error', sass.logError))
    .pipe(autoprefixer(sassConfig.autoprefixer))
    .pipe(gulp.dest(sassConfig.dst, { cwd }))
    .pipe(minifyCss())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(sassConfig.dst, { cwd }));
}

// =============================================
// Sass building functions
// =============================================

function jsBuildLint(glob, cwd = '.') {
  return gulp.src(glob, resolveGlobOpts(jsConfig.src, cwd))
    .pipe(eslint())
    .pipe(eslint.format());
}

function jsBuildCompile(glob, cwd = '.') {
  const { babel: babelOpts } = jsConfig;
  const renameCallback = (filepath) => {
    filepath.basename = filepath.basename.replace(/(\.es6|\.min){1,2}(\.js)?$/, '');
    filepath.extname = '.js';
  };

  return gulp.src(glob, resolveGlobOpts(jsConfig.src, cwd))
    .pipe(sourcemaps.init())
    .pipe(babel(babelOpts)).on('error', err => logger.error(err))
    .pipe(rename(renameCallback))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(jsConfig.dst, { cwd }))
    .pipe(rename({ extname: '.min.js' }))
    .pipe(uglify())
    .pipe(gulp.dest(jsConfig.dst, { cwd }));
}

// =============================================
// Gulp tasks
// =============================================

gulp.task('lint:sass', createAllCwdFunc(sassConfig.cwd, sassConfig.src, sassBuildLint));
gulp.task('compile:sass', createAllCwdFunc(sassConfig.cwd, sassConfig.src, sassBuildCompile));
gulp.task('watch:sass', () => gulp.watch(
  sassConfig.cwd.map(cwd => `${cwd}/${sassConfig.src}`),
  { ignoreInitial: false, queue: false },
  gulp.series('lint:sass', 'compile:sass'),
));

gulp.task('lint:js', createAllCwdFunc(jsConfig.cwd, jsConfig.src, jsBuildLint));
gulp.task('compile:js', createAllCwdFunc(jsConfig.cwd, jsConfig.src, jsBuildCompile));
gulp.task('watch:js', () => {
  jsConfig.cwd.forEach((cwd) => {
    const regexPrefix = new RegExp(`^${escapeRegex(cwd)}/`);
    const buildCallback = (file) => {
      const relFile = file.replace(regexPrefix, '');
      return merge(jsBuildLint(relFile, cwd), jsBuildCompile(relFile, cwd));
    };

    gulp.watch(`${cwd}/${jsConfig.src}`, { ignoreInitial: false, delay: 100 })
      .on('add', buildCallback)
      .on('change', buildCallback)
      .on('unlink', (file) => {
        const baseRegex = new RegExp(`^${escapeRegex(cwd)}/es6/`);
        const delFile = `${cwd}/${jsConfig.dst}/${file.replace(baseRegex, '').replace(/(\.es6|\.min){1,2}(\.js)?$/, '')}`;

        fs.unlink(`${delFile}.js`, () => true);
        fs.unlink(`${delFile}.min.js`, () => true);
      });
  });
});

gulp.task('default', gulp.series('lint:sass', 'compile:sass', 'lint:js', 'compile:js'));
gulp.task('build', gulp.parallel('compile:sass', 'compile:js'));
gulp.task('lint', gulp.series('lint:sass', 'lint:js'));
gulp.task('watch', gulp.parallel('watch:sass', 'watch:js'));
