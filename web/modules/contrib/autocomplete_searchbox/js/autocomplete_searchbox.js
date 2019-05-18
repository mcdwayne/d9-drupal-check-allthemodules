(function($, Drupal) {
  Drupal.behaviors.autocomplete_searchbox = {
    attach: function($context, $settings) {

      // TODO: Find a better way to hide the search button.
      $actions = $(document).find('.autocomplete').parent().parent();
      $search_button = $($actions).find('input[type="submit"]');
      if ($search_button.val() != null) {
        if ($search_button.val().toLowerCase() == 'search') {
          $search_button.addClass('element-invisible');
        }
      }

      /**
       * Override misc/autocomplete.js
       * Puts the currently highlighted suggestion into the autocomplete field.
       */
      if (Drupal.jsAC != null) {
        Drupal.jsAC.prototype.select = function (node) {
          this.input.value = $(node).data('autocompleteValue');

          // Stop a form submit if no results are found.
          if (this.input.value != '') {
            $(this.input).trigger('autocompleteSelect', [node]);
            this.input.form.submit();
            $(this.input).css('background', 'rgba(255,255,255,0.85)').attr('readonly', 'readonly');
          }
        };
      }
    }
  };
})(jQuery, Drupal);
