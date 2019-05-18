/**
 * @file
 * Processes links with the prefetch class.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Process links.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.prefetch_cache = {
    attach: function (context, settings) {

      $('a.prefetch-cache').once('prefetch-link').each(function () {

        var link = $(this);
        var href = link.attr('href');

        if (href.length > 0) {
          href = prepare_href_query(href);
          href += 'prefetch_cache_request=1';

          $.ajax({
            url: href
          })
            .done(function (response) {
              response = $.parseJSON(response);

              if (response.hasOwnProperty('prefetch_cache_token_id')) {
                href = link.attr('href');
                href = prepare_href_query(href);
                href += 'prefetch_cache_token_id=' + response.prefetch_cache_token_id;

                link.attr('href', href);
              }
            });
        }

      });

      function prepare_href_query(href) {
        if (href.indexOf('?') === -1) {
          href += '?';
        }
        else {
          href += '&';
        }
        return href;
      }
    }
  };

})(jQuery, Drupal);