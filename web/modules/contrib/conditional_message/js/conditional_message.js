(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.conditional_message = {
    attach: function (context, settings) {
      // Bail if user already closed the message before.
      if (typeof conditional_message_user_closed  !== "undefined" && localStorage.getItem(conditional_message_user_closed)) {
        return;
      }
      // Run only once (not on ajax calls).
      if (context != document) {
        return;
      }
      // Shorthand for the configurations.
      var config = drupalSettings.conditional_message;
      // TODO rewrite this in pure JS to avoid jQuery dependency.
      $.ajax({
        url: config.base_path + "conditional_message_data_output", success: function (result) {
          // Below are front-end checks to see if the message should be displayed.
          var check = [];
          // Check and set sessions with localStorage.
          check['session'] = true;
          var cmrs = 'conditionalMessageReadStatus';
          var readStatus = localStorage.getItem(cmrs);
          if (config.options.session && readStatus) {
            check['session'] = false;
          } else {
            localStorage.setItem(cmrs, true);
          }
          // Paths conditions.
          check['path'] = true;
          if (config.options.path && result.paths.indexOf(window.location.pathname.substr(config.base_path.length-1)) < 0) {
            check['path'] = false;
          }
          // Content type conditions.
          check['type'] = true;
          if (config.options.content_type && result.types) {
            check['type'] = false;
            result.types.forEach(function (type) {
              var typeClass = 'page-node-type-' + type;
              if ($('body').hasClass(typeClass)) {
                check['type'] = true;
              }
            });
          }
          // Show message if all checks pass.
          if (result.display && check['session'] && check['path'] && check['type']) {
            // Build the message HTML.
            var html = '<div class="conditional-message" style="' +
                    'background-color:#' + config.bg_color + '; ' +
                    'color:#' + config.color + '; ' +
                    '">' + config.message + '<span>X</span></span></div>';
            // Place the message in the page top or bottom.
            switch (config.position) {
              case  'bottom':
                $(config.target).append($(html).addClass('conditional-message-bottom'));
                break;

              default:
                $(config.target).prepend($(html).addClass('conditional-message-top'));
            }
            // Close button.
            $('.conditional-message span').on('click', function () {
              $(this).parent().remove();
              localStorage.setItem(conditional_message_user_closed, true);
            });
          }
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
