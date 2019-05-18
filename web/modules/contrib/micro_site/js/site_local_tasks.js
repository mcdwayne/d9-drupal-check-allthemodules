/**
 * @file
 * Attaches behaviors for the micro site module.
 */
(function ($) {

  "use strict";

  $(document).ready(function() {
    $('#site-admin-toggle').on('click', function (e) {
      $(this).parent().toggleClass('visible');
    });

  });

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.site_local_tasks = {
    attach: function (context, setting) {
      // This behavior is attached twice when user-1 logged in.
      // And so the toggleClass don't work.
      // So code is run from document.ready().
      // @Todo Need investigation.
    }
  };

})(jQuery);


