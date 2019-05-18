(function ($, window) {

    "use strict";
    // @todo Is it possible to use Block module's verion of this function?
    function checkboxesSummary (context) {
        var vals = [];
        var $checkboxes = $(context).find('input[type="checkbox"]:checked + label');
        for (var i = 0, il = $checkboxes.length; i < il; i += 1) {
            vals.push($($checkboxes[i]).text());
        }
        if (!vals.length) {
            vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
    }

    /**
     * Provide the summary information for the block settings vertical tabs.
     */
    Drupal.behaviors.entityBlockVisibilitySettingsSummary = {
        attach: function () {
            // The drupalSetSummary method required for this behavior is not available
            // on the Blocks administration page, so we need to make sure this
            // behavior is processed only if drupalSetSummary is defined.
            if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
                return;
            }
            $('#edit-visibility-bundles').drupalSetSummary(checkboxesSummary);
        }
    };

})(jQuery, window);
