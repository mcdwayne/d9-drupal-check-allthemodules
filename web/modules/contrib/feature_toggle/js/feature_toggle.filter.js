
(function ($, Drupal, debounce) {
  Drupal.behaviors.featureTableFilterByText = {
    attach: function attach(context, settings) {
      var $input = $('input.table-filter-text').once('table-filter-text');
      var $table = $($input.attr('data-table'));
      var $rowsAndDetails = void 0;
      var $rows = void 0;
      var $details = void 0;
      var searching = false;

      function filterFeatureList(e) {
        var query = $(e.target).val();

        var re = new RegExp('\\b' + query, 'i');

        function showModuleRow(index, row) {
          var $row = $(row);
          var $sources = $row.find('.table-filter-text-source, .feature-name');
          var textMatch = $sources.text().search(re) !== -1;
          $row.closest('tr').toggle(textMatch);
        }

        $rowsAndDetails.show();

        if (query.length >= 2) {
          searching = true;
          $rows.each(showModuleRow);

          Drupal.announce(Drupal.t('!features features are available in the modified list.', { '!features': $rowsAndDetails.find('tbody tr:visible').length }));
        } else if (searching) {
          searching = false;
          $rowsAndDetails.show();
        }
      }

      function preventEnterKey(event) {
        if (event.which === 13) {
          event.preventDefault();
          event.stopPropagation();
        }
      }

      if ($table.length) {
        $rowsAndDetails = $table.find('tr, details');
        $rows = $table.find('tbody tr');
        $details = $rowsAndDetails.filter('.package-listing');

        $input.on({
          keyup: debounce(filterFeatureList, 200),
          keydown: preventEnterKey
        });
      }
    }
  };
})(jQuery, Drupal, Drupal.debounce);