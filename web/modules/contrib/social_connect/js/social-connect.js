(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.social_connect = {
    attach: function (context, settings) {
      // social-connect div
      var $socialDiv = $(context).find('div.social-connect').once();
      if ($socialDiv.length) {
        // FB LOGIN
        var socialSettings = settings.social_connect;
        FB.init({
          appId: parseInt(socialSettings.facebook.app_id),
          version: socialSettings.facebook.api_version,
          status: true,
          cookie: true,
          xfbml: true
        });
        $('.social-connect .facebook').click(function () {
          FB.login(function (response) {
            facebookLoginCallback(response);
          }, {scope: 'email,public_profile'});
        });
        var facebookLoginCallback = function (response) {
          if (response.authResponse) {
            var accessData = response.authResponse;
            // Logged into your app and Facebook.
            var data = {
              source: 'facebook',
              access_token: accessData.accessToken
            };
            debug(data.source, 'Welcome!  Fetching your information... ');
            fetchUserData(data);
          } else {
            debug('facebook', 'User cancelled login or did not fully authorize.');
          }
        };

        // facebook logout
        var facebookLogout = function () {
          FB.logout(function (response) {
            debug('facebook', 'logout success.');
          });
        };
        var fetchUserData = function (data) {
          $.ajax({
            type: "POST",
            url: '/social-connect/handle',
            data: data,
            dataType: "json",
            cache: false,
            success: function (result) {
              debug(data.source, 'login success.');
              $('.social-connect .messages').html(result.message);
              if (socialSettings.redirect_to.replace(/^\s+|\s+$/g, "").length) {
                window.location.href = socialSettings.redirect_to;
              } else {
                location.reload();
              }
            },
            error: function (error) {
              debug(data.source, 'login submit error.');
              $('.social-connect .messages').html(error.responseJSON.message);
            }
          });
        };
        // FB LOGIN END

        // G+ LOGIN
        if (socialSettings.google.client_id) {
          var startApp = function () {
            gapi.load('auth2', function () {
              auth2 = gapi.auth2.init({
                client_id: socialSettings.google.client_id,
                cookiepolicy: 'single_host_origin',
                scope: 'https://www.googleapis.com/auth/plus.login'
              });
              attachSignin(document.getElementById('sc-id-google'));
            });
          };

          var attachSignin = function (element) {
            auth2.attachClickHandler(element, {}, function (googleUser) {
              var accessToken = gapi.auth2.getAuthInstance().currentUser.get().getAuthResponse().access_token;
              // Logged into your app and google plus.
              var data = {
                source: 'google',
                access_token: accessToken
              };
              debug(data.source, 'Welcome!  Fetching your information...');
              fetchUserData(data);

            }, function (error) {
              // error
              debug('google', 'User cancelled login or did not fully authorize.');
            });
          }
          startApp();
        }
        // G+ LOGIN END

        // This function is used to console message(s) for debuging
        var debug = function (source, message) {
          if (socialSettings.debug) {
            console.log('Social connet: Source > ' + source.charAt(0).toUpperCase() + source.slice(1) + ' > ' + message);
          }
        };
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
