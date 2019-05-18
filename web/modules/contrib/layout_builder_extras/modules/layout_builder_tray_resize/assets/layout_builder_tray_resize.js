(function ($, Drupal) {
    Drupal.behaviors.layout_builder_tray_resize = {
      attach: function (context, settings) {
        $(document).ajaxComplete(function (event, xhr, settings) {
          if (event.delegateTarget.visibilityState == "visible") {
            $(context)
              .find(".ui-dialog-off-canvas")
              .each(function () {
                $(this).css("width", "100vw");
                $(this).css("left", "0");
              });
          }
        });
    }
  };
}) (jQuery, Drupal);