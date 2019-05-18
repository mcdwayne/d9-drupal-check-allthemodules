/**
 * @file
 */

(function ($) {

    Drupal.behaviors.NiceImageFieldWidget = {
        attach: function (context, settings) {

            $("div.nice-imagefield-sortable").each(function () {
                $(this).sortable({
                    items: '> div',
                    opacity: 0.7,
                    placeholder: "ui-sortable-placeholder",
                    revert: 100,
                    stop: function () {
                        nice_imagefield_widget_sort(this);
                    }
                });

                $(this).disableSelection();
            });

            function nice_imagefield_widget_sort(wrapper) {
                var i = 0;
                $('div.nice-imagefield-weight select', wrapper).each(function () {
                    $(this).find("option").removeAttr("selected");
                    $(this).find("option[value='" + i + "']").attr("selected", "selected");
                    i++;
                });
            }

            $(".nice-imagefield-card:not(.processed)").each(function () {
                $(this).flip({
                    axis: 'x',
                    trigger: 'manual'
                });

                var element = $(this);
                element.find('.flip-back').click(function (event) {
                    event.preventDefault();

                    $(".nice-imagefield-card").each(function () {
                        $(this).flip(false);
                    });

                    element.flip(true);
                });

                element.find('.flip-front').click(function (event) {
                    event.preventDefault();

                    element.flip(false);
                });
            }).addClass('processed');

        }
    };

})(jQuery);
