<?php

/**
 * @file
 * Preprocessors and theme functions of Context Manager UI module.
 */

/**
 * Prepares variables for add/edit ruleset form template.
 *
 * Default template: context-manager-wizard-menu.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - wizard: An object of CTools multistep wizard.
 *   - cached_values: Array of values submitted through the form.
 *   - items: Array of links to display in the menu.
 */
function template_preprocess_context_manager_wizard_menu(&$variables) {

  /** @var $wizard \Drupal\ctools\Wizard\FormWizardInterface|\Drupal\ctools\Wizard\EntityFormWizardInterface */
  $wizard = $variables['wizard'];
  $cached_values = $variables['cached_values'];
  $variables['step'] = $wizard->getStep($cached_values);

  $ruleset_items = [];
  foreach ($wizard->getOperations($cached_values) as $step => $operation) {
    $ruleset_items[$step] = !empty($operation['title']) ? $operation['title'] : '';
  }
  $variables['ruleset_items'] = $ruleset_items;
}
