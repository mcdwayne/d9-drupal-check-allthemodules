(function ($, Drupal, drupalSettings) {

    'use strict';

    /**
     * Simple SEO preview field display.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.simpleSeoPreview = {
        attach: function (context) {
            var titleMaxChar,
                descriptionMaxChars,

                livePreview = function (e, ep, length) {
                    ep.text(e.val());

                    if (ep.text().length <= length) {
                        ep.text(e.val());
                    }
                    else {
                        ep.text(e.val().substring(0, length) + ' ...');
                    }
                };

            titleMaxChar = drupalSettings.simple_seo_preview.title_max_char;
            $(context).find('.js--simple_seo_preview-title').each(function () {
                $(this).on('keyup', function () {
                    livePreview($(this), $('.js--simple_seo_preview').find('.title'), titleMaxChar);
                });
            });

            descriptionMaxChars = drupalSettings.simple_seo_preview.description_max_chars;
            $(context).find('.js--simple_seo_preview-description').each(function () {
                $(this).on('keyup', function () {
                    livePreview($(this), $('.js--simple_seo_preview').find('.description'), descriptionMaxChars);
                });
            });
        }
    };
})(jQuery, Drupal, drupalSettings);
