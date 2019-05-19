(function ($) {

  Drupal.behaviors.toastify = {
    attach: function (context, settings) {
      $('body').once().each(function () {
        var messages = settings.toastify.messages;
        var display_settings = settings.toastify.settings;
        var types = ['status', 'warning', 'error'];

        for (var i = 0; i < types.length; i++) {
          var toastify_settings = {
            duration: display_settings[types[i]].duration,
            gravity: display_settings[types[i]].gravity,
            positionLeft: display_settings[types[i]].positionLeft,
            close: display_settings[types[i]].close,
            backgroundColor: 'linear-gradient(' + display_settings[types[i]].direction + ', ' + display_settings[types[i]].color + ', ' + display_settings[types[i]].color2 + ')',
          };
          if (messages.hasOwnProperty(types[i])) {
            for (var j = 0; j < messages[types[i]].length; j++) {
              toastify_settings['text'] = messages[types[i]][j]
              Toastify(toastify_settings).showToast();
              Drupal.attachBehaviors();
            }
          }
        }
      });
    }
  }

})(jQuery);
