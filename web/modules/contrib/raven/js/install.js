const yaml = require('js-yaml');
const fs = require('fs').promises;

const copyJs = fs.copyFile('node_modules/@sentry/browser/build/bundle.min.js', 'js/sentry-browser/bundle.min.js');
const copyMap = fs.copyFile('node_modules/@sentry/browser/build/bundle.min.js.map', 'js/sentry-browser/bundle.min.js.map');

let libraries;
const readYaml = fs.readFile('raven.libraries.yml', 'utf8')
  .then((contents) => { libraries = yaml.safeLoad(contents); });

let version;
const readJson = fs.readFile('package-lock.json', 'utf8')
  .then((contents) => { version = JSON.parse(contents).dependencies['@sentry/browser'].version; });

const updateVersion = Promise.all([readYaml, readJson])
  .then(() => { libraries['sentry-browser'].version = version; });

const writeVersion = Promise.all([updateVersion])
  .then(() => fs.writeFile('raven.libraries.yml', yaml.safeDump(libraries)));

Promise.all([copyJs, copyMap, writeVersion])
  .then(() => console.log('Achievement unlocked.'));
