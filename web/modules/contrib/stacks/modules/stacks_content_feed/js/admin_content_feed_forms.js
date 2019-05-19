(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.stacks_content_feed = {
    attach: function (context, settings) {
      // Content feed preview on edit mode first page hit.
      $('select[data-drupal-selector="edit-inline-entity-form-field-cfeed-content-types"]').once('preview').each(function () {
        $(this).trigger('change');
      });

      // Onload functionality for front-end editor
      if ($('select[data-drupal-selector="edit-field-cfeed-content-types"]').length && !$('#contentfeed-grid-content-preview div').length) {
        $('select[data-drupal-selector="edit-field-cfeed-content-types"]').once('preview').each(function () {
          $(this).trigger('change');
        });
      }

      // Show/Hide Taxonomy Vocabulary select when that terms filer is selected.
      $('input[data-drupal-selector="edit-inline-entity-form-field-cfeed-enable-filtering-taxonomy-terms"]').once('filter_terms').on('change', function () {
        var $filter_by_vocab = $('#filter_by_vocab');
        if ($(this).is(':checked')) {
          $filter_by_vocab.show();
        } else {
          $filter_by_vocab.hide();
          $('select[data-drupal-selector="edit-inline-entity-form-field-cfeed-vocabulary"]').val("_none").trigger('chosen:updated');
        }
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
