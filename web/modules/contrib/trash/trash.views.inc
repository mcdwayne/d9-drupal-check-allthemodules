<?php

/**
 * Implements hook_views_data().
 */
function trash_views_data() {
  // @todo On the longrun this should be in content_moderation.
  $data['content_moderation_state_field_data']['entity_label'] = [
    'title' => t('Entity label'),
    'field' => [
      'id' => 'trash_content_moderation_state_entity_label',
    ],
  ];

  $data['content_moderation_state_field_data']['entity_operations'] = [
    'title' => t('Entity operations'),
    'field' => [
      'id' => 'trash_content_moderation_state_entity_operations',
    ],
  ];

  return $data;
}
