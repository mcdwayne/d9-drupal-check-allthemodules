(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-appearance'] = function (context) {
    var nodesPerPage = $('select[name="nodes_per_page"]', context);
    var summary = Drupal.t('theme') + ': ' + ($('select[name="theme"]', context).val() || Drupal.t("use parent's theme")) + '<br />';
    if ($('input[name="previews"]', context).is(':checked')) {
      summary += Drupal.t('show only summaries') + '<br />';
    }
    if ($('input[name="rss"]', context).is(':checked')) {
      summary += Drupal.t('enable RSS') + '<br />';
    }
    var num = nodesPerPage.val();
    if (num === '') num = Drupal.t('inherit');
    else if (num === '0') num = Drupal.t('all');
    else if (num === '-2') num = Drupal.t('all, as needed');
    summary += Drupal.t('items per page: @num', {'@num': num});
    var hideMenuTabs = jQuery('select[name="hide_menu_tabs"]', context);
    if (hideMenuTabs.length) {
      var tabs = hideMenuTabs.val();
      if (tabs === '0') tabs = Drupal.t('show');
      else if (tabs === '1') tabs = Drupal.t('hide');
      else tabs = Drupal.t('inherit');
      summary += '<br />' + Drupal.t('hide settings tabs: @tabs', {'@tabs': tabs});
    }
    return summary;
  };
})(jQuery, Drupal, drupalSettings);