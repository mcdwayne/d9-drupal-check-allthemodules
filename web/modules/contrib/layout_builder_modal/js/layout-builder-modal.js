(function ($) {

  let zIndex = 601;

  $(window).on({
    'dialog:aftercreate': function dialogAftercreate(event, dialog, $element, settings) {
      $element.dialog('widget').css('zIndex', zIndex);
      $element.dialog('widget').next('.ui-widget-overlay').css('zIndex', zIndex - 1);

      zIndex += 2;
    }
  });

})(jQuery);
