/* eslint no-undef: "error" */
/* eslint no-console: ["error", { allow: ["log", "warn", "error"] }] */
/* eslint no-process-exit: "off" */
/* eslint-env node */

'use strict';

try {
  var client = require('browser-refresh-client');
}
catch (ex) {
  console.error('Required node modules are not installed!');
  process.exit(1);
}

if (process.argv[2] === 'check') {
  console.log('OK!');
  process.exit(0);
}

var patterns = '*';
var styles = ['', '.css'];
var images = ['', '.png', '.jpeg', '.jpg', '.gif', '.svg'];
var others = ['', '.php', '.inc', '.module'];
var fs = require('fs');
var path = require('path');
var urlFile = false;

/* eslint block-scoped-var: "off" */
client.enableSpecialReload(patterns, {autoRefresh: false})
  .onFileModified(function (filename) {
    var ext = path.extname(filename);
    if (styles.indexOf(ext) > 0) {
      console.log('Refresh styles');
      client.refreshStyles();
    }
    else if (images.indexOf(ext) > 0) {
      console.log('Refresh images');
      client.refreshImages();
    }
    else if (others.indexOf(ext) > 0) {
      console.log('Refresh page');
      client.refreshPage();
    }
  });

if (fs.existsSync('.browser-refresh')) {
  urlFile = JSON.parse(fs.readFileSync('.browser-refresh')).urlFileName || false;
  if (urlFile) {
    fs.writeFile(urlFile, process.env.BROWSER_REFRESH_URL);
    process.send('online');
  }
}

console.error('FAILURE: You should start browser-refresh with the following Drush command:');
console.error('    drush [@alias] browser-refresh-start');
