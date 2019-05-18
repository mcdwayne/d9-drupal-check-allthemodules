/**
 * Implements ajax block update procedure.
 */

(function($, Drupal) {

    /**
     * Implements ajax block behaviour.
     */
    Drupal.behaviors.blockAjax = {
        attach: function (context, drupalSettings) {
            var ajaxHandler = function ($block) {
                var blockId = $block.data('block-id');

                $.ajax({
                    type: 'POST',
                    url: Drupal.url('block/ajax'),
                    data: drupalSettings.ajaxBlocks[blockId],
                    dataType: 'json',
                    success: function (data) {
                        if (typeof data.content !== 'string' || !data.content.length) {
                            return;
                        }

                        // Compose a new block content, replacing the old one
                        // with it.
                        var $newBlock = $(data.content);
                        $block.replaceWith($newBlock);

                        // Attach behaviours to the updated element.
                        $newBlock.each(function () {
                            // Note that the global drupal settings reference is being used to
                            // make sure the actualised global state is being used.
                            Drupal.attachBehaviors(this, window.drupalSettings);
                        });
                    }
                });
            };

            // Initialise ajax refresh event handlers.
            $('[data-block-id]').once('ajax-block').each(function () {
                $(this).on('RefreshBlock', function () {
                    // Execute the handler payload.
                    ajaxHandler($(this));
                });
            });
        }
    };

    /**
     * Implements ajax block refreshing command.
     */
    Drupal.AjaxCommands.prototype.refreshBlockAjaxCommand = function (ajax, response, status) {
        $(response.selector).trigger('RefreshBlock');
    };

})(jQuery, Drupal);
