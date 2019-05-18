/**
 * @file
 * Disable Tab Click.
 */

(function ($) {
  "use strict";
  Drupal.behaviors.TabsClick = {
    attach: function (context) {
      $('.path-password-reset .tabs ul.secondary li a').removeAttr("href");
    }
  };

})(jQuery);
