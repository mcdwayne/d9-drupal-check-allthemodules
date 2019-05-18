/**
 * @file alertbox_modal.js
 *
 * Defines the behavior of the alertbox modal layout.
 */

(function ($, Drupal, drupalSettings) {

    "use strict";

    Drupal.behaviors.alertbox_modal = {
        attach: function (context, settings) {
            // Modal content.
            var block_title = '';
            var block_content = '';
            var alertbox_ids = [];
            // Get all the alertboxes id's and wrap content for display.
            $('.block-alertbox').each(function () {
                var alertbox_id = $(this).attr('id');
                alertbox_ids.push(alertbox_id);
                // Check cookie status. If cookie set, we don't show the alert.
                var status = Drupal.alertbox.getCurrentStatus(alertbox_id);
                console.log(status);
                if (!status || !drupalSettings.alertbox_modal.alertbox_allow_hide) {
                    block_content += $(this).wrapInner('<div class="block-alertbox-row"></div>').html();
                }
            });
            // If no content to show, we don't have any alerts currently.
            if (block_content == '') {
                return;
            }
            // Set the buttons to display.
            var block_buttons = [{
                text: drupalSettings.alertbox.alertbox_label_close,
                click: function () {
                    $(this).dialog("close");
                }
            }];
            // Add a "Dismiss" button if the "alertbox_allow_hide" option is
            // set.
            if (drupalSettings.alertbox_modal.alertbox_allow_hide) {
                block_buttons.push({
                    text: drupalSettings.alertbox.alertbox_label_dismiss,
                    click: function () {
                        alertbox_ids.forEach(function (alertbox_id) {
                            Drupal.alertbox.setStatus(alertbox_id);
                        });
                        $(this).dialog("close");
                    }
                });
            }
            // Create the modal box with the options defined.
            $('<div></div>').dialog({
                title: block_title,
                dialogClass: "no-close",
                create: function (event, ui) {
                    $('.ui-dialog-content').html(block_content);
                },
                modal: true,
                buttons: block_buttons
            });
        }
    }
})(jQuery, Drupal, drupalSettings);
