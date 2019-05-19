
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.initTacJS = {

    /**
     * Attach Drupal behaviors.
     *
     * @param context Element|jQuery The current execution context
     * @param settings Object The Drupal settings
     */
    init: function init(context, settings) {
      console.log(drupalSettings);
      tarteaucitron.init({
        "hashtag": context,
        "cookieName": drupalSettings.options_tacjs.cookie_name,
        "highPrivacy": this.verify(drupalSettings.options_tacjs.high_privacy),
        "orientation": drupalSettings.options_tacjs.orientation,
        "adblocker": this.verify(drupalSettings.options_tacjs.adblocker),
        "showAlertSmall":this.verify(drupalSettings.options_tacjs.show_alertSmall),
        "cookieslist": this.verify(drupalSettings.options_tacjs.cookieslist),
        "removeCredit": this.verify(drupalSettings.options_tacjs.removeCredit),
        "handleBrowserDNTRequest": this.verify(drupalSettings.options_tacjs.handleBrowserDNTRequest)
        //"cookieDomain": ".example.com" /* Nom de domaine sur lequel sera posé le cookie pour les sous-domaines */
      });
    },
    verify : function verify(value){
      if(value === 'true'){
        return true;
      }
        return false;
    },

  };
  Drupal.behaviors.initTacJS.init('#tarteaucitron');
})(jQuery, Drupal, drupalSettings);

