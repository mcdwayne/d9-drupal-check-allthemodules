/**
 * @file
 * Inline Documents Form admin behaviors.
 */

/**
 *
 * @type {Drupal~behavior}
 *
 * @prop {Drupal~behaviorAttach} attach
 *   Edit form settings on tabledrag events.
 */
(function($, Drupal, drupalSettings) {

  // Behavior is trigger by a Drupal ajax request on each table drag events.
  Drupal.behaviors.inlineDocumentsForm = {
    attach(context) {

      // Category weight is transmitted to the ajax submit with an hidden input form field:
      var setCategoryWeight = function($element) {
        var categoryPos = $element.prevAll('.ief-row-entity').length;
        var categoryName = 'category-' + $element.attr('category-id');
        $('input[name="'+ categoryName +'"]').val(categoryPos);
      }

      // Init categories weights:
      $('.category-term').once('init-category-weights').each(function() {
        var removeButtonId = 'remove-category-' + $(this).attr('category-id');
        setCategoryWeight($(this));
        $(this).append('<td></td><td></td><td><input id = "' + removeButtonId + '" value="Retirer" class="button js-form-submit form-submit remove-category"></td>');
        $('#' + removeButtonId).click(function() {
          updateCategoriesWeights();
          $(this).parents('tbody').append($(this).parents('.category-term').hide());
        });
      });

      // Update weights on drag events:
      $('.ief-dnd-categories-form').on('mouseup pointerup', function (event) {
        updateCategoriesWeights();
      });

      // Update weights on inline entity form edits (Ajax context calls):
      if ($(context).hasClass('.ief-dnd-categories-form')) {
        updateCategoriesWeights();
      }

      if ($('.ief-entity-table').length && $('.ief-entity-table .ief-row-entity').length < 2) {
        $('.category-form-option').hide();
      }
      else {
        $('.category-form-option').show();
      }

      function updateCategoriesWeights() {
        $('.category-term').each(function() {
          setCategoryWeight($(this));
        });
      }
    }
  }
})(jQuery, Drupal, drupalSettings);
