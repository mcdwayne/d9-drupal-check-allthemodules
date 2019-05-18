(function ($, Drupal) {

  /**
   * Behavior for disallowing non-latin chars.
   */
  Drupal.behaviors.checkboxesFilter = {
    timer: null,
    attach: function (context, settings) {
      var $checkboxesElement = $('.checkboxes-filter-element');
      var currentElement = $(this);

      $checkboxesElement.once('checkboxes-filter').each(function() {
        $(this).prepend(
          $('<input type="text" class="form-text" placeholder="' + Drupal.t('Filter') + '">').on('blur', function () {
            var $this = $(this);
            if (Drupal.behaviors.checkboxesFilter.timer) {
              clearTimeout(Drupal.behaviors.checkboxesFilter.timer);
            }

            Drupal.behaviors.checkboxesFilter.timer = setTimeout(function () {
              Drupal.behaviors.checkboxesFilter.filter($this.val(), currentElement)
            }, 100)
          }).on('keyup', function () {
            if (Drupal.behaviors.checkboxesFilter.timer) {
              clearTimeout(Drupal.behaviors.checkboxesFilter.timer);
            }

            Drupal.behaviors.checkboxesFilter.timer = setTimeout(function () {
              Drupal.behaviors.checkboxesFilter.filter($(this).val(), currentElement)
            }, 100)
          })
        );
      });
    },

    filter: function (filterText, $element) {
      var checkboxes = $('div.form-item.form-type-checkbox', $element).addClass('hidden');

      checkboxes.filter(function () {
        if (filterText === '') {
          return true;
        }

        var element = $(this);
        var label = element.find('label');
        var labelText = label.html();

        var result = labelText.toUpperCase().indexOf(filterText.toUpperCase()) !== -1;
        if (result === false) {
          $element.closest('div.form-item.form-type-checkbox').addClass('hidden');
        }

        return result;
      }).removeClass('hidden');
    }
  };

})(jQuery, Drupal);
