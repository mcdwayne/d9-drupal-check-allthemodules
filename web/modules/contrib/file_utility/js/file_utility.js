/**
 * @file
 * Js file for this module.
 */

(function ($, Drupal, drupalSettings) {
  /* To restrict user for dowloadable files
   * Permission check
   * User form to be filled in popup
   */
  var base_url = drupalSettings.path.baseUrl;
  var open_model = drupalSettings.open_model_file;
  var file_force_download = drupalSettings.file_force_download;
  var allowed_extensions = drupalSettings.allowed_file_extensions;
  var pattern = new RegExp(allowed_extensions);
  // To open modal and open file directly on browser
  if (open_model == '1' && file_force_download == '0') {
    $('body a').each(function () {
      var vfile = $(this).attr("href");
      var ftoken = encodeURIComponent(window.btoa(vfile));
      if (pattern.test(vfile)) {
        $(this).attr("href", base_url+'form/user_information_form?f_path='+ftoken+'&force_download='+file_force_download);
        $(this).attr("class", 'use-ajax');
        $(this).attr("data-dialog-type", 'modal');
        $(this).attr("data-dialog-options", "{'width':800,'height':500}");
      }
    });
  }
  // To not open modal and force file download to save or download in browser
  else if (open_model == '0' && file_force_download == '1') {
    $('body a').each(function () {
      var vfile = $(this).attr("href");
      var ftoken = encodeURIComponent(window.btoa(vfile));
      if (pattern.test(vfile)) {
        $(this).attr("href", base_url+'filedownload?f_path='+ftoken+'&force_download='+file_force_download);
      }
    });
  }
  // To open modal and force file download to save or download in browser
  else if (file_force_download == '1' && open_model == '1') {
    $('body a').each(function () {
      var vfile = $(this).attr("href");
      var ftoken = encodeURIComponent(window.btoa(vfile));
      if (pattern.test(vfile)) {
        $(this).attr("href", base_url+'form/user_information_form?f_path='+ftoken+'&force_download='+file_force_download);
        $(this).attr("class", 'use-ajax');
        $(this).attr("data-dialog-type", 'modal');
        $(this).attr("data-dialog-options", "{'width':800,'height':500}");
      }
    });
  }
  // To not open modal and directly open file on browser
  else {
    $('body a').each(function () {
      var vfile = $(this).attr("href");
      var ftoken = encodeURIComponent(window.btoa(vfile));
      if (pattern.test(vfile)) {
        $(this).attr("href", base_url+'filedownload?f_path='+ftoken+'&force_download='+file_force_download);
      }
    });
  }
})(jQuery, Drupal, drupalSettings);
