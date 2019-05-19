(function ($) {
    Drupal.behaviors.form = {
        attach: function (context, settings) {
          var checkbox = $('.variations-container [class^=smart-variations-default-]', context);
          checkbox.change(function () {
              if ($(this).is(':checked')) {
                  $(checkbox).each(function () {
                     if (!$(this).is(':checked')) {
                          $(this).attr('disabled', true);
                       }
                    });
                } else {
                  $(checkbox).each(function () {
                      $(this).attr('disabled', false);
                    });
                }
            });
       }
    }
})(jQuery);