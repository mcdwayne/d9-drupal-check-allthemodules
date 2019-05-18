/**
 * @file
 * Defines Javascript behaviors for the csp module admin form.
 */

(function ($, Drupal) {
  /**
   * Sets summary of policy tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour for policy form tabs.
   */
  Drupal.behaviors.cspPolicySummary = {
    attach(context) {
      $(context)
        .find('[data-drupal-selector="edit-policies"] > details')
        .each(function () {
          const $details = $(this);
          const elementPrefix = $details.data('drupal-selector');
          const createPolicyElementSelector = function (name) {
            return '[data-drupal-selector="' + elementPrefix + '-' + name + '"]';
          };

          $details.drupalSetSummary(function () {
            if ($details.find(createPolicyElementSelector('enable')).prop('checked')) {
              const directiveCount = $details
                .find(createPolicyElementSelector('directives') + ' [name$="[enable]"]:checked')
                .length;
              return Drupal.formatPlural(
                directiveCount,
                'Enabled, @directiveCount directive',
                'Enabled, @directiveCount directives',
                { '@directiveCount': directiveCount },
              );
            }

            return Drupal.t('Disabled');
          });
        });
    },
  };

  /**
   * If upgrade-insecure-requests is enabled, block-all-mixed-content should be
   * forced as disabled.
   *
   * Form states handles disabling the field, but it will be left checked.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.cspBlockAllMixedDisabled = {
    attach(context) {
      let blockState = false;

      $(context)
        .find('input[data-drupal-selector="edit-enforce-directives-upgrade-insecure-requests-enable"]')
        .on('change', function () {
          const blockInput = $(context).find('input[data-drupal-selector="edit-enforce-directives-block-all-mixed-content-enable"]');
          if (!this.checked) {
            blockInput.prop('checked', blockState);
            return;
          }
          blockState = blockInput.prop('checked');
          blockInput.prop('checked', false);
        });
    },
  };
}(jQuery, Drupal));
