(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-menu'] = function (context) {
    return $('input[name="hide_menu"]:checked', context).length ? Drupal.t('hide in menu') : Drupal.t('show in menu');
  };
})(jQuery, Drupal, drupalSettings);
