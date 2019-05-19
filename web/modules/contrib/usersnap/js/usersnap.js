(function ($, Drupal, drupalSettings) {

   _usersnapconfig = {
    apiKey: drupalSettings.usersnap.apikey,
    btnText: drupalSettings.usersnap.button,
    lang: drupalSettings.usersnap.language,
    valign: drupalSettings.usersnap.valign,
    halign: drupalSettings.usersnap.halign,
    emailBox: drupalSettings.usersnap.email,
    emailBoxPlaceholder: drupalSettings.usersnap.eplaceholder,
    emailRequired: drupalSettings.usersnap.erequired,
    commentBox: drupalSettings.usersnap.cbox,
    commentBoxPlaceholder: drupalSettings.usersnap.cplaceholder,
    commentRequired: drupalSettings.usersnap.crequired
  };
  
  var s = document.createElement('script'),
      x = document.getElementsByTagName('script')[0];
  s.type = 'text/javascript';
  s.async = true;
  s.src = '//api.usersnap.com/load/' + _usersnapconfig["apiKey"] + '.js';
  x.parentNode.insertBefore(s, x);

})(jQuery, Drupal, drupalSettings);