(function($) {
  $(document).ready(function() {
    $(document).off('click', '.snack-bar-trigger')
      .on('click', '.snack-bar-trigger', function () {
        $('.c-snack-bar').removeClass('is-animated');
        var status = $(this).data('status');
        $('.c-snack-bar[data-status="' + status + '"]').addClass('is-animated');
        setTimeout(function() {
          $('.c-snack-bar[data-status="' + status + '"]').removeClass('is-animated');
        }, 3000);
      });
  });
})(jQuery);
