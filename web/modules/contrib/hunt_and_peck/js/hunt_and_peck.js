(($, Drupal) => {
  Drupal.behaviors.huntAndPeck = {
    attach() {
      $('input:not([type="button"])').on('keyup', ({ currentTarget }) => {
        var _timeout = Math.floor(Math.random() * 3000) + 500;
        var _this = $(currentTarget);
        _this.prop('disabled', true);
        setTimeout(() => {
          _this.prop('disabled', false);
        }, _timeout);
      })
    }
  };
})(jQuery, Drupal)
