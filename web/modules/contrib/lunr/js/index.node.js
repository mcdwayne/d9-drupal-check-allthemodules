/**
 * @file
 * Provides a dependency-free indexing script.
 */

const lunr = require('./vendor/lunr/lunr.js');
const { spawnSync } = require('child_process');
let last_response;

if (process.argv.length < 3) {
  console.log('Usage:');
  console.log('  node js/index.node.js LUNR_SEARCH_ID');
  console.log('Example:');
  console.log('  node js/index.node.js default');
  console.log('If you want to provide an alternative drush executable path, set the LUNR_DRUSH environmental variable.');
  process.exit(1);
}

const drush = process.env.LUNR_DRUSH || 'drush';

console.log(`Indexing Lunr search ${process.argv[2]}...`);

let { stdout, stderr, error } = spawnSync(drush, ['scr', 'search_settings.php', `--script-path=${__dirname}/../scripts`], { input: JSON.stringify({ id: process.argv[2] }) });
error = error || stderr.toString();
if (error) {
  console.error(error);
  process.exit(1);
}

const settings = JSON.parse(stdout.toString());

const request = (path, callback, content) => {
  const input = JSON.stringify({ path, content });
  let { stdout, stderr, error } = spawnSync(drush, ['scr', 'request.php', `--script-path=${__dirname}/../scripts`], { input: input });
  error = error || stderr.toString();
  if (error) {
    console.error(error);
    process.exit(1);
  }
  callback(JSON.parse(stdout.toString()));
};

const upload = (content, path, callback) => {
  request(path, () => {
    if (callback) {
      callback();
    }
  }, content);
};

const indexPage = (builder, path, uploadPath, displayField, usePager, page, callback) => {
  request(path + '?page=' + page, (rawData) => {
    const data = JSON.parse(rawData);
    let documentData = [];
    data.forEach((row) => {
      let documentRow = {};
      documentRow.ref = row.ref;
      documentRow[displayField] = row[displayField];
      documentData.push(documentRow);
      builder.add(row);
    });
    const json = JSON.stringify(documentData);
    console.log(`Indexed ${page} pages for ${path}.`);
    if (json === last_response) {
      console.error('Infinite paging detected!');
      process.exit(1);
      return;
    }
    last_response = json;
    if (data.length) {
      upload(json, uploadPath + '/page/' + page);
    }
    if (data.length && usePager) {
      indexPage(builder, path, uploadPath, displayField, usePager, page + 1, callback);
    }
    else {
      callback();
    }
  });
};

const indexNextPath = (paths, uploadPaths, indexFields, displayField, usePager, callback) => {
  const builder = new lunr.Builder;
  builder.pipeline.add(
    lunr.trimmer,
    lunr.stopWordFilter,
    lunr.stemmer
  );
  builder.searchPipeline.add(
    lunr.stemmer
  );
  builder.ref('ref');

  for (const field in indexFields) {
    if (indexFields.hasOwnProperty(field)) {
      builder.field(field, indexFields[field]);
    }
  }
  const path = paths.shift();
  const uploadPath = uploadPaths.shift();
  indexPage(builder, path, uploadPath, displayField, usePager, 0, () => {
    upload(JSON.stringify(builder.build()), uploadPath, () => {
      if (paths.length && uploadPaths.length) {
        indexNextPath(paths, uploadPaths, indexFields, displayField, usePager, callback);
      }
      else {
        callback();
      }
    });
  });
};

indexNextPath(settings.paths, settings.uploadPaths, settings.indexFields, settings.displayField, settings.usePager, () => {
  console.log('Finished indexing! 🎊');
});
