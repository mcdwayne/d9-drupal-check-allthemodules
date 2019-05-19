/**
 * @file
 * Javascript for Social Profile Field.
 */

/**
 * Show Social Network icon depending on URL.
 */
(function ($, Drupal, drupalSettings) {
    "use strict";
    Drupal.behaviors.socialProfileField = {
        attach: function (context) {
            $(context).on("change" ,".edit-field-social-profile-url" , function (event) {
                var $this = $(this),
                    $wrapper = $this.parent('.form-type-url'),
                    domain;

                if ($this.val()) {
                    domain = $.url($this.val()).attr('host').replace('.', '-').replace('.', '-');
                }

                if (domain) {
                    $wrapper.attr('rel', domain).addClass(domain);
                } else {
                    domain = $wrapper.attr('rel');
                    if (domain) {
                        $wrapper.removeAttr('rel').removeClass(domain);
                    }
                }
            });
            $(context).find(".edit-field-social-profile-url").change();
        }
    };
})(jQuery, Drupal, drupalSettings);
