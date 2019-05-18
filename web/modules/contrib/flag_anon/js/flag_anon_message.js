/**
 * @file
 * Handle popin message display to anonymous user.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.flagAnonMessage = {
    popupLockFlag: false,
    attach: function (context, settings) {

      $('.flag-anon-message .label', context)
        .once('flag-anon-message')
        .each(function () {
          var popupContent = $($(this).data('selector'), $(this).parent());

          if (popupContent.length <= 0) {
            return true;
          }

          var self = Drupal.behaviors.flagAnonMessage;
          var popupDialog = Drupal.dialog(
            popupContent,
            {
              position: {my: "center", at: "center", of: this },
              autoResize: false,
              close: function (event, ui) {
                self.unlockPopup();
              }
            }
          );

          $('a', popupContent).click(function () {
            popupDialog.close();
          });

          $(this).click(function () {

            if (self.isPopupLocked()) {
              return;
            }

            popupDialog.show();
            self.lockPopup();
          });
        });
    },
    isPopupLocked: function () {
      return this.popupLockFlag;
    },
    lockPopup: function () {
      this.popupLockFlag = true;
    },
    unlockPopup: function () {
      this.popupLockFlag = false;
    }
  };

})(jQuery);
