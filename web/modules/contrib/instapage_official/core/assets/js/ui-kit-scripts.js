(function($) {
  $(document).ready(function() {
    // uikit menu
    var titles = $('main > .ui-section .ui-title');
    var uiMenu = $('#ui-menu');
    var uiMenuContent = '';
    var title;
    var toggle_menu = $('#show-menu');

    titles.each(function(i, e){
      uiMenuContent += '<li data-id="'+i+'">' + (i+1) + '. ' + $(e).text() + '</li>';
    });

    uiMenu.append(uiMenuContent);

    title = uiMenu.find('li');

    title.on('click', function(){
      var thiz = $(this);
      var thizSection = thiz.data('id');

      title.removeClass('is-active');
      thiz.addClass('is-active');

      $('html, body').animate({scrollTop: $('#sections > li').eq(thizSection).offset().top});
    });

    $('.ui-toggle-menu').on('click', function(){
      uiMenu.toggleClass('is-active');
      title.removeClass('is-active');
    });
  });
})(jQuery);
