(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.pretty_options = {
    /**
     * Drupal attach behavior.
     */
    attach: function(context, settings) {

        $('.pretty-element').each(function () {

            // Settings
            var $widget = $(this),
                $button = $widget.find('button'),
                $checkbox = $widget.find('input:checkbox'),
                color = $button.data('color'),
                settings = {
                    on: {
                        icon: 'pe-glyphicon pe-glyphicon-check'
                    },
                    off: {
                        icon: 'pe-glyphicon pe-glyphicon-unchecked'
                    }
                };

            // Event Handlers
            $button.on('click', function () {
                $checkbox.prop('checked', !$checkbox.is(':checked'));
                $checkbox.triggerHandler('change');
                updateDisplay();
            });

            $checkbox.on('change', function () {
                updateDisplay();
            });

            // Actions
            function updateDisplay() {
                var isChecked = $checkbox.is(':checked');

                // Set the button's state
                $button.data('state', (isChecked) ? "on" : "off");

                // Set the button's icon
                $button.find('.state-icon')
                    .removeClass()
                    .addClass('state-icon ' + settings[$button.data('state')].icon);

                // Update the button's color
                if (isChecked) {
                    $button
                        .removeClass('btn-pe-default')
                        .addClass('btn-pe-' + color);
                }
                else {
                    $button
                        .removeClass('btn-pe-' + color)
                        .addClass('btn-pe-default');
                }
            }

            // Initialization
            function init() {

                updateDisplay();

                // Inject the icon if applicable
                if ($button.find('.state-icon').length == 0) {
                    $button.prepend('<i class="state-icon ' + settings[$button.data('state')].icon + '"></i> ');
                }
            }
            init();
        });

    }

};
})(jQuery, Drupal, drupalSettings);
