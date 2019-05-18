/**
 * @file
 * Defines Javascript behaviors for the css debuger module
 */

(function($, Drupal, drupalSettings) {
  Drupal.behaviors.css_debugger = {
    attach: function attach() {
      const colorize = function(a) {
        [].forEach.call(document.querySelectorAll(a), b => {
          b.style.outline = `1px solid #${(~~(
            Math.random() *
            (1 << 24)
          )).toString(16)}`;
        });
      };

      const decolorize = function(a) {
        [].forEach.call(document.querySelectorAll(a), b => {
          b.style.outline = "none";
        });
      };

      let status = false;
      $(document).keydown(event => {
        if (
          event.altKey === true &&
          (event.keyCode === 67 || event.keyCode === 207)
        ) {
          if (status === false) {
            colorize("*");
            status = true;
          } else if (status) {
            decolorize("*");
            status = false;
          }
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
