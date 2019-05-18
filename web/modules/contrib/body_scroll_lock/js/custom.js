(function ($, bodyScrollLock) {
  $(window).on({
    'dialog:beforecreate': (event, dialog, $element) => {
      if (!$element.is('#drupal-off-canvas')) {
        bodyScrollLock.disableBodyScroll($element[0]);
      }
    },
    'dialog:beforeclose': (event, dialog, $element) => {
      if (!$element.is('#drupal-off-canvas')) {
        bodyScrollLock.enableBodyScroll($element[0]);
      }
    },
  });
})(jQuery, bodyScrollLock);
