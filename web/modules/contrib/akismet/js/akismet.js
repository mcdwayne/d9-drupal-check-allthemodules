(function ($) {
  'use strict';

  Drupal.akismet = Drupal.akismet || {};

/**
 * Open links to Akismet.com in a new window.
 *
 * Required for valid XHTML Strict markup.
 */
  Drupal.behaviors.akismetTarget = {
  attach: function (context) {
    $(context).find('.akismet-target').click(function () {
      this.target = '_blank';
    });
  }
};

})(jQuery);
