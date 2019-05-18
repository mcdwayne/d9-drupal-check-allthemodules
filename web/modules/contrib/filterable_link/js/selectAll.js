(function($, Drupal) {
    "use strict";

    Drupal.behaviors.selectAll = {
        attach: function(context) {
            const $context = context;
            const $bundle_types = $(
                "#edit-settings-bundle-types .form-item",
                $context
            );
            let $selectAll = $("#edit-settings-bundle-types-all");

            $selectAll.once("selectAll").on("click", function() {
                if ($(this).prop("checked")) {
                    $bundle_types
                        .find('input[type="checkbox"]')
                        .prop("checked", true)
                        .trigger("change");
                } else {
                    $bundle_types
                        .find('input[type="checkbox"]')
                        .prop("checked", false)
                        .trigger("change");
                }
            });
        }
    };
})(jQuery, Drupal);
