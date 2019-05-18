# Critical CSS

Inlines a critical CSS file into a page's HTML head, and loads non-critical CSS
asynchronously using W3C Spec's preload.


## Module configuration ##
It must be enabled in /admin/config/development/performance/critical-css. 
This allows for easy enabling/disabling without uninstalling it.

## How it works ##
 * This module looks for a css file inside your theme directory.
   That css filename should match any of:
    * bundle type (i.e., "article.css")
    * entity id (i.e., "123.css")
    * url (i.e., "my-article.css")
 * If none of the previous filenames can be found, this module will search 
   for a file named "default-critical.css".
 * If any of the above paths is found, it's contents are loaded as
   a string inside a _style_ tag placed into the HTML head.
 * Any other CSS file used in the HTML head is loaded using 
   [preload](https://www.w3.org/TR/preload/). For browsers not supporting 
   this preload feature, a polyfill is provided.

## Debugging ##
When twig debug is enabled, Critical CSS will show all the possible 
file paths that is trying to find inside a css comment.

If you see ‘NONE MATCHED’, check to see if you are logged in and 
make sure Critical CSS is enabled for logged-in users.
Since the contents of the critical CSS files are generated emulating 
an anonymous visit, I recommend disabling this feature once you’ve 
finished testing.

### Gulp task for generating a css file ###
Before this module can do anything, you should generate the critical css 
of the page. That css filename should match any of:
 * bundle type (i.e., "article.css")
 * entity id (i.e., "123.css")
 * url (i.e., "my-article.css")
 
If none of the previous filenames can be found, this module will search 
for a file named "default-critical.css".
 
This can be achieved by running a Gulp task to automatically extract the 
critical css of any page.
Using Addy Osmani's [critical](https://github.com/addyosmani/critical) 
package is highly recommended.
 
Another option is Filament Group's 
[criticalCSS](https://github.com/filamentgroup/criticalCSS).
 
The extracted critical css must be saved in a directory inside the 
current theme.

 
#### Sample gulp task using Addy Osmani's critical  ####

```javascript
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


// Allow request to work with non-valid SSL certificates.
process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

gulp.task('critical', ['critical:clean'], function (done) {
  'use strict';
  Object.keys(config.critical.urls).map(function (url, index) {
    var pageUrl = urljoin(configLocal.critical.baseDomain, url);
    var destCssPath = path.join(
      process.cwd(), 
      config.critical.dest, 
      config.critical.urls[url] + '.css'
      );

    return rp({uri: pageUrl, strictSSL: false}).then(function (body) {
      var htmlString = body
        .replace(
          /href="\//g, 
          'href="' + urljoin(configLocal.critical.baseDomain, '/')
          )
        .replace(
          /src="\//g, 
          'src="' + urljoin(configLocal.critical.baseDomain, '/')
          );

      gutil.log(
        'Generating critical css', 
        gutil.colors.magenta(destCssPath), 
        'from', 
        pageUrl
        );

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
    gutil.log(
      'Critical directory', 
      gutil.colors.magenta(config.critical.dest), 
      'deleted'
      );
    return done();
  });
});

```

## Third-party libraries ##
Critical CSS uses two files from 
[Filament Group's loadCSS](https://github.com/filamentgroup/loadCSS). 
If your PHP installation has 
[allow_url_fopen()](http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen) 
enabled, they will be downloaded during the installation process, 
and be placed into public://critical_css directory 
(typically sites/default/files/critical_css). 
If allow_url_fopen() is not enabled, you should manually download 
loadCSS.min.js and cssrelpreload.min.js files from 
https://github.com/filamentgroup/loadCSS/releases/v1.3.1/
and place them into that directory.
