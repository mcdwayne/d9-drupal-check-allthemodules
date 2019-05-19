;(function(window) {
  'use strict';
  var Twig = window.Twig;
  Twig.extend(function(Twig) {
    // Make trans tag available.
    Twig.exports.extendTag({
      type: 'trans',
      regex: /^trans$/,
      // Should be followed by endtrans.
      next: [ 'endtrans' ],
      open: true,

      // The following is based on this wiki page: https://github.com/justjohn/twig.js/wiki/Extending-twig.js-With-Custom-Tags
      // runs on matched tokens when the template is loaded. (once per template)
      compile: function (token) {
        return token;
      },

      // Runs when the template is rendered
      parse: function (token, context, chain) {
        var output = Twig.parse.apply(this, [token.output, context]);

        return {
          chain: false,
          output: output
        };
      }
    });

    // Export endtrans tag.
    Twig.exports.extendTag({
      type: 'endtrans',
      open: false,
      regex: /^endtrans$/,
      next: []
    });
  });
})(window);
