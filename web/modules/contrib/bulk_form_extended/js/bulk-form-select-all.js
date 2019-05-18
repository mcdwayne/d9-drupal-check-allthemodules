/**
 * @file
 */

(function ($, Drupal) {
    'use strict';
    Drupal.behaviors.buldFormSelectAll = {
        attach: function (context) {
            $('.js-bulk-form-extended-select-all', context).once('buldFormSelectAll').each(function () {
                jQuery(this).click(function (e) {
                    e.preventDefault();

                    var form = jQuery(this).closest('form');
                    var checkboxes = form.find('[type=checkbox]');

                    var allchecked = (checkboxes.length === form.find('[type=checkbox]:checked').length) ? true : false;

                    if (allchecked) {
                        checkboxes.prop('checked', false);
                        jQuery('.js-bulk-form-extended-select-all').val(jQuery(this).data('select'));
                        return;
                    }

                    // Check all the boxes.
                    checkboxes.prop('checked', 'checked');

                    // Set all checked to true.
                    jQuery(this).data('allchecked', 'true');

                    // Change the button text.
                    jQuery('.js-bulk-form-extended-select-all').val(jQuery(this).data('deselect'));
                });
            });
        }
    }
})(jQuery, Drupal);
