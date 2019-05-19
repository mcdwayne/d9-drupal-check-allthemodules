<?php

/**
 * @file Views hook implementations for the Type Style module.
 */

use Drupal\Core\Config\Entity\ConfigEntityType;

/**
 * Implements hook_views_data_alter().
 */
function type_style_views_data_alter(array &$data) {
  $definitions = \Drupal::entityTypeManager()->getDefinitions();
  foreach ($definitions as $definition) {
    if ($definition instanceof ConfigEntityType && $type = $definition->getBundleOf()) {
      $label = $definition->getLabel();
      $base_table = $definitions[$type]->getBaseTable();
      $data[$base_table]['type_style'] = [
        'title' => t('@label custom style', ['@label' => $label]),
        'help' => t('Outputs a custom @label style.', ['@label' => $label]),
        'field' => [
          'id' => 'type_style',
        ],
      ];
      $data[$base_table]['type_style_color'] = [
        'title' => t('@label color', ['@label' => $label]),
        'help' => t('Displays the @label color.', ['@label' => $label]),
        'field' => [
          'id' => 'type_style',
        ],
      ];
      $data[$base_table]['type_style_icon'] = [
        'title' => t('@label icon', ['@label' => $label]),
        'help' => t('Displays the @label icon name.', ['@label' => $label]),
        'field' => [
          'id' => 'type_style',
        ],
      ];
    }
  }
}
