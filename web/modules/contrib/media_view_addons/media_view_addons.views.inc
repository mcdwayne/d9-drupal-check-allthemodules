<?php

/**
 * Implements hook_views_data_alter().
 *
 * @param array $data
 */
function media_view_addons_views_data_alter(array &$data) {
  $data['media__field_media_image']['media_view_addons_nodes_field'] = array(
    'title' => t('Media View Add-ons plugin'),
    'description' => t('Adds a dummy field to the default media View that displays top level nodes referencing media images directly or indirectly via reference.'),
    'group' => 'Media View Add-ons',
    'field' => array(
      'title' => t('Media View Add-ons top level node'),
      'help' => t('Used to add the top level node to the media View.'),
      'id' => 'media_view_addons_nodes_field',
    ),
  );
}
