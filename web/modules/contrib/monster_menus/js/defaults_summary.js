(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-defaults'] = function (context) {
    var attribution = Drupal.t("attribution") + ": ",
      node_info = $('select[name="node_info"]', context);
    if (node_info.length && drupalSettings.MM.mmNodeInfo) {
      attribution += drupalSettings.MM.mmNodeInfo[node_info.val()];
    }
    if (drupalSettings.MM.comment_enabled) {
      var comments = Drupal.t("comments") + ": ";
      switch ($('select[name="comment"]', context).val()) {
        case '0':
          comments += Drupal.t('disabled');
          break;
        case '1':
          comments += Drupal.t('read-only');
          break;
        case '2':
          comments += Drupal.t('read/write');
          break;
        default:
          comments += Drupal.t('unknown');
          break;
      }
      return attribution + '<br />' + comments;
    }
    return attribution;
  };
})(jQuery, Drupal, drupalSettings);