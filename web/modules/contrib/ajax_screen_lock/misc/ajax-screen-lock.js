var ajaxScreenLock = {};
(function ($) {
  var unblock;
  $(document).ajaxSend(function (event, jqxhr, settings) {
    // If not locked and ajaxScreenLock settings are available.
    if (!unblock && Drupal.settings.ajaxScreenLock) {
      var target = ajaxScreenLock.getUrlPath(settings.url),
        pages = Drupal.settings.ajaxScreenLock.pages,
        visibility = Number(Drupal.settings.ajaxScreenLock.visibility);

      // Handle for certain pages.
      if (!$.isEmptyObject(pages)) {
        $.each(pages, function (num, page) {
          page = ajaxScreenLock.getUrlPath(page);
          if (target.length >= page.trim().length) {
            if (target.substr(0, page.trim().length) === page.trim() && visibility === 1) {
              ajaxScreenLock.blockUI();
            }
            else if (visibility === 0 && target.substr(0, page.trim().length) !== page.trim()) {
              ajaxScreenLock.blockUI();
            }
          }
        });
      }
      // Lock for all.
      else {
        ajaxScreenLock.blockUI();
      }
    }
  });

  $(document).ajaxStop(function (r, s) {
    if (unblock) {
      $.unblockUI();
      unblock = false;
    }
  });


  ajaxScreenLock = {
    // Grab path from AJAX url.
    getUrlPath: function (ajaxUrl) {
      var url = document.createElement("a");
      url.href = ajaxUrl;
      return url.pathname;
    },

    blockUI: function () {
      unblock = true;
      if (drupalSettings.ajaxScreenLock.throbber_hide) {
        $('.ajax-progress-throbber').hide();
      }

      $.blockUI({
        message: drupalSettings.ajaxScreenLock.message,
        css: {
          top: ($(window).height() - 400) / 2 + 'px',
          left: ($(window).width() - 400) / 2 + 'px',
          width: '400px'
        },
        timeout: drupalSettings.ajaxScreenLock.timeout
      });
    }
  }
}(jQuery));