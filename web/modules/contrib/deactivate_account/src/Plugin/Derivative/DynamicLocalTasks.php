<?php

namespace Drupal\deactivate_account\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Implement dynamic logic to provide values for the same keys as in example.links.task.yml.
    $this->derivatives['deactivate_account.form'] = $base_plugin_definition;
    $this->derivatives['deactivate_account.form']['title'] = "Deactivate Account";
    $this->derivatives['deactivate_account.form']['route_name'] = 'deactivate_account.form';
    if (\Drupal::config('deactivate_account.settings')->get('deactivate_account_tab')) {
      $this->derivatives['deactivate_account.form']['base_route'] = 'entity.user.canonical';
      $this->derivatives['deactivate_account.form']['weight'] = 10;
    }
    return $this->derivatives;
  }
}
