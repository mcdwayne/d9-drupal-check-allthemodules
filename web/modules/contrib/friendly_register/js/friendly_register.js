(function ($, drupalSettings) {

  Drupal.behaviors.friendly_register = {
    attach: function (context, settings) {
      var timeout;

      var loginURL = drupalSettings.basePath + 'user';
      var resetURL = drupalSettings.basePath + 'user/password';

      var userName = new Object();
      userName.oldValue = '';
      userName.ajaxPath = drupalSettings.basePath + 'ajax/check-user/';
      userName.field = $('#user-register-form #edit-name', context);
      userName.avail = Drupal.t('This username is available.');
      userName.notAvail = Drupal.t('This username is not available.');

      var email = new Object();
      email.oldValue = '';
      email.ajaxPath = drupalSettings.basePath + 'ajax/check-email/';
      email.field = $('#user-register-form #edit-mail', context);
      email.avail = Drupal.t('This email address has not been used.');
      email.notAvail = Drupal.t('This email address is already in use, please <a href="@login">try logging in</a> with that email address or <a href="@reset">resetting your password</a>.', {'@login': loginURL, '@reset': resetURL});

      userName.field.focus(function () {
        timeout = setInterval(function (){
          var newValue = userName.field.val();
          if (newValue != userName.oldValue) {
            userName.oldValue = newValue;
            $.getJSON(userName.ajaxPath + newValue, function(data) {
              var message;
              var cssclass;
              if (data.available) {
                message = userName.avail;
                cssclass = 'ok';
              } else {
                message = userName.notAvail;
                cssclass = 'error';
              }
              $('#edit-name-check').remove();
              userName.field.after('<div id="edit-name-check" class="' + cssclass + '"><span class="text">' + message + '</span></div>');
            });
          }
        }, 1000);
      })
      .blur(function () {
        clearTimeout(timeout);
      });

      email.field.focus(function () {
        timeout = setInterval(function (){
          var newValue = email.field.val();
          if (newValue != email.oldValue) {
            email.oldValue = newValue;
            $.getJSON(email.ajaxPath + newValue, function(data) {
              var message;
              if (data.available) {
                message = email.avail;
                cssclass = 'ok';
              } else {
                message = email.notAvail;
                cssclass = 'error';
              }
              $('#edit-mail-check').remove();
              email.field.after('<div id="edit-mail-check" class="' + cssclass + '"><span class="text">' + message + '</span></div>');
            });
          }
        }, 1000);
      })
      .blur(function () {
        clearTimeout(timeout);
      });
    }
  };

})(jQuery, drupalSettings);
