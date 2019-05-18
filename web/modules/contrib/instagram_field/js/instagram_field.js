/**
 * @file
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  $('#instagram-fieldsettings #edit-auth').on('click', function (e) {
    var formdata = $('#instagram-fieldsettings').serializeArray();
    formdata.push({name: 'op', value: $('#instagram-fieldsettings #edit-submit').val()});
    $.post($('#instagram-fieldsettings').attr('action'), formdata, function (data) {
      var url = 'https://api.instagram.com/oauth/authorize/?client_id=' +
        $('#instagram-fieldsettings #edit-clientid').val() +
        '&redirect_uri=' +
        encodeURIComponent($('#instagram-fieldsettings #edit-callbackurl--description').text().replace(/\s+/g, '')) +
        '&response_type=code';
      window.open(url);
    });
    e.preventDefault();
  });
})(jQuery, Drupal, drupalSettings);
