/**
 * @file
 * Javascript implementations for asset details.
 */

/**
 * Asset details elements.
 */
(function($) {

  /**
   * Changes the value of the embed text area when the select field is changed.
   */
  Drupal.behaviors.widencollectiveSetEmbedTextAreaValue = {
    attach: function (context, settings) {
      // Hide toolbar and branding areas by default.
      $('#toolbar').hide();
      $('#branding').hide();

      // Set the value of the embed text area based on the select field.
      $('#widen_embed_select').bind('change', function() {
        $('#widen_embed_text_area').val(this.value);
      });
    }
  };

})(jQuery);
