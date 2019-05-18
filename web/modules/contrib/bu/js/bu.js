(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.bu = {
    attach: function attach(context, settings) {
      if (context == document) {
        window.$buoop = {
          notify: {
            i: settings.bu.notify_ie,
            f: settings.bu.notify_firefox,
            o: settings.bu.notify_opera,
            s: settings.bu.notify_safari,
            c: settings.bu.notify_chrome,
          },
          insecure: settings.bu.insecure,
          unsupported: settings.bu.unsupported,
          mobile: settings.bu.mobile,
          style: settings.bu.position,
          text: settings.bu.text_override,
          reminder: settings.bu.reminder,
          reminderClosed: settings.bu.reminder_closed,
          test: settings.bu.test_mode,
          newwindow: settings.bu.new_window,
          url: settings.bu.url,
          noclose: settings.bu.no_close,
          jsshowurl: settings.bu.show_source,
          api: 5
        };
        var e = document.createElement("script");
        e.setAttribute("type", "text/javascript");
        e.src = settings.bu.source;
        document.body.appendChild(e);
      }
    }
  };
})(jQuery, Drupal);



