/* jslint node: true */
var gulp = require('gulp');
var path = require('path');
var gutil = require('gulp-util');
var urljoin = require('url-join');
var rimraf = require('rimraf');
var rp = require('request-promise');
var critical = require('critical');
var osTmpdir = require('os-tmpdir');

var config = {
  critical: {
    width: 1280,
    height: 900,
    dest: 'css/critical/',
    urls: {
      '/': 'home',
      '/sample-article': 'article',
      '/sample-page': 'page'
    }
  }
};

var configLocal = {
  critical: {
    baseDomain: 'http://localhost/'
  }
};


// Allows invalid HTTPS certificates
process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

gulp.task('critical', ['critical:clean'], function (done) {
  'use strict';
  Object.keys(config.critical.urls).map(function (url, index) {
    var pageUrl = urljoin(configLocal.critical.baseDomain, url);
    var destCssPath = path.join(process.cwd(), config.critical.dest, config.critical.urls[url] + '.css');

    return rp({uri: pageUrl, strictSSL: false}).then(function (body) {
      var htmlString = body
        .replace(/href="\//g, 'href="' + urljoin(configLocal.critical.baseDomain, '/'))
        .replace(/src="\//g, 'src="' + urljoin(configLocal.critical.baseDomain, '/'));

      gutil.log('Generating critical css', gutil.colors.magenta(destCssPath), 'from', pageUrl);

      critical.generate({
        base: osTmpdir(),
        html: htmlString,
        src: '',
        dest: destCssPath,
        minify: true,
        width: config.critical.width,
        height: config.critical.height
      });

      if (index + 1 === Object.keys(config.critical.urls).length) {
        return done();
      }
    });


  });
});

gulp.task('critical:clean', function (done) {
  'use strict';
  return rimraf(config.critical.dest, function () {
    gutil.log('Critical directory', gutil.colors.magenta(config.critical.dest), 'deleted');
    return done();
  });
});
