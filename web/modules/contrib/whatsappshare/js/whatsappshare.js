/**
 * Javascript for the whatsappshare module.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.whatsappshare = {
  attach: function (context, settings) {
	var page_url = drupalSettings.whatsappshare.whatsappshareJS.page_url;
    var whatsappshare_button_text = drupalSettings.whatsappshare.whatsappshareJS.whatsappshare_button_text;
    var whatsappshare_button_size = drupalSettings.whatsappshare.whatsappshareJS.whatsappshare_button_size;
    var whatsappshare_sharing_text = drupalSettings.whatsappshare.whatsappshareJS.whatsappshare_sharing_text;
    var whatsappshare_sharing_location = drupalSettings.whatsappshare.whatsappshareJS.whatsappshare_sharing_location;
   $(whatsappshare_sharing_location).append('<a href="whatsapp://send" data-text="' + whatsappshare_sharing_text + '" data-href="' + page_url + '" class="wa_btn ' + whatsappshare_button_size + '" style="display:none; margin:1px;">' + whatsappshare_button_text + '</a>');
  }
};
})(jQuery, Drupal, drupalSettings);
