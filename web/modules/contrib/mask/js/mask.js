(function ($, Drupal, drupalSettings) {

  // Disables masking by data-mask attribute.
  $.jMaskGlobals.dataMask = false;

  // Use modules settings for digit translation.
  $.jMaskGlobals.translation = {};
  for (var symbol in drupalSettings.mask.translation) {
    var options = drupalSettings.mask.translation[symbol];
    options.pattern = new RegExp(options.pattern);
    $.jMaskGlobals.translation[symbol] = options;
  }

  Drupal.behaviors.mask = {
    attach: function (context, settings) {
      // Applies mask to fields.
      $('*[data-mask-value]', context).once('mask').each(function () {
        var $this = $(this);

        // Gets mask options.
        var maskValue = $this.attr('data-mask-value');
        var maskOptions = {
          reverse: $this.attr('data-mask-reverse') === 'true',
          clearIfNotMatch: $this.attr('data-mask-clearifnotmatch') === 'true',
          selectOnFocus: $this.attr('data-mask-selectonfocus') === 'true',
          translation: drupalSettings.mask.translation
        };

        // Applies the mask.
        $this.mask(maskValue, maskOptions);
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
