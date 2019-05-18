(function (drupalSettings) {


    function displayNbResult(nbResult) {
        return '<span><span class="result">' + nbResult + '</span> résultats pour votre recherche</span>';
    }

    function displayBody(highlightResult) {
        var maxLength = 400;
        var highlightText = '';

        var propertyPriority = ['head', 'body'];

        propertyPriority.every(function(property) {
            // Do I have a match already?
            if (highlightText !== '') {
                return true;
            }

            // The property exist?
            if (!highlightResult.hasOwnProperty(property)) {
                return true;
            }

            // Do I have a match?
            if (highlightResult[property].matchLevel !== 'full') {
                return true;
            }

            var textToSearch = highlightResult[property].value.toLowerCase();
            var searchIndex = textToSearch.indexOf('<u>');

            // Don't cut the string if < maxLength.
            if (textToSearch.length < maxLength) {
                highlightText = textToSearch;
                return true;
            }

            if (searchIndex > maxLength/2) {
                searchIndex -= maxLength/2;
            } else {
                searchIndex = 0;
            }

            var inputText = highlightResult[property].value.substr(searchIndex, maxLength);

            inputText = inputText.substr(0, Math.min(inputText.length, inputText.lastIndexOf(' ')))

            inputText = inputText.trim();
            var res = inputText.split(' ');
            res.shift();

            res.forEach(function(word) {
                highlightText += word + ' ';
            });

            highlightText = highlightText.trim();
            highlightText = '...' + highlightText + '...';

            return true;
        });

        return '<p>' + highlightText + '</p>';
    }


    function displayItem(item) {
        var createdDate = new Date(item.created * 1000);

        var options = { year: 'numeric', month: '2-digit', day: '2-digit' };
        var createdDateString = createdDate.toLocaleDateString('fr-FR', options);

        var html = '';

        html += '<div>';
        html +=   '<a href= node/' + item.nid + '>';
        if (!item.objectID.startsWith('url')){
            html +=     '<span class="search-date">  '+ createdDateString +'</span>';
        }
        html +=     item.title !== null ? '<h3>' + item._highlightResult.title.value + '</h3>' : '';
        html +=     item.body !== null ? displayBody(item._highlightResult) : '';
        html +=   '</a>';
        html += '</div>';

        return html;
    }

    // 1. Instantiate the search
    const search = instantsearch({
        appId: drupalSettings.settings.appId,
        apiKey: drupalSettings.settings.apiKey,
        indexName: drupalSettings.settings.indexName,
        urlSync: true,
        searchFunction : function(helper) {
            if (helper.state.query.length >= 3) {
                helper.search();
            }
        }
    });
    // 2. Create an interactive search box
    search.addWidget(
        instantsearch.widgets.searchBox({
            container: '#searchbox',
            placeholder: 'Search a news '
        })
    );

    // initialize hits widget
    search.addWidget(
        instantsearch.widgets.hits({
            container: '#hits',
            templates: {
                empty: displayNbResult(0),
                item: displayItem
            }
        })
    );

    // initialize pagination
    search.addWidget(
        instantsearch.widgets.pagination({
            container: '#pagination',
            maxPages: 10,
            // default is to scroll to 'body', here we disable this behavior
            scrollTo: false
        })
    );

    // 5. Start the search!
    search.start();

})(drupalSettings);