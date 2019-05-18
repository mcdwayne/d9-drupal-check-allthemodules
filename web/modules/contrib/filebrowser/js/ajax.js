(function ($, window, Drupal, drupalSettings) {

    'use strict';

    /**
     * Command to Slide Down page elements before removing them.
     *
     * @param {Drupal.Ajax} [ajax]
     * @param {object} response
     * @param {string} response.selector
     * @param {string} response.duration
     * @param {object} [response.settings]
     * @param {number} [status]
     */
    Drupal.AjaxCommands.prototype.slideDown = function(ajax, response, status){
        // Get duration if sent, else use default of slow.
        var duration = response.duration ? response.duration : "slow";
        // slide down the selected element(s).
        $(response.selector).slideDown(duration);
    }

    /**
     * Drupal behavior to remove a log element when it is closed.
     *
     * @type {{attach: Function}}
     */
    Drupal.behaviors.ajaxDbLog = {
        attach: function(context, settings) {
            // Find all event close buttons and attach to click event.
            $(context).find('.filebrowser-close-window-link').on('click', function(event){
                // prevent the default action.
                event.preventDefault();
                // Get event-id (wid).
                //var wid = $(this).data('event-id');
                // Hide details for the event.
                $('.form-in-slide-down').slideUp("slow", function() {
                    // Remove the wid details (copied from the core remove command).
                   // $('#dblog-event-row-' + wid).remove();
                });
            });
        }
    }


})(jQuery, this, Drupal, drupalSettings);