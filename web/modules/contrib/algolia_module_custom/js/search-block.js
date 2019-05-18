(function($) {

  Drupal.behaviors.algolia_search_custom_block = {
    attach: function(context, settings) {

      var client = algoliasearch(settings.settings.appId, settings.settings.apiKey)
      var index = client.initIndex(settings.settings.indexName);

      function suggestionTemplate(suggestion) {
        var html = '';

        html += '<div>';
        html +=   '<a href='+ suggestion.url +'>';
        html +=     suggestion._highlightResult.title.value;
        html +=   '</a>';
        html += '</div>';

        return html;
      }

      var search = autocomplete('#js-algolia-search', { hint: false }, [
      {
        source: autocomplete.sources.hits(index, { hitsPerPage: 6 }),
        displayKey: 'title',
        templates: {
          suggestion: suggestionTemplate,
          footer : '<a href="/recherche" class="btn btn--white" id="js-showresult">VOIR TOUS LES RÉSULTATS</a>'
        }
      }
      ]).on('autocomplete:updated', function(event, suggestion, dataset) {
        var val = search.autocomplete.getVal();
        $('#js-showresult').attr('href', '/recherche?q=' + val);

        if (val.length === 0) {
          $('#js-btn-box').removeClass('hidden');
        } else {
          $('#js-btn-box').addClass('hidden');
        }
      }).on('autocomplete:opened', function(event, suggestion, dataset) {
        $('#js-btn-box').addClass('hidden');
      }).on('autocomplete:closed', function(event, suggestion, dataset) {
        $('#js-btn-box').removeClass('hidden');
      });
    }
  }
})(jQuery)
