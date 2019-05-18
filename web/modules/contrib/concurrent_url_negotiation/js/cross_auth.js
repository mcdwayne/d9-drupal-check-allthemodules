/**
 * @file
 * Provides cross domain authentication functionality.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  var crossAuth = Drupal.behaviors.crossAuth = {

    /**
     * Drupal attach.
     *
     * @param {HTMLDocument|HTMLElement} context
     *    The context argument.
     * @param {object} settings
     *    The settings argument.
     */
    attach: function (context, settings) {
      $(context).find('a').once('cross-checked').each(crossAuth.listenLink);
    },

    /**
     * Adds click listener to link if it leads to different domain.
     */
    listenLink: function () {
      var link = $(this);
      var domain = link.prop('hostname');

      // Only listen to links that lead to this website on other domain.
      if (
        domain !== 0 && domain !== location.host &&
        drupalSettings.crossAuth.domains.indexOf(domain) !== -1
      ) {
        link.on('click', crossAuth.alterLinkDestination);
      }
    },

    /**
     * Interrupts navigation and requires authentication token.
     *
     * @param {Event} event
     *    The click event.
     */
    alterLinkDestination: function (event) {
      // Prevent bubbling of the click event so navigation doesn't
      // happen automatically. This way we can acquire a token and
      // navigate manually afterwards.
      event.stopPropagation();
      event.preventDefault();

      var originalDestination = event.target.getAttribute('href');
      var isNewTab = event.target.getAttribute('target') === '_blank';
      jQuery.ajax({
        url: drupalSettings.crossAuth.getTokenUrl,
        dataType: 'json'
      }).done(function (resp) {
        crossAuth.crossNavigate(originalDestination, resp, isNewTab);
      });
    },

    /**
     * Goes to provided link appended with cross authentication data.
     *
     * @param {string} url
     *      The URL to navigate to.
     * @param {object} data
     *      The data that contains the token and the id of it.
     * @param {bool} isNewTab
     *      Whether it should be opened in a new tab.
     */
    crossNavigate: function (url, data, isNewTab) {
      // Append to the URL the token and id query parameters.
      url += (url.indexOf('?') === -1) ? '?' : '&';
      url += 'cross_token=' + data.token + '&cross_id=' + data.id;

      if (isNewTab) {
        window.open(url);
      }
      else {
        window.location = url;
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
