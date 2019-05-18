/**
 * @file
 * Contains js for the copy .
 */

(function ($, Drupal) {
    'use strict';
    Drupal.behaviors.myModuleBehavior = {
        attach: function (context, settings) {
            $('.menu-twig-command').on('click', function (event) {
                event.preventDefault();
                var $temp = $("<input>");
                $(this).append($temp);
                $temp.val($(this).parents('tr').find('.command').text()).select();
                document.execCommand("copy");
                $temp.remove();
                var $this = this;
                $($this).text("Copied").fadeTo('slow', '0.5');
                setTimeout(function () {
                    $($this).fadeTo('fast','1').text('Copy');
                }, 900);
            });
        }
    };
})(jQuery, Drupal);
