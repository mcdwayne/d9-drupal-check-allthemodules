<?php

/**
 * @file Views hook implementations for the Type Style Moderation module.
 */

/**
 * Implements hook_views_data_alter().
 */
function type_style_moderation_views_data_alter(array &$data) {
  foreach ($data as $base_table => $values) {
    if (isset($values['moderation_state'])) {
      $data[$base_table]['moderation_state_type_style_color'] = [
        'title' => t('Moderation state color'),
        'help' => t('Displays the moderation state color.'),
        'field' => [
          'id' => 'type_style_moderation',
        ],
      ];
      $data[$base_table]['moderation_state_type_style_icon'] = [
        'title' => t('Moderation state icon'),
        'help' => t('Displays the moderation state icon name.'),
        'field' => [
          'id' => 'type_style_moderation',
        ],
      ];
    }
  }
}
