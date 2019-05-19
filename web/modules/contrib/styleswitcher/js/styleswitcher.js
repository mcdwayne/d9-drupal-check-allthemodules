/**
 * @file
 * Functionality that provides style switching without page reloading.
 */

(function ($) {

  'use strict';

  /**
   * Namespace for styleswitcher-related functionality for Drupal.
   *
   * @namespace
   */
  Drupal.styleSwitcher = {};

  /**
   * Gets the visitor's active style or saves the style key to the cookie.
   *
   * @param {string|undefined} styleName
   *   (optional) Style key to save.
   *
   * @return {string|undefined}
   *   The key of active style in case if styleName is not provided and
   *   corresponding cookie exists in visitor's browser.
   */
  Drupal.styleSwitcher.cookie = function (styleName) {
    var key = 'styleswitcher[' + drupalSettings.styleSwitcher.theme + ']';

    if (typeof styleName != 'undefined') {
      var expires = new Date();
      expires.setTime(expires.getTime() + (drupalSettings.styleSwitcher.cookieExpire * 1000));

      // Follow PHP way: do not encode cookie name but encode the value. This
      // consistency must be preserved because both PHP and JS are used for work
      // with styleswitcher cookies.
      document.cookie = key + '=' + encodeURIComponent(styleName) + '; expires=' + expires.toUTCString() + '; path=' + drupalSettings.path.baseUrl;
    }
    else if (document.cookie) {
      var cookies = document.cookie.split('; ');

      for (var i = 0; i < cookies.length; i++) {
        var parts = cookies[i].split('=');

        if (parts[0] === key) {
          return decodeURIComponent(parts[1]);
        }
      }
    }
  };

  /**
   * Given the style object, switches stylesheets.
   *
   * @param {object} style
   *   A style object.
   */
  Drupal.styleSwitcher.switchStyle = function (style) {
    // Update the cookie first.
    Drupal.styleSwitcher.cookie(style.name);

    // Now switch the stylesheet. Path is absolute URL with scheme.
    $('#styleswitcher-css').attr('href', style.path);

    // Cosmetic changes.
    Drupal.styleSwitcher.switchActiveLink(style.name);
  };

  /**
   * Switches active style link.
   *
   * @param {string} styleName
   *   Machine name of the active style.
   */
  Drupal.styleSwitcher.switchActiveLink = function (styleName) {
    $('.style-switcher').removeClass('active')
      .filter('[data-rel="' + styleName + '"]').addClass('active');
  };

  /**
   * Builds an overlay for transition from one style to another.
   *
   * @return {HTMLElement}
   *   Dom object of overlay.
   */
  Drupal.styleSwitcher.buildOverlay = function () {
    var $overlay = $('<div>')
      .attr('id', 'style-switcher-overlay')
      .appendTo($('body'))
      .hide();

    return $overlay;
  };

  /**
   * Removes overlay.
   */
  Drupal.styleSwitcher.killOverlay = function () {
    // This is more useful than just "$(this).remove()".
    $('#style-switcher-overlay').remove();
  };

  /**
   * Binds a switch behavior on links clicking.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach style switching behavior to relevant context.
   */
  Drupal.behaviors.styleSwitcher = {
    attach: function (context, settings) {
      // Set active link. It is not set from PHP, because of pages caching.
      var activeStyle = Drupal.styleSwitcher.cookie() || settings.styleSwitcher.default;
      Drupal.styleSwitcher.switchActiveLink(activeStyle);

      $('.style-switcher', context).once('styleswitcher').click(function (e) {
        var $link = $(this).blur();
        var name = $link.attr('data-rel');
        var style = settings.styleSwitcher.styles[name];

        if (settings.styleSwitcher.enableOverlay) {
          var $overlay = Drupal.styleSwitcher.buildOverlay();

          $overlay.fadeIn('slow', function () {
            Drupal.styleSwitcher.switchStyle(style);
            $overlay.fadeOut('slow', Drupal.styleSwitcher.killOverlay);
          });
        }
        else {
          Drupal.styleSwitcher.switchStyle(style);
        }

        e.preventDefault();
      });
    }
  };

})(jQuery);
