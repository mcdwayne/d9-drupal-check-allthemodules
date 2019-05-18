(function($) {
  $(document).ready(function () {
    $(document).off('blur','.c-form-text-item__field')
      .on('blur', '.c-form-text-item__field', function () {

        if ($(this).val()) {
          $(this).addClass('is-not-empty');
        } else {
          $(this).removeClass('is-not-empty');
        }
      });
  });
})(jQuery);
