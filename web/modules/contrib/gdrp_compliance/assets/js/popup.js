/**
 * @file
 * GDRP cookie agreement js.
 */

(function ($) {
  $(document).ready(function () {
    var $gdrpAgree = $.cookie('gdrp_compliance');
    var $gdrpPopup = $('#gdrp-popup');

    if ($gdrpAgree !== 'agreed') {
      $gdrpPopup.show();
    }
    $('#gdrp-agree').click(function () {
      $gdrpPopup.fadeOut();
      $.cookie('gdrp_compliance', 'agreed', {path: '/', expires: 30});
    });
    $('#gdrp-find-more').click(function () {
      // TODO: for (var c in $.cookie()) {$.removeCookie(c, { path: '/' });} //.
      $.cookie('gdrp_compliance', 'morelink', {path: '/', expires: 30});
      // Go to rules page.
      var $path = $(this).data('morelink');
      if ($path.substring(0, 4) === 'http') {
        window.open($path);
      }
      if ($path.substring(0, 1) === '/') {
        window.open(window.location.origin + $path);
      }
    });
  });
})(this.jQuery);
