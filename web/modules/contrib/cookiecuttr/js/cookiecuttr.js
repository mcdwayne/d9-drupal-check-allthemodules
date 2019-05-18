(function ($) {
  Drupal.behaviors.cookiePolicy = {
    attach: function (context, settings) {
      $.cookieCuttr(drupalSettings.cookieCuttr);
    }
  };
})(jQuery);