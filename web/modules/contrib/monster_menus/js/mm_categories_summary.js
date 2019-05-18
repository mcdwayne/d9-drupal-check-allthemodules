(function ($, Drupal, drupalSettings) {
  drupalSettings.MM.summaryFuncs['edit-mm-categories'] = function (context) {
    return Drupal.formatPlural($('div.mm-list,div.mm-list-selected', context).length + drupalSettings.MM.mm_categories_summary.offset, '1 page', '@count pages');
  };
})(jQuery, Drupal, drupalSettings);