(function ($) {
    $.colorWidget = {
        init: function () {
            var fieldsets = $('fieldset.color-picker-radio-class');

            fieldsets.each(function (i, el) {
                var fieldset = $(el),
                    labels = fieldset.find('label');
                labels.each(function (i, label) {
                    label = $(label);
                    if (!label.hasClass('initialized')) {
                        var input = $('#' + label.attr('for'));
                        label.css('background-color', label.text());

                        if (input.val() === '') {
                            label.addClass('non-required-value');
                        }

                        if (input.attr('checked') === 'checked') {
                            label.addClass('checked');
                        }

                        label.on('click', function (e) {
                            fieldset.find('input[type="radio"]').removeAttr('checked');
                            fieldset.find('label').removeClass('checked');
                            input.attr('checked', 'checked');
                            label.addClass('checked');
                        });

                        label.addClass('initialized');
                    }
                });
            });
        }
    };

    Drupal.behaviors.color_widget = {
        attach: function (context, settings) {
            $.colorWidget.init();
        }
    };
})(jQuery, Drupal);