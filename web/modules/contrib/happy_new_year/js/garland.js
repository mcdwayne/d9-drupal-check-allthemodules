(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.garland = {
    attach: function (context, settings) {

      var num = 0;
      function garland() {
        $('#garland').css('backgroundPosition', '0 -' + num + 'px');
        if (num > 72) {
          num = 36;
        }
        else {
          num = num + 36;
        }
      }

      // if garland not exists.
      if ($('#garland').length === 0) {
        $('body').prepend('<div id="garland"></div>');

        if (drupalSettings.fixed_garland) {
          $('#garland').css('position','fixed');
        }
        else {
          $('#garland').css('position','absolute');
        }
      }

      // if core toolbar or admin_toolbar exists.
      if ($('body').hasClass('toolbar-horizontal')) {
        var toolbarHeight = $('#toolbar-bar').height() + $('#toolbar-item-administration-tray').height();
        $('#garland').css('top', toolbarHeight + 'px');
        $('#garland').css('zIndex', '1');
      }

      // if bootstrap navbar fixed top exists.
      if ($('header').is('.navbar-fixed-top')) {
        var navbarHeight = $('.navbar-fixed-top').height();
        var navbarTop = $('.navbar-fixed-top').position().top;
        $('#garland').css('top', navbarTop + navbarHeight + 'px');
      }

      setInterval(function () {
        garland();
      }, 500);

    }
  };

})(jQuery, Drupal);
