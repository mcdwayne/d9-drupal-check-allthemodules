/**
 * @file
 * To add input method support to the editable fields of a web page.
 */

(function ($, Drupal, drupalSettings) {

  // Call ime function for input method support to the editable fields of a web page.
  var flb_uid = drupalSettings.field_ids;
  var flb_path = drupalSettings.jqueryImePath + '/';
  $(flb_uid).ime({imePath: flb_path});
  
  // Set Language script after first select.
  var values = [];
  $.each($.ime.languages, function(key, value) {
    var obj = {};
    obj[key] = value['inputmethods'][0];
    if (key != 'en') {
      values.push(obj);
    }
  });
  $.each(values, function(key, value) {
    $.extend($.ime.preferences['registry']['imes'], value);
  });

})(jQuery, Drupal, drupalSettings);
