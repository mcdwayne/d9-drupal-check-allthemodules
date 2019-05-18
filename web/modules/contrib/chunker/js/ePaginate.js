/**
 * @file
 * @fileOverview jquery plugin Elemental Paginate (ePaginate)
 *               <p>Dual License MIT and LGPL
 *               <br />Built 2015 for Drupal 'Chunker'
 *               <br />as part of the "Fantastic Semantic Markup" suite.
 *               <a href="http://pontikis.net">http://pontikis.net</a>
 *               <br />Project page <a href="https://www.drupal.org/node/2138605">https://www.drupal.org/node/2138605</a>
 *
 * @author Dan (dman) Morrison https://www.drupal.org/u/dman
 * @requires jquery
 */

/**
 * Fork of jquery plugin Elemental Paginate.
 *
 * Built upon previous work by Angel Grablev (enavu.com)
 * as the jPaginate plugin http://web.enavu.com/js/jquery/jpaginate-jquery-pagination-system-plugin/.
 *
 * Modified to:
 * - Treat the child elements as first-class countable elements (no grouping)
 * - Use data-index instead of 'title' to store step data.
 * - Support and link to anchors where available.
 * - Standardize the list item rendering (not repeated HTML strings)
 * - Introduced *partial* browser/url/hash/history support.
 *   If you want full compatibility for all browsers and their history
 *   behaviours, I guess you should add History.js add-on or something.
 *   https://github.com/cowboy/jquery-bbq
 *   Current support is only the minimum documented native stuff.
 * - Removed cookie behaviour as browser history + anchor hashes now work.
 */

/*

 To use, call .ePaginate() on the container containing elements
  to be paged through.

 ``$("#content").ePaginate();``

 You can specify the following options:

 - next_text = The text you want to have inside the text button.
   Default: 'Next'.
 - previous_text = The text you want in the previous button.
   Default: 'Previous'.
 - active_class = The class you want the active pagination link to have.
   Default: 'active'.
 - pagination_class = The class of the pagination element
   that is being generated for you to style.
   Default: 'pagination'.
 - minimize = minimizing will limit the overall number of elements
   in the pagination links.
 - nav_items = when minimize is set to true you can specify how many items
   to show. 5 will show a range of 5, so: 2 on either side of the current
   selection, flattened against the beginning or end.
 - position = The position of the pagination list,
   possible options: "before", "after", or "both".

 */
