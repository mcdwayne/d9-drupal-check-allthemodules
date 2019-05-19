/**
 * @file
 * Add a parameter to the node title link, so tha we can open right
 * filters once in the node body.
 *
 * The problem is that on the teaser listing page (landing page), when user
 * clicks on the node title and goes into the node, the left menu will
 * collapse, as a node url normally does not have information about
 * applied filters.
 * This function is used to fix this problem where the menu tree collapses
 * if you go into the node, by appending this information in the form
 * of url arguments.
 */

(function ($) {
  'use strict';
  $(function () {
    var curent_page_url = $(location).attr('href');
    var filters = curent_page_url.split('/listings/')[1];
    if(filters){
      var url_argument = '?taxonomy_facets=' + filters
      var current_url = $(".node__title a").attr("href");
      // Append argument to all node titles
      $(".node__title a").attr("href", current_url + url_argument)
    }
  });
})(jQuery);
