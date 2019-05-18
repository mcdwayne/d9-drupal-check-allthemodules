(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-settings-perms'] = function (context) {
    var summary = [];
    var owner = Drupal.t('owner:') + ' ' + $('.settings-perms-owner-name', context).text();

    var check = $('input[name=node-everyone]', context);
    if (check.is(':checked')) {
      summary.push(Drupal.t('everyone'));
      summary.push(owner);
    }
    else {
      summary.push(owner);
      summary.push(Drupal.formatPlural($('.mm-permissions-data-row :hidden+.form-type-item', context).length, '1 individual', '@count individuals'));
      summary.push(Drupal.formatPlural($('.mm-permissions-data-row details', context).length, '1 group', '@count groups'));
    }

    return summary.join('<br />');
  };
})(jQuery, Drupal, drupalSettings);