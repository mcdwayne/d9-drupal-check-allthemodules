/**
 * Init selection sharer.
 *
 * @see https://drupal.stackexchange.com/q/211546
 **/

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.initSelectionSharer = {
    attach: function attach(context) {
      // For each configured selector.
      $.each(drupalSettings.selection_sharer.jquery_selectors, function(i, item){
        // Allow use selection sharer.
        $(item).selectionSharer();
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
