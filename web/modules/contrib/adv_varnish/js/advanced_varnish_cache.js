/**
 * @file
 * Handle user blocks behavior on the page.
 */
(function ($, drupalSettings) {

  "use strict";

  // Replace placeholder with actual user data.
  $('#avc-user-blocks .avc-user-block').each(function () {
    var $this = jQuery(this);
    var $target = jQuery($this.attr('data-target'));
    if ($target.length > 0) {
      $target.replaceWith($this.html());
    }
  });

  // Extend drupalSettings with user block js data
  $.extend(true, drupalSettings, avcUserBlocksSettings);

})(jQuery, drupalSettings);
