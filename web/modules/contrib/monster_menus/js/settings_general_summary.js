(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-settings-general'] = function (context) {
    var pageName = $('input[name="name"]', context).val() || Drupal.t('none specified');
    var out = Drupal.t('name: @pageName', {'@pageName' : pageName}) + '<br />';

    var alias = $('input[name="alias"]', context).val() || $('.mm-alias-name', context).val();
    return out + (alias ? Drupal.t('url: :url', {':url' : alias}) : '');
  };
})(jQuery, Drupal, drupalSettings);
