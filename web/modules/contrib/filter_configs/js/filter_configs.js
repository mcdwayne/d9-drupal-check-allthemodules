/**
 * @file
 *
 * Filters out items on 'admin/config' page based on the search input provided.
 * */

(($, Drupal, debounce) => {
  Drupal.behaviors.filterConfigurations = {
    attach: function attach(context, settings) {
      const $input = $('input.config-filter-filter-text').once('config-filter-filter-text');
      const $configPanelBlocks = $('.layout-column .panel');
      let searching = false;
      let re = '';
      let matchCounts = 0;

      function getSearchableText(configItem) {
        let text = '';
        const $sources = configItem.find('.label, .description');
        $sources.each((index, source) => {
          text += `${$(source).text()} `;
        });
        return text;
      }

      function toggleConfigItemVisibility(index, item) {
        const $configItem = $(item);
        const textMatched = getSearchableText($configItem).search(re) !== -1;
        $configItem.toggle(textMatched);
        if (textMatched) {
          matchCounts += 1;
        }
      }

      function filterConfigurations(e) {
        const query = $(e.target).val();
        re = new RegExp(`\\b${query}`, 'i');
        if (query.length >= 2) {
          searching = true;
          $configPanelBlocks.each((index, panel) => {
            const $panelBlock = $(panel);
            const panelTitle = $panelBlock.find('.panel__title').text();
            // Search inside the panel li elements if it's title doesn't
            // matches with the search string.
            if (panelTitle.search(re) === -1) {
              matchCounts = 0;
              $panelBlock.find('.admin-list li').each(toggleConfigItemVisibility);
              $panelBlock.toggle(!!matchCounts);
            }
            else {
              $panelBlock.show();
            }
          });
        }
        else if (searching) {
          searching = false;
          $('ul.admin-list li').show();
          $configPanelBlocks.show();
        }
      }

      function preventEnterKey(event) {
        if (event.which === 13) {
          event.preventDefault();
          event.stopPropagation();
        }
      }

      $input.on({
        keyup: debounce(filterConfigurations, 200),
        keydown: preventEnterKey,
      });
    },
  };
})(jQuery, Drupal, Drupal.debounce);
