/**
 * @file
 * Behaviours for MMU module.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.mmu = {
    attach: function () {

      var $searchFilter = $('input.table-filter-text');
      var $table = $($searchFilter.attr('data-table'));
      var $rows = $table.find('.mmu-mix');

      var $packageFilter = $('#edit-package');
      $packageFilter.change(mix);

      var $statusFilter = $('#edit-status');
      $statusFilter.change(mix);

      var $sourceFilter = $('#edit-source');
      $sourceFilter.change(mix);

      var $sortBy = $('#edit-sort-by');
      $sortBy.change(mix);

      var $sortOrder = $('#edit-sort-order');
      $sortOrder.change(mix);

      var $summary = $('#edit-summary');

      var $totalSelected = $('#edit-total-selected');

      var mixer = mixitup('#mmu-container', {
        load: {
          filter: 'none',
          sort: getSorts()
        },
        controls: {
          enable: false
        },
        layout: {
          containerClassName: 'mmu-container-processed'
        },
        selectors: {
          target: '.mmu-mix'
        },
        callbacks: {
          onMixEnd: setState
        }
      });

      mixer.filter(getFilters());

      // Set defaults.
      $('#edit-reset').click(function () {
        $searchFilter.val('');
        $packageFilter.val('any');
        $statusFilter.val('any');
        $sourceFilter.val('any');
        $sortBy.val('name');
        $sortOrder.val('asc');
        mix();
        return false;
      });

      // Display dialog.
      $('.mmu-mix-short-description')
        .once('mmu-dialog')
        .click(function () {
          $('#' + $(this)
            .data('dialog-id'))
            .dialog({
              modal: true,
              hide: {effect: 'explode'}
            });
        });

      // Count selected.
      $rows.find('input[type=checkbox]').change(function () {
        var $this = $(this);
        $this.closest('.mmu-mix')
          .toggleClass('mmu-status-selected', $this.is(':checked'))
          .attr('data-status', 'selected');
        var totalSelected = $rows.filter('.mmu-status-selected').length;
        var totalTranslated = Drupal.formatPlural(parseInt(totalSelected), '1 module selected', '@count modules selected');
        $totalSelected.html(totalTranslated);
      });

      // Search modules.
      $searchFilter.on('keyup',  Drupal.debounce(filterModuleList, 150));
      function filterModuleList(e) {
        var query = $(e.target).val().toLowerCase();
        $rows.removeClass('mmu-text-match');

        $rows.each(function (index, row) {
          var $row = $(row);
           if ($row.data('name').toLowerCase().indexOf(query) !== -1 || $row.data('machine-name').toLowerCase().indexOf(query) !== -1) {
            $row.addClass('mmu-text-match');
          }
        });

        mix();
      }

      function mix() {
        mixer.multimix({
            filter: getFilters(),
            sort: getSorts()
          }
        );
      }

      function setState(state) {
        $.cookie('mmu-active-filter', state.activeFilter.selector);
        $.cookie('mmu-active-sort', state.activeSort.sortString);
        setSummary(state.totalShow, state.totalTargets);
      }

      function getFilters() {
        var filters = '';

        if ($searchFilter.val()) {
          filters += '.mmu-text-match';
        }

        var sourceFilter = $sourceFilter.val();
        if (sourceFilter !== 'any') {
          filters += '.mmu-source-' + sourceFilter;
        }

        var packageFilter = $packageFilter.val();
        if (packageFilter !== 'any') {
          filters += '.mmu-package-' + packageFilter;
        }

        var statusFilter = $statusFilter.val();
        if (statusFilter !== 'any') {
          filters += '.mmu-status-' + statusFilter;
        }

        if (!filters) {
          filters = '.mmu-mix';
        }

        return filters;
      }

      function getSorts() {
        return $sortBy.val() + ':' + $sortOrder.val();
      }

      function setSummary(count, total) {
        var summary = Drupal.t('Displaying @count of @total', {
          '@count': count,
          '@total': total
        });
        $summary.html(summary);
      }

    }
  };

})(jQuery, Drupal);
