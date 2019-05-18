/**
 * @file
 * Author Pane Popup. author_pane_popup.js.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.AuthorPanePopup = {
    attach: function (context, settings) {
      var instances = $.parseJSON(drupalSettings.data.author_pane_popup.qtip_instances);
      var instance = drupalSettings.data.author_pane_popup.qtip_instance;
      var author_pane_popup_triggers = drupalSettings.data.author_pane_popup.jquery_selectors;
      var qtip_settings = (instances[instance] !== 'undefined') ? instances[instance] : '';
      $(author_pane_popup_triggers).each(function () {
        var url = $(this).attr('href');
        $(this).qtip(qtip_settings);
        $(this).qtip('option', 'content.text', drupalSettings.data.author_pane_popup.loading_text);
        $(this).qtip('option', 'content.ajax', {
          url: '/author_pane_popup/user',
          data: {url: url},
          type: 'POST',
          success: function (data, status) {
            this.set('content.text', $(data).find('.view-author-pane-popup').html());
          }
        });
      });
    }
  };
})(jQuery);
