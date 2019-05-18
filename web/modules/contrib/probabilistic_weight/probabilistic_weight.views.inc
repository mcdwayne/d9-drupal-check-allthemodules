<?php

/**
 * @file
 * Provide views data and handlers for probabilistic_weight.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data_alter().
 */
function probabilistic_weight_views_data_alter(&$data) {

  $fields = field_info_fields();

  foreach ($fields as $key => $field) {
    if ($field->module == 'probabilistic_weight') {
      $group = 'field_data_' . $key;
      $name = $key . '_weight';
      $data[$group][$name]['group'] = t('Probabilistic weight');
      $data[$group][$name]['help'] = t('Probabilistic weight fields: !field_name',
        array(
          '!field_name' => $key,
        )
      );
      $data[$group][$name]['sort'] = array(
        'id' => 'probabilistic_weight_random',
      );
    }

  }

}