(function ($) {
  $.fn.ePaginate = function (options) {

    var defaults = {
      next_text: "Next",
      previous_text: "Previous",
      active_class: "active",
      pagination_class: "pagination",
      minimize: false,
      nav_items: 5,
      position: "after"
    };
    options = $.extend(defaults, options);
    var pagination_selector = '.' + options.pagination_class;

    return this.each(function () {
      var obj = $(this);
      var number_of_pages = obj.children().size();

      // Get handles on each item to be paginated.
      var array_of_elements = [];
      // Force the count to start from 1; Counting from 0 is undesirable.
      var count = 1;
      obj.children().each(function () {
        array_of_elements[count] = this;
        count ++;
      });

      // Monitor History state - browser forward/back.
      window.onpopstate = function (event) {
        if (window.location.hash) {
          // Hash includes the # prefix. Boring.
          var pageName = window.location.hash.replace(/^(#)/, '');
          // console.log('onpopstate', window.location.hash, pageName);.
          var pageNumber = numberFromName(pageName);
          showPageNumbered(pageNumber);
          // Do NOT re-inject this onto history this time.
          rebuildPagination(pageNumber);
        }
      };

      // Display first, preferred, or requested page.
      var pageName = getCurrentPageName();
      var pageNumber = numberFromName(pageName);
      showPageNumbered(pageNumber);
      createPagination(pageNumber);

      /**
       * Check the incoming parameters for preferred page to open.
       *
       * Environment is checked for URL hash.
       *
       * @return Page anchor ID. HTML ID string, not pager numeric.
       */
      function getCurrentPageName() {
        var pageName = window.location.hash;
        // Paranoia about XSS (baa).
        pageName = pageName.replace(/[^a-zA-Z0-9_-]/g,'');
        return pageName;
      }

      /**
       * Show selected page, by index number.
       */
      function showPageNumbered(pageNumber) {
        console.log("ePaginate: showPageNumbered", pageNumber);
        obj.children().hide();
        if (array_of_elements[pageNumber]) {
          $(array_of_elements[pageNumber]).show();
        }
      }

      /**
       * Push the numbered page into history.
       *
       * History runs on textual anchors, as they are more portable, and
       * hopefully persistent, than numerical counters.
       */
      function addToHistory(pageNumber) {
        var pageName = '';
        if (array_of_elements[pageNumber]) {
          pageName = array_of_elements[pageNumber].getAttribute('id');
        }

        // Add persistence via history or hash.
        // Browser compat. history.pushState is newer.
        if (history.pushState) {
          history.pushState(null, null, '#' + pageName);
        }
        else {
          location.hash = pageName;
        }
      }

      /**
       * The primary index for pagination is the number.
       *
       * But if we are asked for a page by name, we need a lookup.
       * Default to first page, if there is any confusion.
       *
       * @param pageName string
       *
       * @returns int
       */
      function numberFromName(pageName) {
        for (var i in array_of_elements) {
          if (array_of_elements[i].getAttribute('id') == pageName) {
            return parseInt(i);
          }
        }
        return 1;
      }

      /**
       * Remove the old nav bar, rebuild a new one.
       *
       * @param pageNumber
       */
      function rebuildPagination(pageNumber) {
        $(pagination_selector).remove();
        createPagination(pageNumber);
      }

      /**
       * Create the navigation for the pagination.
       *
       * This component does not get 'updated', it gets rebuilt from scratch
       * each time.
       * The primary keys are numeric IDs. Calculations are made from numbers.
       *
       * @param pageNumber int
       *   Pager page index number.
       */
      function createPagination(pageNumber) {
        var after = number_of_pages - options.after + 1;
        var pager_range = paginationCalculator(pageNumber);

        // Build a list of button descriptions.
        var buttons = [];

        var endbutton = {
          'href': '#',
          'data-index': '',
          'text': options.previous_text,
          'class': (pageNumber == 1) ? 'inactive' : 'js-goto_previous'
        };
        buttons[0] = endbutton;

        // Loop over all pages - adding a button for each to the pagination bar.
        // Optionally skip sections if thee are too many to show at once.
        // A flag to track if we are in a gap;.
        var skipping = false;

        for (var i in array_of_elements) {
          var element = array_of_elements[i];
          var button = {
            'href': '#' + element.getAttribute('id'),
            'data-index': i,
            'text': i,
            'class': (i == pageNumber) ? options.active_class : 'js-goto'
          };

          // Minimize option means leave gaps in the sequence.
          if (options.minimize == true) {
            // Show the first and last of the range, and n items surrounding
            // the current index.
            var endbuffer = 1;
            if (i <= endbuffer) {
              // Show the first {buffer} items.
              buttons[i] = button;
            }
            else if (i > (number_of_pages - endbuffer)) {
              // Show the last few items.
              buttons[i] = button;
            }
            else if (i >= pager_range.start && i <= pager_range.end) {
              // Mid-range.
              buttons[i] = button;
              skipping = false;
            }
            else {
              // We don't want to show these numbers.
              if (skipping) {
                continue;
              }
              // Start skipping.
              button['class'] = 'skip';
              button['text'] = "...";
              skipping = true;
              buttons[i] = button;
            }
          }
          else {
            // If not asked to minimize, just show every button.
            buttons[i] = button;
          }
        }

        endbutton = {
          'href': '#',
          'data-index': '',
          'text': options.next_text,
          'class': (pageNumber == number_of_pages) ? 'inactive' : 'js-goto_next'
        };
        buttons[i + 1] = endbutton;

        // Render the buttons.
        var items = '';
        for (var bi in buttons) {
          button = buttons[bi];
          items += '<li><a href="' + button['href'] + '"  class="' + button['class'] + '" data-index="' + button['data-index'] + '">' + button['text'] + '</a></li>';
        }

        var start = "<ul class='" + options.pagination_class + "'>";
        var end = "</ul>";
        var nav = start + items + end;

        // Place the pager inside the wrapper.
        if (options.position == "before") {
          obj.before(nav);
        }
        else if (options.position == "after") {
          obj.after(nav);
        }
        else {
          obj.after(nav);
          obj.before(nav)
        }

        // Attach click handler to the buttons we just added.
        $(".js-goto").click(function (e) {
          e.preventDefault();
          var newcurr = $(this).attr("data-index");
          showPageNumbered(newcurr);
          addToHistory(newcurr);
          rebuildPagination(newcurr);
        });
        $(".js-goto_next").click(function (e) {
          e.preventDefault();
          var activeselector = "." + options.active_class;
          var newcurr = parseInt($(pagination_selector).find(activeselector).attr("data-index")) + 1;
          showPageNumbered(newcurr);
          addToHistory(newcurr);
          rebuildPagination(newcurr);
        });
        $(".js-goto_previous").click(function (e) {
          e.preventDefault();
          var activeselector = "." + options.active_class;
          var newcurr = parseInt($(pagination_selector).find(activeselector).attr("data-index")) - 1;
          showPageNumbered(newcurr);
          addToHistory(newcurr);
          rebuildPagination(newcurr);
        });
      }

      /**
       * Calculates where in a sequence gaps need to be skipped.
       *
       * The pager should display up to nav_items buttons.
       *
       * @param pageNumber
       *   The current page in the sequence.
       *
       * @returns {{start: number, end: number}}
       */
      function paginationCalculator(pageNumber) {
        pageNumber = parseInt(pageNumber);
        var half = Math.floor(options.nav_items / 2);
        // The range surrounds the item.
        var start = pageNumber - half;
        var end = pageNumber + half;
        // Unless it's being squashed against the ends.
        if (pageNumber <= half) {
          end = options.nav_items;
        }
        else if (pageNumber > number_of_pages - half) {
          start = number_of_pages - options.nav_items + 1
        }
        return {start: start, end: end};
      }

    });

  }; // End ePaginate plugin.

})(jQuery);
