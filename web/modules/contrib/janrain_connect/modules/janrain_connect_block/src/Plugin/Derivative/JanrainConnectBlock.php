<?php

namespace Drupal\janrain_connect_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Create a Janrain block.
 *
 * @Block(
 *   id = "janrain_connect_block",
 *   admin_label = @Translation("Janrain Connect block"),
 *   category = @Translation("Janrain Connect Block")
 * )
 */
class JanrainConnectBlock extends DeriverBase {

  /**
   * Get Derivative Definitions.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    // Get forms if should be render as block.
    $config = \Drupal::configFactory()->getEditable('janrain_connect.settings');
    $forms_as_block = $config->get('forms_as_block');

    if (!is_array($forms_as_block)) {
      return [];
    }

    foreach ($forms_as_block as $key => $form) {

      if (empty($form)) {
        continue;
      }

      $this->derivatives[$key] = $base_plugin_definition;
      $this->derivatives[$key]['admin_label'] = t('Janrain: @name', [
        '@name' => $form,
      ]);

    }

    return $this->derivatives;
  }

}
