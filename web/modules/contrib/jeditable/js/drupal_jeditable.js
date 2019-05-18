/**
 * @file
 * Triggers editable function from min.js.
 */

(function ($) {
    Drupal.behaviors.jeditable = {
        attach: function (context) {
            $('.jeditable-textfield', context).editable('/jeditable/ajax/save', {
                indicator : 'Saving...',
                tooltip   : 'Click to edit...',
                cancel    : 'Cancel',
                submit    : 'Save'
            });
        }
    };
})(jQuery);
