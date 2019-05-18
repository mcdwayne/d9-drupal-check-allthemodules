var CludoSearch;

(function ($, Drupal, drupalSettings) {
Drupal.behaviors.CludoSearchBehavior = {
  attach: function (context, settings) {
    // can access setting from 'drupalSettings';
    var cludoSettings = {
        customerId: drupalSettings.cludo_search.cludo_searchJS.customerId,
        engineId: drupalSettings.cludo_search.cludo_searchJS.engineId,
        searchUrl: drupalSettings.cludo_search.cludo_searchJS.searchUrl,
        disableAutocomplete: drupalSettings.cludo_search.cludo_searchJS.disableAutocomplete,
        hideResultsCount: drupalSettings.cludo_search.cludo_searchJS.hideResultsCount,
        hideSearchDidYouMean: drupalSettings.cludo_search.cludo_searchJS.hideSearchDidYouMean,
        hideSearchFilters: drupalSettings.cludo_search.cludo_searchJS.hideSearchFilters,
        language: 'en',
        searchInputs: ["cludo-search-block-form","cludo-search-search-form"],
        type: 'inline'
      };
    CludoSearch = new Cludo(cludoSettings);
    CludoSearch.init();
  }
};
})(jQuery, Drupal, drupalSettings);
