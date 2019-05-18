(function($) {
    $(document).ready(function() {
        if (window.Modernizr.objectfit) {
            return;
        }
        /* IE manual replacement for "object-fit: cover" */
        $('a.instagram-link').each(function () {
            var $container = $(this),
                imgUrl = $container.find('img').prop('src');
            if (imgUrl) {
              $container
                .css('backgroundImage', 'url(' + imgUrl + ')')
                .addClass('compat-object-fit');
            }
        });
    });
})(jQuery);