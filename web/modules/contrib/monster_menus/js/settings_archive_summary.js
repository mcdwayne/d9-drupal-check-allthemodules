(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-settings-archive'] = function (context) {
    if (!$('input[name="archive"]:checked', context).length) {
      return Drupal.t('none');
    }
    return Drupal.t('archive') + ': ' +
      Drupal.t('@frequency', {'@frequency' : $('select[name="frequency"]').children('option:selected').text()}) + '<br />' +
      Drupal.formatPlural($('select[name="main_nodes"]').val(), '1 item per page', '@count items per page');
  };
})(jQuery, Drupal, drupalSettings);
