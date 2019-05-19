/** edit-ayout.js
 * 
 *
 */
;(function ($, Drupal, undefined) {
  Drupal.behaviors.wisskiEditLayout = {
    attach: function(context, settings) {
      // as we substitute the button labels by icons one may wonder about the functionality
      // thats why we attach a toolbox with a label hint to each button
      $('input[data-drupal-selector$="actions-ief-add"]', context).attr('title', 'Add entry');
      $('input[data-drupal-selector$="actions-ief-entity-edit"]', context).attr('title', 'Edit entry');
      $('input[data-drupal-selector$="actions-ief-entity-remove"]', context).attr('title', 'Remove entry');
      $('input[data-drupal-selector$="actions-ief-edit-save"]', context).attr('title', 'Save entry and collapse it');
      $('input[data-drupal-selector$="actions-ief-edit-cancel"]', context).attr('title', 'Cancel editing');
      $('input[data-drupal-selector$="actions-ief-add-save"]', context).attr('title', 'Save entry and collapse it');
      $('input[data-drupal-selector$="actions-ief-add-cancel"]', context).attr('title', 'Discard new entry');
    }
  };
})(jQuery, Drupal);

