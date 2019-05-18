/**
 * @file
 * Adds JS functionality for accessibility tool
 */
(function ($) {
  "use strict";

  Drupal.behaviors.accessibiltityTool = {
    /**
     * Attach method for this behavior.
     *
     * @param context
     *   The context for which the behavior is being executed.
     * @param settings
     *   An array of settings.
     */
    attach: function (context, settings) {
      var self = this;

      self.prependBody(context, settings);
      self.zoom(context, settings);
      self.contrast(context, settings);
      self.refresh(context, settings);
    },

    /**
     * Prepends block to body when required.
     */
    prependBody: function (context, settings) {
      $('body', context).prepend($('.block-accessibility-tool-block.right, ' +
        '.block-accessibility-tool-block.left', context));
    },

    /**
     * Functionality to zoom in and out on site.
     */
    zoom: function (context, settings) {
      var zoom = readCookie('zoom');

      // Initial.
      if (zoom) {
        // Add zoom class.
        $('html', context).addClass('zoom-' + zoom);
      }
      else {
        // Create zoom cookie.
        createCookie('zoom', 0, 7);
      }

      // Zoom in.
      $('.accessibility-tool .zoom-in-btn', context).click(function () {
        zoom = readCookie('zoom');

        if (zoom < 4) {
          var zoom_increase = parseInt(zoom) + 1;

          // Remove zoom class.
          $('html', context).removeClass('zoom-' + zoom);

          // Add new zoom class.
          $('html', context).addClass('zoom-' + zoom_increase);

          // Recreate cookie.
          createCookie('zoom', zoom_increase, 7);
        }

        return false;
      });

      // Zoom out.
      $('.accessibility-tool .zoom-out-btn', context).click(function () {
        zoom = readCookie('zoom');

        if (zoom > 0) {
          var zoom_increase = parseInt(zoom) - 1;

          // Remove zoom class.
          $('html', context).removeClass('zoom-' + zoom);

          // Add new zoom class.
          $('html', context).addClass('zoom-' + zoom_increase);

          // Recreate cookie.
          createCookie('zoom', zoom_increase, 7);
        }

        return false;
      });
    },

    /**
     * Functionality to set contrast on site.
     */
    contrast: function (context, settings) {
      var contrast = readCookie('contrast');
      var tool_settings = settings.accessibility_tool;
      var css_styles = '';

      // Initial.
      if (contrast) {
        // Add contrast class.
        $('body', context).addClass('contrast-' + contrast);
      }
      else {
        // Create contrast cookie.
        createCookie('contrast', 0, 7);
      }

      // Change contrast class.
      $('.accessibility-tool .contrast-btn', context).click(function () {
        contrast = readCookie('contrast');
        var contrast_increase;

        if (contrast < tool_settings.contrast_color_count) {
          contrast_increase = parseInt(contrast) + 1;
        }
        else {
          contrast_increase = 0;
        }

        // Remove zoom class.
        $('body', context).removeClass('contrast-' + contrast);

        // Add new zoom class.
        $('body', context).addClass('contrast-' + contrast_increase);

        // Recreate cookie.
        createCookie('contrast', contrast_increase, 7);

        return false;
      });

      // Add CSS colors.
      for (var i = 1; i <= tool_settings.contrast_color_count; i++) {
        var contrast_class = '.contrast-' + i;
        var selectors_str = '';
        var alt_selectors_str = '';
        var selectors = tool_settings.selectors.split(/\r?\n/);
        var alt_selectors = tool_settings.alt_selectors.split(/\r?\n/);
        var background_color = tool_settings['color_background_' + i];
        var foreground_color = tool_settings['color_foreground_' + i];
        var background = 'background-color:' + background_color +
          ' !important;';
        var foreground = 'color:' + foreground_color + ' !important;';
        var alt_background = 'background-color:' + foreground_color +
          ' !important;';
        var alt_foreground = 'color:' + background_color + ' !important;';

        // Adding settings selectors.
        $.each(selectors, function( index, value ) {
          if (value != '') {
            selectors_str += contrast_class + ' ' + value + ',';
          }
        });

        // Add "at-contrast" class.
        selectors_str += contrast_class + ' .at-contrast,';

        // Add body.
        selectors_str += contrast_class;

        // Adding settings selectors.
        $.each(alt_selectors, function( index, value ) {
          if (value != '') {
            alt_selectors_str += contrast_class + ' ' + value + ',';
          }
        });

        // Add "at-alt-contrast" class.
        alt_selectors_str += contrast_class + ' .at-alt-contrast';

        // Create code blocks and add to style.
        css_styles += selectors_str + '{' + background + foreground + '}';
        css_styles += alt_selectors_str + '{' + alt_background +
          alt_foreground + '}';
      }

      var css_rules = '<style type="text/css">' + css_styles + '</style>';

      $('head').append(css_rules);
    },

    /**
     * Resets zoom and contrast cookies on page.
     */
    refresh: function (context, settings) {
      var zoom = readCookie('zoom'),
        contrast = readCookie('contrast');

      $('.accessibility-tool .refresh-btn', context).click(function () {
        var zoom = readCookie('zoom'),
          contrast = readCookie('contrast');

        // Remove zoom class.
        $('html', context).removeClass('zoom-' + zoom);
        $('body', context).removeClass('contrast-' + contrast);

        // Set zoom and contrast to 0.
        createCookie('zoom', 0, 7);
        createCookie('contrast', 0, 7);

        return false;
      });
    }
  };

  /**
   * Creates cookie with value and days to expire.
   *
   * @param name
   *   String of cookie name.
   * @param value
   *   Integer of zoom value.
   * @param days
   *   Integer of days to expire.
   */
  function createCookie(name, value, days) {
    if (days) {
      var date = new Date(),
        expires;
      date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
      expires = "; expires=" + date.toGMTString();
    }
    else {
      expires = "";
    }

    document.cookie = name + "=" + value + expires + "; path=/";
  }

  /**
   * Reads cookie and returns cookie value.
   *
   * @param name
   *   String of cookie name.
   *
   * @returns string
   *   Returns cookie value
   */
  function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1, c.length);
      }
      if (c.indexOf(nameEQ) == 0) {
        return c.substring(nameEQ.length, c.length);
      }
    }
    return null;
  }

})(jQuery);

