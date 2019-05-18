
(function ($) {

    Drupal.behaviors.parallax = {
        attach: function() {

            $.each(drupalSettings.parallax_blur_bg.tags, function( index, value ) {
                $(value).wrapAll( '  <div class="parallax-blur-bg" id="parallax-blur-bg-' + index +'"><div class="parallax-blur-bg-container"></div></div>' );
                $('head').append('<style>#parallax-blur-bg-'+index+':before{background-image:url('+drupalSettings.parallax_blur_bg.small_bg_url[index]+')}</style>');
            });

            $(window).on('load', function () {
                $.each(drupalSettings.parallax_blur_bg.tags, function( index, value ) {
                    $('#parallax-blur-bg-'+index).css('background-image', 'url(' + drupalSettings.parallax_blur_bg.large_bg_url[index] + ')');
                    $('head').append('<style>#parallax-blur-bg-'+index+':before{opacity: 0}</style>');
                });

            });
        }};

}(jQuery));
