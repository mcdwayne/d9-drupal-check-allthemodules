Drupal.behaviors.gated_file_close_dialog = {
  attach: function (context, settings) {
    jQuery.extend( jQuery.ui.dialog.prototype.options, {
      open: function() {
        var dialog = this;
        jQuery('.ui-widget-overlay').bind('click', function() {
          jQuery(dialog).dialog('close');
        });
      }

    });
  }
};
