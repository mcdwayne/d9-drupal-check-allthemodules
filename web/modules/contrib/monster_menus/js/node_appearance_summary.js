(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-mm-appearance'] = function (context) {
    var summary = [];

    var sticky = $('input[name="sticky[value]"]', context);
    if (sticky.length) {
      summary.push(sticky.is(':checked') ? Drupal.t('sticky') : Drupal.t('not sticky'));
    }

    var attribution = $('select[name="show_node_info"]', context);
    if (attribution.length) {
      summary.push(Drupal.t("attribution") + ": " + drupalSettings.MM.mmNodeInfo[attribution.val()]);
    }

    var date = $('input[name="created[0][value]"],input[name="created[0][value][date]"],input[name="created[0][value][time]"]', context);
    if (date.length) {
      summary.push(Drupal.t("authored") + ": " + date.eq(0).val() + (date.length > 1 ? ' ' + date.eq(1).val() : ''));
    }

    return summary.join('<br />');
  };
})(jQuery, Drupal, drupalSettings);