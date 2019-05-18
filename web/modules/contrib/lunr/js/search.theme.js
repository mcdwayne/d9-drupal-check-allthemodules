/**
 * @file
 * Provides theme functions for Lunr search pages.
 */

(function(Drupal) {

  /**
   * Theme function for the progress element, which is appended to the body.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.lunrSearchProgress = function(settings) {
    return '<div class="ajax-progress ajax-progress-fullscreen">&nbsp;</div>';
  };

  /**
   * Theme function to inform the user of how many search results there are.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} settings.count
   *   The number of search results.
   * @param {string} settings.page
   *   The current page (starting from 0).
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.lunrSearchResultCount = function(settings) {
    if (settings.count) {
      return '<p class="lunr-search-results-count">' + Drupal.formatPlural(settings.count, 'Found one result', 'Page @page of @count results', {
        '@count': settings.count,
        '@page': settings.page + 1
      }) + '</p>';
    }
    return '<p class="lunr-search-results-count">' + Drupal.t('No results found', {
      '@count': settings.count
    }) + '</p>';
  };

  /**
   * Theme function for the wrapper around search result HTML.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.lunrSearchResultWrapper = function(settings) {
    return '<div class="lunr-search-result-row"></div>';
  };

  /**
   * Theme function for the search pager.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} settings.count
   *   The number of search results.
   * @param {string} settings.max
   *   The current page (starting from 0).
   * @param {string} settings.resultsPerPage
   *   The number of search results per page.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.lunrSearchPager = function(settings) {
    var pager = '<div class="lunr-search-pager">';
    var max = (settings.count / settings.resultsPerPage);
    var start = (settings.page - 5) > 0 ? settings.page - 5 : 0;
    var end = settings.page + 5;
    if (end < Math.abs(10 - start)) {
      end = Math.abs(10 - start);
    }
    if (end > max) {
      end = (max <= 1) ? 0 : max;
    }
    if (settings.page !== 0 && settings.page <= max) {
      pager += '<a href="" data-page="0">' + Drupal.t('« First') + '</a>';
      pager += '<a href="" data-page="' + (settings.page - 1) + '">' + Drupal.t('‹ Previous') + '</a>';
    }
    for (var i = start; i < end; ++i) {
      pager += '<a href="" data-page="' + i + '"';
      if (i === settings.page) {
        pager += ' class="active"';
      }
      pager += '>' + (i + 1) + '</a>';
    }
    if (max > 1 && settings.page < Math.ceil(max - 1)) {
      pager += '<a href="" data-page="' + (settings.page + 1) + '">' + Drupal.t('Next ›') + '</a>';
      pager += '<a href="" data-page="' + Math.ceil(max - 1) + '">' + Drupal.t('Last »') + '</a>';
    }
    return pager;
  };

})(Drupal);
