/**
 * @file
 * Author: Synpase-studio.
 */

(function ($) {
  $(document).ready(function () {
    console.log('init fingerprint');
    var canvas = getCanvasFpCustom();
    $('#fingerprint').html('<b>hash:</b>' + canvas.hash);
    $('#fingerimage').html('<br><img src="' + canvas.data + '"/>');
    $('#fingerdata').html("<div style='word-break: break-all'>" + canvas.data + "</div>");
  });
})(this.jQuery);
