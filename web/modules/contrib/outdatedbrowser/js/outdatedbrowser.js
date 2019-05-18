/**
 * @file
 * Initializes Outdated Browser library.
 */

(function (Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.initOutdatedbrowser = {
    attach: function (context) {
      if (!typeof outdatedBrowser == 'function' || typeof drupalSettings.outdatedbrowser === 'undefined') {
        return;
      }

      if(jQuery.cookie("outdated") == 'closed') {
        //stop the script - do not show the outdated browser message as it has been closed
        return;  
      }
          
      outdatedBrowser({
        bgColor: drupalSettings.outdatedbrowser.bgColor,
        color: drupalSettings.outdatedbrowser.color,
        lowerThan: drupalSettings.outdatedbrowser.lowerThan,
        languagePath: drupalSettings.outdatedbrowser.languagePath
      });
      
      var add_button_mousedown = function() {
         if(jQuery("#btnCloseUpdateBrowser").length == 0){
           setTimeout(add_button_mousedown, 1000);
           return;
         }
         jQuery("#btnCloseUpdateBrowser").on('mousedown', function(){
           //drop the cookie so we know the message has been closed
           jQuery.cookie("outdated",'closed'); 
         });
      }
      //let's wait while the button appears...      
      setTimeout(add_button_mousedown, 1000);
       
    }
  };
})(Drupal, drupalSettings);
