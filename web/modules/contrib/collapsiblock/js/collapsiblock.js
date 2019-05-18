(function ($) {

  Drupal.Collapsiblock = Drupal.Collapsiblock || {};

  Drupal.behaviors.collapsiblock = {

    attach: function (context, settings) {
      var cookieData = Drupal.Collapsiblock.getCookieData();
      var slidetype = settings.collapsiblock.slide_type;
      var activePages = settings.collapsiblock.active_pages;
      var slidespeed = parseInt(settings.collapsiblock.slide_speed, 10);
      $('.collapsiblock').once('collapsiblock').each(function () {
        var id = this.id.split("-").pop();
        var titleElt = $(this)[0];
        if (titleElt.children.length > 0) {
          // Status values: 1 = not collapsible, 2 = collapsible and expanded,
          // 3 = collapsible and collapsed, 4 = always collapsed,
          // 5 = always expanded
          var stat = $(this).data('collapsiblock-action');
          if (stat == 1) {
            return;
          }

          titleElt.target = $(this).siblings().not($('.contextual'));
          $(titleElt)
          .wrapInner('<a href="#collapse-' + id + '" role="link" />')
          .click(function (e) {
            if ($(this).is('.collapsiblockCollapsed')) {
              $(this).removeClass('collapsiblockCollapsed');
              if (slidetype == 1) {
                $(this.target).slideDown(slidespeed).attr('aria-hidden', false);
              }
              else {
                $(this.target).animate({
                  height: 'show',
                  opacity: 'show'
                }, slidespeed);
              }

              // Don't save cookie data if the block is always collapsed.
              if (stat != 4 && stat != 5) {
                cookieData[id] = 1;
              }
            }
            else {
              $(this).addClass('collapsiblockCollapsed');
              if (slidetype == 1) {
                $(this.target).slideUp(slidespeed).attr('aria-hidden', true);
              }
              else {
                $(this.target).animate({
                  height: 'hide',
                  opacity: 'hide'
                }, slidespeed);
              }

              // Don't save cookie data if the block is always collapsed.
              if (stat != 4 && stat != 5) {
                cookieData[id] = 0;
              }
            }
            // Stringify the object in JSON format for saving in the cookie.
            cookieString = JSON.stringify(cookieData);
            $.cookie('collapsiblock', cookieString, {
              path: settings.basePath
            });
          });
          // Leave active blocks if Remember collapsed on active pages is false.
          // If the block is expanded, do nothing.
          if (stat == 4 || (cookieData[id] == 0 || (stat == 3 && cookieData[id] == undefined))) {
            if (!$(this).find('a.active').length || activePages === 1) {
              // Allow block content to assign class 'collapsiblock-force-open'
              // to it's content to force itself to stay open. E.g. useful if
              // block contains a form that was just ajaxly updated and should
              // be visible
              if (titleElt.target.hasClass('collapsiblock-force-open') || titleElt.target.find('.collapsiblock-force-open').length > 0) {
                return;
              }
              $(titleElt).addClass('collapsiblockCollapsed');
              $(titleElt.target).hide();
            }
          }
        }
      });
    }

  };

  Drupal.Collapsiblock.getCookieData = function () {
    if ($.cookie) {
      var cookieString = $.cookie('collapsiblock');
      return cookieString ? $.parseJSON(cookieString) : {};
    }
    else {
      return '';
    }
  };

})(jQuery);
