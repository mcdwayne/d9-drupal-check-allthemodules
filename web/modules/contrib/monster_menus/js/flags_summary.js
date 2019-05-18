(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-flags'] = function (context) {
    var mm_flags = [];
    $('.flag-checkbox', context).each(function(index) {
      if (this.type == 'checkbox') {
        if ($(this).is(':checked')) {
          mm_flags.push(Drupal.checkPlain($.trim($("label[for='" + $(this).attr('id') + "']").text())));
        }
      }
      else if (this.type == 'text') {
        if ($(this).val() != '') {
          mm_flags.push(Drupal.checkPlain($.trim($("label[for='" + $(this).attr('id') + "']").text()) + ": " + $(this).val()));
        }
      }
      else {
        // Revisit this, currently not dynamically adding free form flags to the summary.
        // Tough because it's a textarea. Further tough because Drupal's AJAX stuff here
        // isn't automatically called on textarea update.
        //  Drupal.getSelection($("textarea[name='free_flags']"));
      }
    });
    return mm_flags.length < 1 ? Drupal.t("none") : mm_flags.join(', ');
  };
})(jQuery, Drupal, drupalSettings);