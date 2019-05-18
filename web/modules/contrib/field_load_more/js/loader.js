(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.fieldLoadMoreLoader = {
        attach: function (context) {
            $('.load-more-btn', context).bind('click', function(e) {
                var $parentFieldWrapper = $(this).parents('.field-load-more');
                var parentWrapperClass = $(this).attr('data-field-class');
                var itemLimit = drupalSettings.field_load_more[parentWrapperClass].limit;
                var count = 0;
                $('.field__item.element-invisible', $parentFieldWrapper).each(function() {
                    if (count < itemLimit) {
                        $(this).removeClass('element-invisible');
                    }
                    else {
                        return false;
                    }
                    count++;
                });
                var invisibleItemsCount = $('.field__item.element-invisible', $parentFieldWrapper).length;

                if (invisibleItemsCount === 0) {
                    $(this).addClass('element-invisible');
                }
            });
        }
    }
}(jQuery, Drupal, drupalSettings));