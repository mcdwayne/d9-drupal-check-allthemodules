/**
 * @file
 * Popup behaviors.
 */

(function ($) {

  // Function to set the Cookie.
  function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
  }

  // Retrieve the cookie which is used to show the popup.
  function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }

  /**
   * Disabling popup.
   */
  function popupDisable() {
    // Disables popup only if it is enabled.
    $("#popup-dialog-window").fadeOut("slow");
    $("#popup-dialog-background").fadeOut("slow");
  }

  // Popup markup and displaying it.
  function displayPopup(title, body, top) {
    $('body')
      .append("<div id='popup-dialog-window' style='top: " + top + "px;'><div id='popup-header'><h2 id='popup-dialog-title'>" + title + "</h2><span id='close-dialog'>X</span></div><div id='popup-dialog-content'>" + body + "</div></div><div id='popup-dialog-background'></div>");

    // Closing popup.
    // Click the x event!
    $("#close-dialog").click(function () {
      popupDisable();
    });

    // Click out event!
    $("#popup-dialog-background").click(function () {
      popupDisable();
    });
  }

  // Drupal behaviors for the popup.
  Drupal.behaviors.popup = {
    attach: function (context, settings) {
      var user = getCookie("FirstUser");
      var enabled = settings.enabled;
      var title = settings.title;
      var body = settings.body;
      var delay = settings.delay + "000";
      var top = settings.top;
      var popup_interval_setting = settings.popup_interval_setting;
      var time_interval = settings.time_interval;
      if (user == "" && enabled == 1) {
        var showpopup = function () {
          if (popup_interval_setting == 2) {
            if (time_interval) {
              setCookie("FirstUser", 1, time_interval);
            }
          }
          else {
            setCookie("FirstUser", 1, 180);
          }
          displayPopup(title, body, top);
        };
        setTimeout(showpopup, delay);
      }
    }
  };
  // Drupal behaviors for the popup.
  Drupal.behaviors.checkbox = {
    attach: function (context, settings) {
      // Enable checkbox functionhality.
      $('input#edit-popup-enabled').change(function () {
        if ($(this).is(':checked')) {
          $('#config-form-section').removeAttr('hidden').show();
        } else {
          $('#config-form-section').hide();
        }
      });
    }
  };

})(jQuery);
