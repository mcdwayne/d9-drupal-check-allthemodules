(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs = [];
  Drupal.behaviors.createPageFieldSummaries = {
    attach: function (context) {
      if (typeof $.fn.drupalSetSummary === 'function' && typeof drupalSettings.MM.summaryFuncs === 'object') {
        $('.vertical-tabs__panes>details', context).once('mmAttachSummaryFuncs').each(function () {
          var func = drupalSettings.MM.summaryFuncs[$(this).attr('id')];
          if (typeof func === 'function') {
            if ($(this).text() == '') {
              // The fieldset contains no text, but might have hidden inputs, so
              // hide it and move it outside of the list of tabs.
              $(this).closest('.vertical-tabs__panes').after($(this).hide().detach());
            }
            else {
              $(this).drupalSetSummary(func);
            }
          }
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);