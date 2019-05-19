/**
 * @file
 * Handles Views update through node.js .
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the AJAX behavior to views which use node.js.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches ajaxView functionality to relevant elements.
   */
  Drupal.behaviors.viewsNodejs = {};
  Drupal.behaviors.viewsNodejs.attach = function () {
    if (drupalSettings && drupalSettings.viewsNodejs && drupalSettings.viewsNodejs.views) {
      $.each(drupalSettings.viewsNodejs.views, function (i, view) {
        var settings = view.settings;
        var selector = '.js-view-dom-id-' + settings.view_dom_id,
            href = view.href,
            viewData = {};

        // Construct an object using the settings defaults and then overriding
        // with data specific to the link.
        $.extend(
            viewData,
            settings,
            Drupal.Views.parseQueryString(href),
            // Extract argument data from the URL.
            Drupal.Views.parseViewArgs(href, settings.view_base_path)
        );

        // For anchor tags, these will go to the target of the anchor rather
        // than the usual location.
        $.extend(viewData, Drupal.Views.parseViewArgs(href, settings.view_base_path));
        
        // Check if there are any GET parameters to send to views.
        var queryString = window.location.search || '';
        if (queryString !== '') {
          // Remove the question mark and Drupal path component if any.
          queryString = queryString.slice(1).replace(/q=[^&]+&?|&?render=[^&]+/, '');
          if (queryString !== '') {
            // If there is a '?' in ajax_path, clean url are on and & should be
            // used to add parameters.
            queryString = ((/\?/.test(drupalSettings.viewsNodejs.ajax_path)) ? '&' : '?') + queryString;
          }
        }

        // Ajax settings for uping view.
        var ajax_settings = {
          url: drupalSettings.viewsNodejs.ajax_path + queryString,
          submit: viewData,
          selector: selector,
          setClick: true,
          event: 'viewsNodejs',
          base: selector,
          element: $(selector),
          progress: {}
        };

        new Drupal.ajax(ajax_settings);
      });
    }
  };

  /**
   * Drupal.Nodejs.callback on views update.
   */
  if (Drupal.Nodejs && Drupal.Nodejs.callbacks) {
    Drupal.Nodejs.callbacks.viewsNodejs = {
      callback: function (message) {
        if (drupalSettings && drupalSettings.viewsNodejs && drupalSettings.viewsNodejs.views) {
          $.each(drupalSettings.viewsNodejs.views, function (i, view) {
            var settings = view.settings;
  
            // Search in settings view which need update.
            if (settings.view_name == message.view_id && settings.view_display_id == message.display_id) {
              var selector = '.js-view-dom-id-' + settings.view_dom_id;
              $(selector).trigger('viewsNodejs');
            }
          });
        }
      }
    };
  }
})(jQuery, Drupal, drupalSettings);