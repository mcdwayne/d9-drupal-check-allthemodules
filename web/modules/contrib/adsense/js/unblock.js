(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.adSenseUnblock = {
    attach: function (context, settings) {
      setTimeout(function() {
        $('.adsense').each(function() {
          var $ins = $(this).find('ins');
          if (($ins.length) && ($ins.contents().length === 0)) {
            $(this).html(Drupal.t("Please, enable ads on this site. By using ad-blocking software, you're depriving this site of revenue that is needed to keep it free and current. Thank you."));
            $(this).css({'overflow': 'hidden', 'font-size': 'smaller'});
          }
        });
        // Wait 3 seconds for adsense async to execute.
      }, 3000);
    }
  };
})(jQuery, Drupal, drupalSettings);
