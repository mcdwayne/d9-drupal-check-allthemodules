<?php

/**
 * @file
 * Contains \Drupal\registration_types\Plugin\Derivative;\RegistrationTypesLocalTasks.
 */

namespace Drupal\registration_types\Plugin\Derivative;

use Drupal\registration_types\Entity\RegistrationType;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks.
 */
class RegistrationTypesLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $types = RegistrationType::loadMultiple();
    foreach ($types as $machine_name => $type) {
      if (!$type->getEnabled() || !$type->getTabTitle()) {
        continue;
      }
      $this->derivatives[$machine_name] = [];
      $this->derivatives[$machine_name]['title'] = t($type->getTabTitle());
      $this->derivatives[$machine_name]['base_route'] = 'user.page';
      $this->derivatives[$machine_name]['route_name'] = 'registration_types.' . $machine_name;

      $this->derivatives[$machine_name] += ['cache_tags' => []];
      $this->derivatives[$machine_name]['cache_tags'] += ['registration_type'];
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
