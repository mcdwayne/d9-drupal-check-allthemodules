<?php
/**
 * @file
 * Contains \Drupal\field_presets\Plugin\Derivative\DynamicLocalTasks.
 */

namespace Drupal\field_presets\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $manager = \Drupal::service('entity.manager');
    $config = \Drupal::configFactory()->get('field_presets.settings');
    foreach ($manager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        $derivative_id = 'field_presets.' . $entity_type_id . '.add_field_using_preset';
        $appears_on = ['entity.' . $entity_type_id . '.field_ui_fields'];
        if ($config->get('form_display') === 1) {
          $appears_on[] = 'entity.entity_form_display.' . $entity_type_id . '.default';
        }
        $this->derivatives[$derivative_id] = $base_plugin_definition;
        $this->derivatives[$derivative_id]['title'] = 'Add field using preset';
        $this->derivatives[$derivative_id]['route_name'] = $derivative_id;
        $this->derivatives[$derivative_id]['weight'] = -50;
        $this->derivatives[$derivative_id]['appears_on'] = $appears_on;
      }
    }

    return $this->derivatives;
  }

}
