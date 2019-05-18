/**
 * @file
 * JavaScript file for the Druqs module.
 *
 * TODO remove jQuery dependency?
 */

(function ($, Drupal) {
  'use strict';

  var scheduled;
  var results;

  /**
   * Decorate the druqs element
   */
  Drupal.behaviors.druqs = {
    attach: function (context, settings) {

      // Only fire this once.
      if (this.processed) {
        return;
      }
      this.processed = true;

      // Attach code check handlers
      $('#druqs-input').keyup(function () {

        // We only continue with actual input
        if (!$('#druqs-input').val().length) {
          return;
        }

        results = false;

        // Hide results while we're typing
        $('#druqs-results').removeClass('active');

        // Clear any previously scheduled requests
        clearTimeout(scheduled);

        // Schedule a new request in 200ms
        scheduled = setTimeout(function () {
          request();
        }, 200);
      });

      // Submitting the form will automatically clear the automatic query
      // schedule and will trigger an immediate query
      $('#druqs').submit(function () {
        clearTimeout(scheduled);
        request();
        return false;
      });

      // Clicks outside the results hide them
      $('body').click(function () {
        $('#druqs-results').removeClass('active');
      });

      // On focusing the input again, we show the results we have, so people can
      // get back to the results once they've clicked outside of them
      $('#druqs-input').click(function (e) {
        if (results) {
          $('#druqs-results').addClass('active');
          e.stopPropagation();
        }
      });

    }
  };

  /**
   * Sends a search request to the server
   */
  function request() {

    var search = $('#druqs-input').val();

    if (search.length) {
      // Show results and add throbber.
      var results = document.querySelector('#druqs-results');
      results.innerHTML = '<div class="druqs-throbber"></div>';
      results.classList.add('active');
      // Send ajax request.
      var ajax = new XMLHttpRequest();
      ajax.onreadystatechange = function () {
        if (ajax.readyState === XMLHttpRequest.DONE) {
          if (ajax.status === 200) {
            decorate(ajax.response);
          }
        }
      };
      ajax.open('POST', '/admin/druqs/search', true);
      ajax.setRequestHeader(
        'Content-type',
        'application/x-www-form-urlencoded'
      );
      ajax.send('query=' + encodeURIComponent(search));
    }
  }

  /**
   * Helper function to decorate the druqs results
   * @param json data
   *   A json formatted response from the server
   */
  function decorate(data) {
    var html = '';
    var results = JSON.parse(data);
    if (results.length) {
      var r;
      var action;
      for (r = 0; r < results.length; r++) {
        html += '<div class="druqs-result">';
        html += '<div class="druqs-result-content">';
        html += '<div class="druqs-result-type">' + results[r].type + '</div>';
        html += results[r].title;
        html += '</div>';
        html += '<div class="druqs-result-actions">';
        if (results[r].actions) {
          for (action in results[r].actions) {
            if (results[r].actions.hasOwnProperty(action)) {
              html += '<a href="' + results[r].actions[action] + '">' + action + '</a>';
            }
          }
        }
        html += '</div>';
        html += '</div>';
      }
    }
    else {
      html = '<div class="nope"><span>:-(</span>This search yielded no results.</div>';
    }
    document.querySelector('#druqs-results').innerHTML = html;
  }

  /**
   * Adds keyboard shortcut (ALT+S) to focus the search field
   */
  $(document).keydown(function (e) {
    if (e.altKey === true && e.keyCode === 83) {
      $('#druqs-input').val('').focus();
      event.preventDefault();
    }
  });

})(jQuery, Drupal);
