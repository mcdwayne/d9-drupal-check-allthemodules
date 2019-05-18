(function($) {
  /**
   * @file
   * JS support for favorites.
   */

  // $('#myfavlist span a').on('click', function (e){
  $(document).on('click', '#myfavlist span a', function(e) {
    var url = $(this).attr('href');
    $.ajax({
      url: url,
      dataType: 'json',
      success: function(data){
        $('span#' + data.list).closest('li').remove();
      }
    });
    e.preventDefault();
  });
})(jQuery);
