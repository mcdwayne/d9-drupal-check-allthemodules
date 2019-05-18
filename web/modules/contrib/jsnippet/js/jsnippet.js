/**
 * @file
 * Drupal JSnippet module.
 */

(function ($, Drupal) {

    /**
     * Attaches or detaches behaviors, except the ones we do not want.
     *
     * @param {string} action
     *   Either 'attach' or 'detach'.
     * @param {HTMLDocument|HTMLElement} context
     *   The context argument for Drupal.attachBehaviors()/detachBehaviors().
     * @param {object} settings
     *   The settings argument for Drupal.attachBehaviors()/detachBehaviors().
     */
    Drupal.behaviors.JSnippetBehaviors = {
        attach: function () {
            var behavior = $('input[name=behavior').attr('checked');
            if ($('select#edit-scope').val() != 'footer') {
                $('input[name=behavior]').attr('disabled', true);
            }

            $('select#edit-scope').on('change', function() {
                var selected = this.value;
                if (selected != 'footer') {
                    $('input[name=behavior').prop('checked', false);
                    $('input[name=behavior]').attr('disabled', true);
                }
                else {
                    if (behavior == 'checked') {
                        $('input[name=behavior]').prop('checked', true);
                    }
                    $('input[name=behavior]').removeAttr('disabled');
                }
            });
        }
    };

})(jQuery, Drupal);
