(function($) {
  $(document).ready(function () {
    $(document).off('click', '.c-dropdown__trigger')
      .on('click', '.c-dropdown__trigger', function (e) {
        e.stopPropagation();
        $(this).parent().toggleClass('is-open');
      });

    $(document).off('click', '.c-dropdown__content')
      .on('click', '.c-dropdown__content', function (e) {
        e.stopPropagation();
      });

    $(document).off('click', 'body')
      .on('click', 'body', function () {
        $('.c-dropdown').removeClass('is-open');
      });
  });
})(jQuery);
