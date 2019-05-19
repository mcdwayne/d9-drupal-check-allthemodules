/**
 * Overwrites menu links with anchor tags and adds "fixed" class to menu if necessary.
 */
(function ($) {
  "use strict";
  Drupal.behaviors.singlePageMenu = {
    init: function (context, settings) {
      var menu_element = null;
      if (drupalSettings.singlePage.className === 'li') {
        menu_element = drupalSettings.singlePage.menuClass + ' li a';
      }
      else {
        menu_element = drupalSettings.singlePage.menuClass + ' .' + drupalSettings.singlePage.className;
      }
      var basePath = window.location.host + drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix;
      $(menu_element).each(function (index) {
        var hr = this.href.split(basePath);
        // To support "no clean" urls, replace q param by empty string.
        var anchor = hr[hr.length - 1].replace('?q=', '').split('/').join('_');
        if ($(document).find('#single-page-overall-wrapper').length) {
          // We are on the single page, just add anchor.
          this.href = "#" + anchor;
        }
        else {
          if (drupalSettings.singlePage.isFrontpage) {
            // Go to homepage, with anchor.
            this.href = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + "#" + anchor;
          }
          else {
            // Go to single-page-site (or alias) with anchor.
            this.href = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + drupalSettings.singlePage.urlAlias + "#" + anchor;
          }
        }
        $(this).attr('data-active-item', anchor);
      });
      // Remove menu items with class "hide" from DOM.
      $(drupalSettings.singlePage.menuClass + ' li .hide').parent().remove();
      // Add "fixed" class to menu when it disappears form viewport.
      if ($(document).find(drupalSettings.singlePage.menuClass).length) {
        var top = $(drupalSettings.singlePage.menuClass).offset().top;
        $(window).scroll(function (event) {
          // What the y position of the scroll is.
          var y = $(this).scrollTop();
          // Whether that's below the form.
          if (y > top) {
            // If so, ad the fixed class.
            $(drupalSettings.singlePage.menuClass).addClass('fixed');
          }
          else {
            // Otherwise remove it.
            $(drupalSettings.singlePage.menuClass).removeClass('fixed');
          }
        });
      }
    }
  };

  $(function () {
    // Init menu behaviour.
    Drupal.behaviors.singlePageMenu.init();
  });
})(jQuery);
