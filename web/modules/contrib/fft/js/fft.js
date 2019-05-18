(function ($, Drupal, drupalSettings) {

  /**
   * fft_template Behaviors.
   */
  Drupal.behaviors.fft_template = {
    attach: function (context, settings) {
      var $fft_settings = $('textarea.fft-settings');
      $('select.fft-template:not(.fft-processed)').addClass('fft-processed').change(function(event) {
        var template = this.value;
        $fft_settings.val('');
        if (drupalSettings.fft[template] !== 'undefined'){
          $fft_settings.val(drupalSettings.fft[template]);
        }
      });

      if ($fft_settings.val() === '') {
        $('select.fft-template.fft-processed').trigger('change');
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
