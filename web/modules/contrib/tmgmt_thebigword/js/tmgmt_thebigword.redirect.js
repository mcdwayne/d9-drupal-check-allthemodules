;(function($) {

  /**
   * Automatically submit the redirect form.
   */
  Drupal.behaviors.tmgmtThebigwordAutosubmit = {
    attach: function (context, settings) {
      $('form.tmgmt-thebigword-external-review-redirect-form', context).submit();
    }
  }
})(jQuery);
