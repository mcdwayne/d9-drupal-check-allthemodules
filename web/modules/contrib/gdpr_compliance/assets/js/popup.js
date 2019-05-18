/**
 * @file
 * Gdpr cookie agreement js.
 */

(function ($) {
  $(document).ready(function () {
    var $gdprAgree = $.cookie('gdpr_compliance');
    var $gdprPopup = $('#gdpr-popup');

    if ($gdprAgree !== 'agreed') {
      $gdprPopup.show();
    }
    $('#gdpr-agree').click(function () {
      $gdprPopup.fadeOut();
      $.cookie('gdpr_compliance', 'agreed', {path: '/', expires: 30});
    });
    $('#gdpr-find-more').click(function () {
      $.cookie('gdpr_compliance', 'morelink', {path: '/', expires: 30});
      // Go to rules page.
      var $path = $(this).data('morelink');
      if ($path.substring(0, 4) === 'http') {
        window.open($path);
      }
      if ($path.substring(0, 1) === '/') {
        window.open(window.location.origin + $path);
      }
    });
    $('#gdpr-clear-cookie').click(function () {
      for (var c in $.cookie()) {
        $.removeCookie(c, { path: '/' });
      }
      if ($(this).data('done')) {
        alert($(this).data('done'));
      }
      else {
        alert('ok');
      }
    });
  });
})(this.jQuery);
