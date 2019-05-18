(function($, Drupal) {

  Drupal.AjaxCommands.prototype.clearContextual = function(ajax, response, status){
    if (sessionStorage.length > 0) {
      var item = null;
      var itemID = null;

      for (var i = 0; i < sessionStorage.length; i++) {
        itemID = sessionStorage.key(i);
        item = sessionStorage.getItem(itemID);
        if (itemID.indexOf("Drupal.contextual.") != -1) {
          sessionStorage.removeItem(itemID);
        }
      }
    }
  }
})(jQuery, Drupal);
