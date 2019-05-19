(function (Drupal, $, drupalSettings) {
    const CLASS_COLUMN = 'term-index-column';
    const CLASS_INDEX = 'term-index-index';
    const CLASS_CONTAINER = 'term-index-container';

    Drupal.behaviors.resizableIndex = {
        attach: function () {
            var indexes = $('.' + CLASS_CONTAINER);
            for (var i = 0; i < indexes.size(); i++) {
                var id = indexes[i].id;
                var index = $('#' + id);
                // Terms must be sorted.
                // JS objects do not maintain order, but in practice they do.
                // This can be a problem in some browsers.
                index.data('terms', drupalSettings.terms[id]);
                index.data('currentColumns', -1);
                index.data('showIndex', drupalSettings['show-index']);
                index.fillIndexOnResize = fillIndexOnResize;
                index.buildListToDisplay = buildListToDisplay;
                index.termsToList = termsToList;
                index.getNumberOfRows = getNumberOfRows;
                index.getColumnWidth = getColumnWidth;
                index.fillIndexOnResize();
                indexes[i] = index;
            }
            $(window).resize(function () {
                for (var i = 0; i < indexes.size(); i++) {
                    indexes[i].fillIndexOnResize();
                }
            });
        }
    };

    function fillIndexOnResize() {
        var numberOfColumns =  Math.floor(this.width() / this.getColumnWidth());
        if (numberOfColumns === this.data('currentColumns')) {
            // Don't do anything if the number of columns to display didn't change.
            return;
        }
        this.data('currentColumns', numberOfColumns);

        var terms = this.data('terms');
        var numberOfRows = this.getNumberOfRows(terms);
        var columns = calculateRowCountsOfColumns(numberOfRows, numberOfColumns);
        var listToDisplay = this.buildListToDisplay(terms, columns);
        this.html(listToDisplay);
    }

    /**
     * See "https://stackoverflow.com/questions/5841635/how-to-get-css-width-of-class".
     */
    function getColumnWidth() {
        var column = $('<ul>').addClass(CLASS_COLUMN).hide();
        this.append(column);
        var width = column.innerWidth();
        column.remove();

        return parseFloat(width);
    }

    function getNumberOfRows(terms) {
        var numberOfRows = Object.keys(terms).length;

        if (this.data('showIndex')) {
            numberOfRows += getIndexCount(terms);
        }

        return numberOfRows;
    }

    function getIndexCount(terms) {
        var indexSet = {};

        for (var term in terms) {
            indexSet[getIndex(term)] = 1;
        }

        return Object.keys(indexSet).length;
    }

    function getIndex(str) {
        if (isLetter(str.charAt(0))) {
            return str.charAt(0).toUpperCase();
        }
        else {
            return '#';
        }
    }

    function isLetter(c) {
        return c.toLowerCase() != c.toUpperCase();
    }

    function calculateRowCountsOfColumns(numberOfRows, numberOfColumns) {
        var columns = {};
        var currentColumn = 0;

        while (numberOfRows--) {
            if (columns[currentColumn] === undefined || columns[currentColumn] === null) {
                columns[currentColumn] = 0;
            }
            columns[currentColumn]++;
            currentColumn++;
            if (currentColumn >= numberOfColumns) {
                currentColumn = 0;
            }
        }

        return columns;
    }

    function buildListToDisplay(terms, columns) {
        var listToDisplay = '';
        var rows = this.termsToList(terms);

        for (var column in columns) {
            listToDisplay += '<ul class="' + CLASS_COLUMN + '">';
            for (var i = 0; i< columns[column]; i++) {
                listToDisplay += rows.shift();
            }
            listToDisplay += '</ul>';
        }

        return listToDisplay;
    }

    function termsToList(terms) {
        var list = [];
        var currentIndex = '';
        var showIndex = this.data('showIndex');

        for (var term in terms) {
            var index = getIndex(term);
            if (showIndex && index !== currentIndex) {
                list.push('<li class="' + CLASS_INDEX + '">' + index + '</li>');
                currentIndex = index;
            }
            list.push('<li><a href="' + terms[term] + '">' + term + '</a></li>');
        }

        return list;
    }
})(Drupal, jQuery, drupalSettings);
