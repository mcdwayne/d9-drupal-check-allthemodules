(function($){
    $('.field--widget-md-starrating .starrating').each(function(){
        var $field = $(this).closest('.field--widget-md-starrating'),
            $field_title = $('.md-title-rate', $field),
            icon_color  = $field_title.attr('data-color'),
            icon_type = $field_title.attr('data-icon-type'),
            icon_on = icon_type + icon_color + '-on',
            icon_off = icon_type + '-off',
            rate;
            
        $('.rate-image', this).hover(function(){
            $(this).removeClass(icon_off).addClass(icon_on).prevAll('.rate-image').removeClass(icon_off).addClass(icon_on);
             $(this).nextAll('.rate-image').removeClass(icon_on).addClass(icon_off);
        }, function(){});
        $('.rate-image', this).click(function(){
            rate = $(this).index() + 1;
            $('.md-rate-item', $field).val(rate);
        });
    });
})(jQuery);
