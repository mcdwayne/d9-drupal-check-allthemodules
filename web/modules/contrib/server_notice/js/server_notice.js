/**
 * @file
 * Inserts the server notice element in the DOM.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  function addServerNoticeToDom(context) {
    $('body').once('server-notice-once').each(function () {
      var serverNoticeColour = drupalSettings.servernotice.colour;
      var serverNoticeBorderElement = $("<div class='server-notice-border'></div>");
      $('body').after(serverNoticeBorderElement);
      $('.server-notice-border').css({border: '7px solid ' + serverNoticeColour});
      if (drupalSettings.servernotice.hasOwnProperty('notice')) {
        var serverNoticeText = drupalSettings.servernotice.notice;
        var serverNoticeNoticeElement = $('<div class="server-notice-notice">' + serverNoticeText + '</div>').css({background: serverNoticeColour});
        $('.server-notice-border').append(serverNoticeNoticeElement);
      }
    });
  }

  Drupal.behaviors.ServerNoticeBehavior = {
    attach: function (context, settings) {
      addServerNoticeToDom();
    }
  };

})(jQuery, Drupal, drupalSettings);
