/**
 * @file
 *
 * MerlinOne Entity Browser integration
 */
(function ($, Drupal) {
  'use strict';

  function getHost(url) {
    var l = document.createElement('a');
    l.href = url;
    return l.hostname;
  }

  Drupal.behaviors.merlinOneEntityBrowserSearch = {
    attach: function (context, settings) {

      // Search frame
      var $searchFrame = $(context).find('.merlinone-search-iframe').once('merlinOneEntityBrowserSearch');
      if ($searchFrame.length) {
        // Hidden input to hold the JSON response from Merlin
        var $merlinMessageResponse = $(context).find('input[name=merlinone_response]');

        // Disable submit button until we get data
        var $submit = $(context).find('.is-entity-browser-submit');
        $submit.prop('disabled', true);

        // Display throbber when button is clicked
        $submit.on('click', function () {
          var throbber = $('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div><div class="message">' + Drupal.t('Please wait...') + '</div></div>');
          $submit.after(throbber);
        });

        // Receive selected item information
        var messageHandler = function (e) {
          if (e.data && getHost(e.origin) === settings.merlinone.entity_browser.mx_host) {
            $merlinMessageResponse.val(JSON.stringify(e.data));
            $submit.prop('disabled', false);
          }
        };

        // Attach listener
        window.addEventListener('message', messageHandler);
      }
    }
  };

})(jQuery, Drupal);
