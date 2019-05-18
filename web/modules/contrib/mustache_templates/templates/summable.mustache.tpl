/**
 * @file
 * Mustache template: {{{name}}}
 */
(function (sync) {
  sync.templates.push({
    name: '{{{name}}}',
    content: '{{{content}}}'
  });
  sync.now();
}(window.mustacheSync));