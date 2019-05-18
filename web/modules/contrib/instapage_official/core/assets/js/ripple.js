(function($) {
  $(document).ready(function() {
    $(document).off('click', '.fx-ripple-effect')
      .on('click', '.fx-ripple-effect', function(e) {
        var rippler = $(this);

        // create .ink element if it doesn't exist
        if (rippler.find(".fx-ink").length == 0) {
          rippler.append("<span class='fx-ink'></span>");
        }

        var ink = rippler.find(".fx-ink");

        // prevent quick double clicks
        ink.removeClass("fx-animate");

        // set .ink diametr
        if (!ink.height() && !ink.width()) {
          var d = Math.max(rippler.outerWidth(), rippler.outerHeight());
          ink.css({
            height: d,
            width: d
          });
        }

        // get click coordinates
        var x = e.pageX - rippler.offset().left - ink.width() / 2;
        var y = e.pageY - rippler.offset().top - ink.height() / 2;

        // set .ink position and add class .animate
        ink.css({
          top: y + 'px',
          left: x + 'px'
        }).addClass("fx-animate");
      });
  });
})(jQuery);
