var stencil = require('@stencil/core/server');
var args = process.argv.slice(2);
if (args.length < 3) {
  console.error('Not enough args');
}

var data = '';

process.stdin.resume();
process.stdin.setEncoding('utf8');

process.stdin.on('data', function(chunk) {
  data += chunk;
});

process.stdin.on('end', function() {
  var config = stencil.loadConfig({
    rootDir: args[0],
    buildDir: args[1],
    namespace: args[2]
  });
  var renderer = stencil.createRenderer(config);

  renderer.hydrateToString({
    html: data
  }, function(results) {
    if (results.diagnostics.length) {
      console.error(results.diagnostics);
    }
    console.log(results.html);
  });
});
