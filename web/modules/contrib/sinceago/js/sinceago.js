/**
 * @file
 */

(function ($) {
    Drupal.behaviors.sinceago = {
        attach: function (context) {
            $.extend($.timeago.settings, drupalSettings.sinceago);
            $('.node-sinceago').timeago();
            // Iterates all comments.
            $(".comment-sinceago").each(function (index) {
              var title = $(this).attr('title');
              $(this).find('.comment__time').attr('title', title);
            });
            $('.comment__time').timeago();
        }
    };
})(jQuery);
