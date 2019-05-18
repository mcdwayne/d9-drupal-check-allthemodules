/**
 * @file
 * Defines Javascript behaviors for the imagefield_slideshow module.
 */
(function($, Drupal, drupalSettings) {

    'use strict';

    $(document).ready(function() {

        // If Before/After setting is enabled.
        if (drupalSettings.beforeafter.prev_next) {
            jQuery(".beforeafter").find('img:eq(1)').hide();
            jQuery('.image-before').click(function() {
                jQuery(this).parents('.beforeafter-wrapper').find('img:eq(0)').show();
                jQuery(this).parents('.beforeafter-wrapper').find('img:eq(1)').hide();
                jQuery(this).siblings().removeClass('active')
                jQuery(this).addClass('active');
            });
            jQuery('.image-after').click(function() {
                jQuery(this).parents('.beforeafter-wrapper').find('img:eq(1)').show();
                jQuery(this).parents('.beforeafter-wrapper').find('img:eq(0)').hide();
                jQuery(this).siblings().removeClass('active')
                jQuery(this).addClass('active');
            });
        }

    });

})(jQuery, Drupal, drupalSettings);