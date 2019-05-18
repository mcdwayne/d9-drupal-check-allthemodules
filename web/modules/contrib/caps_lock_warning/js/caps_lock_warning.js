(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS regarding the modal block.
   */
  Drupal.behaviors.capscheck = {
    attach: function (context, settings) {
      // var lotusHeight = drupalSettings.lotus.lotusJS.lotus_height;
      // check the state of the caps lock key
      // display a warning when caps lock is turned on
      // CapsLock.addListener(function(isOn){
      //   if (isOn){
      //     alert('Warning: you have turned caps lock on');
      //   }
      // });
      var message = settings.message;

      /**
       * Display a warning on capslock
       */
      $('.js-form-type-textfield input').keypress(function (e) {
                // Detect current character & shift keygeoField
        // var enabled = settings.enabled;
        var character = e.keyCode ? e.keyCode : e.which;
        // alert(character);
        var sftKey = e.shiftKey ? e.shiftKey : ((character == 20) ? true : false);
        // Is caps lock on?
        var isCapsLock = (((character >= 65 && character <= 90) && !sftKey) || ((character >= 97 && character <= 122) && sftKey));
        // alert(isCapsLock);
        // Display warning and set css
        if (isCapsLock == true) {
          var parent = $(this).parent();
          parent.addClass('capslock');
          $('.capslockMessage').remove();
          parent.append('<div class="capslockMessage">' + message +  '</div>');
          // alert('Warning: you have turned caps lock on');
          // alert(message);
        }
        else{
          var parent = $(this).parent();
          parent.addClass('capslock');
          $('.capslockMessage').remove();
          parent.append('<div class="capslockMessage">' + 'Caps Lock is off'  +  '</div>');
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
