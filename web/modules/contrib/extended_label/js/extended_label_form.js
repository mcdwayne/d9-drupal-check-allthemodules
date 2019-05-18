(function($) {
  $(function() {
    $('.form-item .form-required .extended-label-inner').each(function() {
      $(this).find('p').last().append('<span class="extended-required"></span>');
    });
  });
})(jQuery);
