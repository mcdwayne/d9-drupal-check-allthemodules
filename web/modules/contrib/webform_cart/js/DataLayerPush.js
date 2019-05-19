(function ($, Drupal) {
Drupal.AjaxCommands.prototype.DataLayerPush = function (ajax, response, status) {
  var pushAttr = response.data;
  pushAttr = pushAttr.replace(/'/g, '"');
  var pushAttrObj = JSON.parse(pushAttr);
  // For debugging purposes.
  window.dataLayer.push(pushAttrObj);
  // console.log(pushAttrObj);
}
})(jQuery, Drupal);