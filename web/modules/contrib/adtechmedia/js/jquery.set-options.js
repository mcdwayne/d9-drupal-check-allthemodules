/* global jQuery, Drupal, drupalSettings */

(function ($, D) {

  $.fn.setOptions = function (options) {
    var $this = $(this);

    if ($this[0].tagName !== 'SELECT') {
      return;
    }

    $this.html(
      $('<option/>', {
        html: D.t('- Select -')
      })
    );

    for (var value in options) {
      if (options.hasOwnProperty(value)) {
        $this.append(
          $('<option/>', {
            value: value,
            html: options[value]
          })
        );
      }
    }
  };

})(jQuery, Drupal);
