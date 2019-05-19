(function ($) {

    /**
     * Provide the summary information for the tracking settings vertical tabs.
     */
    Drupal.behaviors.trackingSettingsSummary = {
        attach: function (context) {
            // Make sure this behavior is processed only if drupalSetSummary is defined.
            if (typeof jQuery.fn.drupalSetSummary == 'undefined') {
                return;
            }

            $('fieldset#edit-messagetracking', context).drupalSetSummary(function (context) {
                var vals = [];
                $('input[type="checkbox"]:checked', context).each(function () {
                    vals.push($.trim($(this).next('label').text()));
                });
                if (!vals.length) {
                    return Drupal.t('Not tracked');
                }
                return Drupal.t('@items enabled', {'@items': vals.join(', ')});
            });
        }
    };

})(jQuery);
