(function($, Drupal, drupalSettings) {
    Drupal.behaviors.counter = {
        attach: function (context, settings) {
            $('.counter').each(function() {
                var $this = $(this),
                    countTo = $this.attr('data-count');

                $({ countNum: $this.text()}).animate({
                        countNum: countTo
                    },
                    {
                        duration: drupalSettings.counter_attributes.duration,
                        easing: drupalSettings.counter_attributes.easing_style,
                        step: function() {
                            $this.text(Math.floor(this.countNum));
                        },
                        complete: function() {
                            $this.text(this.countNum);
                        }

                    });
            });
        }
    }
})(jQuery, Drupal, drupalSettings);
