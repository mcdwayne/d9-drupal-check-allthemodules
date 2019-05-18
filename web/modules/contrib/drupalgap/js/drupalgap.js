/**
 * @file
 * This file contains the JavaScript implementation for the DrupalGap module.
 */
(function ($) {
  Drupal.behaviors.drupalgap = {
    attach: function (context, settings) {
      try {
        var drupalgap_documentation_link = '<a href="http://drupal.org/node/2015065" target="_blank">View DrupalGap Troubleshooting Topics</a>';
        $('#drupalgap-system-connect-status-message').html("<img src='" + drupalSettings.basePath  + "misc/throbber.gif' />");
        // Obtain session token.
        var token_url = "/rest/session/token";
        $.ajax({
          url:window.location.origin + token_url,
          type:"get",
          dataType:"text",
          error:function (jqXHR, textStatus, errorThrown) {
            if (!errorThrown) {
              errorThrown = Drupal.t('Token retrieval failed!');
            }
            var html = '<div class="messages error">' + errorThrown + '</div>';
                html += '<div class="messages warning">' + drupalgap_documentation_link + '</div>';
                $('#drupalgap-system-connect-status-message').html(html);
          },
          success: function (token) {
            // Call system connect with session token.
            $.ajax({
              url: window.location.origin + '/drupalgap/system/connect',
              type: "post",
              dataType: "json",
              beforeSend: function (request) {
                request.setRequestHeader("X-CSRF-Token", token);
              },
              error: function (jqXHR, textStatus, errorThrown) {
                if (!errorThrown) {
                  errorThrown = Drupal.t('System connect failed!');
                }
                var html = '<div class="messages error">' + errorThrown + '</div>';
                html += '<div class="messages warning">' + + '</div>';
                $('#drupalgap-system-connect-status-message').html(html);
              },
              success: function (data) {
                msg = Drupal.t("The system connect test was successful, <strong>DrupalGap is configured properly!</strong>");
                $('#drupalgap-system-connect-status-message').html("<div class='messages status'>" + msg + "</div>");
              }
            });
          }
        });
      }
      catch (error) {
        alert(Drupal.t(error));
      }
    }
  };
}(jQuery));
