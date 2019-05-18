(function ($) {
  Drupal.behaviors.cookiec_popup = {
    attach: function (context, settings) {
      $('body').not('.sliding-popup-processed').addClass('sliding-popup-processed').each(function () {
        try {
          var enabled = drupalSettings.cookiec.popup_enabled;
          if (!enabled) {
            // return;
          }
          if (!Drupal.cookiec.cookiesEnabled()) {
            //  return;
          }
          var status = Drupal.cookiec.getCurrentStatus();

          var agreed_enabled = drupalSettings.cookiec.popup_agreed_enabled;
          var popup_hide_agreed = drupalSettings.cookiec.popup_hide_agreed;
          if (status == 0) {
            var next_status = 1;

            $('.agree-button').click(function () {
              if (!agreed_enabled) {
                Drupal.cookiec.setStatus(1);
                next_status = 2;
              }
              Drupal.cookiec.changeStatus(next_status);
            });

            Drupal.cookiec.createPopup(drupalSettings.cookiec.popup_html_info);
          } else if (status == 1) {
            Drupal.cookiec.createPopup(drupalSettings.cookiec.popup_html_agreed);
            if (popup_hide_agreed) {
              $('a, input[type=submit]').bind('click.cookiec_hideagreed', function () {
                Drupal.cookiec.changeStatus(2);
              });
            }

          } else {
            return;
          }
        }
        catch (e) {
          return;
        }
      });
    }
  }

  Drupal.cookiec = {};

  Drupal.cookiec.createPopup = function (html) {
    var popup = $(html)
      .attr({"id": "sliding-popup"})
      .height(drupalSettings.cookiec.popup_height)
      .width(drupalSettings.cookiec.popup_width)
      .hide();

    if (drupalSettings.cookiec.popup_position) {
      popup.prependTo("body");
      var height = popup.height();
      popup.show()
        .attr({"class": "sliding-popup-top"})
        .css({"top": -1 * height})
        .animate({top: 0}, drupalSettings.cookiec.popup_delay);
    } else {
      popup.appendTo("body");
      height = popup.height();
      popup.show()
        .attr({"class": "sliding-popup-bottom"})
        .css({"bottom": -1 * height})
        .animate({bottom: 0}, drupalSettings.cookiec.popup_delay)
    }
    Drupal.cookiec.attachEvents();
  }

  Drupal.cookiec.attachEvents = function () {
    var agreed_enabled = drupalSettings.cookiec.popup_agreed_enabled;
    $('.find-more-button').click(function () {
      if (drupalSettings.cookiec.popup_link) {
        window.open(drupalSettings.cookiec.popup_link);
      }
    });
    $('.agree-button').click(function () {
      var next_status = 1;
      if (!agreed_enabled) {
        Drupal.cookiec.setStatus(1);
        next_status = 2;
      }
      Drupal.cookiec.changeStatus(next_status);
    });
    $('.hide-popup-button').click(function () {
      Drupal.cookiec.changeStatus(2);
    });
  }

  Drupal.cookiec.getCurrentStatus = function () {
    var search = 'cookie-agreed-' + drupalSettings.cookiec.popup_language + '=';
    var offset = document.cookie.indexOf(search);
    if (offset < 0) {
      return 0;
    }
    offset += search.length;
    var end = document.cookie.indexOf(';', offset);
    if (end == -1) {
      end = document.cookie.length;
    }
    var value = document.cookie.substring(offset, end);
    return parseInt(value);
  }

  Drupal.cookiec.changeStatus = function (value) {
    var status = Drupal.cookiec.getCurrentStatus();
    if (status == value) return;
    if (drupalSettings.cookiec.popup_position) {
      $(".sliding-popup-top").animate({top: $("#sliding-popup").height() * -1}, drupalSettings.cookiec.popup_delay, function () {
        if (status == 0) {
          $("#sliding-popup").html(drupalSettings.cookiec.popup_html_agreed).animate({top: 0}, drupalSettings.cookiec.popup_delay);
          Drupal.cookiec.attachEvents();
        }
        if (status == 1) {
          $("#sliding-popup").remove();
        }
      });
    } else {
      $(".sliding-popup-bottom").animate({bottom: $("#sliding-popup").height() * -1}, drupalSettings.cookiec.popup_delay, function () {
        if (status == 0) {
          $("#sliding-popup").html(drupalSettings.cookiec.popup_html_agreed).animate({bottom: 0}, drupalSettings.cookiec.popup_delay)
          Drupal.cookiec.attachEvents();
        }
        if (status == 1) {
          $("#sliding-popup").remove();
        }
      });
    }
    Drupal.cookiec.setStatus(value);
  }

  Drupal.cookiec.setStatus = function (status) {
    var date = new Date();
    date.setDate(date.getDate() + 100);
    var cookieString = "cookie-agreed-" + drupalSettings.cookiec.popup_language + "=" + status + ";expires=" + date.toUTCString() + ";path=" + drupalSettings.path.baseUrl;
    document.cookie = cookieString;
  }

  Drupal.cookiec.hasAgreed = function () {
    var status = Drupal.cookiec.getCurrentStatus();
    if (status == 1 || status == 2) {
      return true;
    }
    return false;
  }

  Drupal.cookiec.cookiesEnabled = function () {
    var cookieEnabled = (navigator.cookieEnabled) ? true : false;
    if (typeof navigator.cookieEnabled == "undefined" && !cookieEnabled) {
      document.cookie = "testcookie";
      cookieEnabled = (document.cookie.indexOf("testcookie") != -1) ? true : false;
    }
    return (cookieEnabled);
  }

})(jQuery);