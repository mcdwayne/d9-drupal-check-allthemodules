// Custom scrolling speed with jQuery
// Version: 1.0.2

(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.mybehavior = {
        attach: function (context, settings) {
            var step = drupalSettings.smooth_scroll.scrool_speed.step;
            var speed = drupalSettings.smooth_scroll.scrool_speed.speed;
            var easing = '';
            var $document = $(document),
            $window = $(window),
            $body = $('html, body'),
            option = 'default',
            root = 0,
            scroll = false,
            scrollY,
            view;

            if (window.navigator.msPointerEnabled)
                return false;

            $window.on('mousewheel DOMMouseScroll', function(e) {
                var deltaY = e.originalEvent.wheelDeltaY,
                detail = e.originalEvent.detail;
                scrollY = $document.height() > $window.height();
                scroll = true;

            if (scrollY) {
                view = $window.height();
                if (deltaY < 0 || detail > 0)
                    root = (root + view) >= $document.height() ? root : root += step;
                
                if (deltaY > 0 || detail < 0)
                    root = root <= 0 ? 0 : root -= step;
                
                $body.stop().animate({
                    scrollTop: root
                }, speed, option, function() {
                    scroll = false;
                });
            }
            return false;
            
            }).on('scroll', function() {
                if (scrollY && !scroll) root = $window.scrollTop();
            }).on('resize', function() {
                if (scrollY && !scroll) view = $window.height();
            });
        }
    };

    jQuery.easing.default = function (x,t,b,c,d) {
        return -c * ((t=t/d-1)*t*t*t - 1) + b;
    };

})(jQuery, Drupal, drupalSettings);



