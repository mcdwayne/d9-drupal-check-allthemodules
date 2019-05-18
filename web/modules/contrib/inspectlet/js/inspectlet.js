(function ($, Drupal) {
  Drupal.behaviors.inspectlet = {
    attach: function (context, settings) {
      $('html', context).once('inspectlet').each(function () {

        let installCode = settings.inspectlet.installCode;
        $('body').prepend(installCode);

      });
    }
  };
})(jQuery, Drupal);
