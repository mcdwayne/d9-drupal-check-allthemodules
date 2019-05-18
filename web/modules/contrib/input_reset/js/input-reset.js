(function ($) {
  'use strict';

  $.fn.clearSearch = function (options) {
    var settings = $.extend({
      clearClass: 'clearable-input',
      focusAfterClear: true
    }, options);
    return this.each(function () {
      var $this = $(this);
      var btn;
      var divClass = settings.clearClass + '_div';

      if (!$this.parent().hasClass(divClass)) {
        $this.wrap('<div class="' +
          divClass + '">' + $this.html() + '</div>');
        $this.after('<span class="data-clear-input">x</span>');
      }
      btn = $this.next();

      function clearField() {
        $this.val('').change();
        triggerBtn();
        if (settings.focusAfterClear) {
          $this.focus();
        }
        if (typeof (settings.callback) === 'function') {
          settings.callback();
        }
      }

      function triggerBtn() {
        if (hasText()) {
          btn.show();
        }
        else {
          btn.hide();
        }
      }

      function hasText() {
        return $this.val().replace(/^\s+|\s+$/g, '').length > 0;
      }

      btn.on('click', clearField);
      $this.on('keyup keydown change focus', triggerBtn);
      triggerBtn();
    });
  };
  $('.clearable_input').clearSearch();
})(jQuery);
