<?php

namespace Drupal\flexiform_wizard\Plugin\FormEnhancer;

use Drupal\flexiform\FormEnhancer\FormEnhancerBase;

/**
 * Plugin for adding Wizard Parameters into the Flexiform entity manager.
 *
 * @FormEnhancer(
 *   id = "wizard_parameters",
 *   label = @Translation("Wizard Parameters"),
 * )
 */
class WizardParameters extends FormEnhancerBase {

  /**
   * {@inheritdoc}
   */
  public function applies($event) {
    if ($event != 'init_form_entity_config') {
      return FALSE;
    }

    return substr($this->getFormDisplay()->id(), 0, 17) == 'flexiform_wizard.';
  }

  /**
   * Initialise the enhancer config..
   *
   * @return array
   *   The initial config for the enhancer.
   */
  public function initFormEntityConfig() {
    list(, $wizard_id, $step) = explode('.', $this->getFormDisplay()->id(), 3);
    $wizard = entity_load('flexiform_wizard', $wizard_id);

    $form_entity_settings = [];
    foreach ($wizard->get('parameters') as $param_name => $param_info) {
      $form_entity_settings[$param_name] = [
        'entity_type' => $param_info['entity_type'],
        'bundle' => $param_info['bundle'],
        'plugin' => 'provided',
        'label' => $param_info['label'],
      ];
    }

    return $form_entity_settings;
  }

}
