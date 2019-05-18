<?php

/**
 * @file
 * Provide views data for dblog.module.
 */

/**
 * Implements hook_views_data().
 */
function config_views_views_data() {
  /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
  $data = [];
  $typed_config = Drupal::service('config.typed');

  $all_definitions = $typed_config->getDefinitions();
  $all_definitions_keys = array_keys($all_definitions);
  // Small optimization: the config entities due to their prefixed nature always
  // end up in *. Other types might have this too so these are just candidates.
  $config_keys = preg_grep('/\*$/', $all_definitions_keys);
  $entity_types = \Drupal::entityTypeManager()->getDefinitions();
  // Registers an entity area handler per entity type.
  /** @var Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_info */
  foreach ($entity_types as $entity_type => $entity_info) {

    if (!$entity_info->entityClassImplements('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
      continue;
    }

    // Exclude entity types, which cannot be rendered.
    if ($entity_info->hasListBuilderClass()) {
      $label = $entity_info->getLabel();
      $data['views']['entity_' . $entity_type] = [
        'title' => t('Rendered entity - @label', ['@label' => $label]),
        'help' => t('Displays a rendered @label entity in an area.', ['@label' => $label]),
        'area' => [
          'entity_type' => $entity_type,
          'id' => 'entity',
        ],
      ];
    }

    if ($prefix = $entity_info->getConfigPrefix()) {
      $table_name = 'config_' . str_replace('.', '_', $prefix);
      $id = $entity_info->getKey('id');
      $data[$table_name]['table'] = [
        'group' => t('Config'),
        'entity type' => $entity_type,
        'entity revision' => FALSE,
      ];
      $data[$table_name]['table']['base'] = [
        'title' => $entity_info->getLabel(),
        'field' => $id,
        'query_id' => 'views_config_entity_query',
      ];

      $data[$table_name][$id]['field'] = [
        'id' => 'standard',
        'title' => 'id',
      ];
      $data[$table_name]['operation'] = [
        'title' => t('Operations'),
        'help' => t('Operations'),
        'field' => [
          'id' => 'config_entity_operations',
        ],
      ];

      // We do not kno how many stars a given config prefix requires, for
      // example the schema for block is under block.block.*.*.
      $keys = preg_grep('/^' . str_replace('.', '\.', $prefix) . '\.\*/', $config_keys);
      // There always will be just one. @TODO: add exception?
      $key = reset($keys);
      $definition = $all_definitions[$key];
      _views_views_config_process_schema($data[$table_name], $all_definitions, $all_definitions_keys, $definition);
    }
  }
  return $data;
}

/**
 * Process all config definitions.
 *
 * @param array $views_config_data
 *   Views config data structure.
 * @param array $all_definitions
 *   All config definitions.
 * @param array $all_definitions_keys
 *   All config definition keys.
 * @param array $definition
 *   The current definition.
 * @param string $prefix
 *   A prefix.
 */
function _views_views_config_process_schema(array &$views_config_data, array $all_definitions, array $all_definitions_keys, array $definition, $prefix = '') {
  if (!is_array($definition)) {
    return;
  }
  $delimiter = $prefix ? '.' : '';
  if (isset($definition['type'])) {
    if (is_array($definition['type'])) {
      return;
    }
    $type = $definition['type'];
    // The type might be constant (for eg. type: views_display) or might be a
    // reference, for eg. type: views.field.[plugin_id] As it is impossible to
    // know just from the metadata what the exact type will end up in the
    // latter case, iterate over every possibility (views.field.boolean,
    // views.field.counter, etc).
    if (isset($all_definitions[$type])) {
      $new_definition = $all_definitions[$type];
      if (isset($new_definition['type'])) {
        _views_views_config_process_schema($views_config_data, $all_definitions, $all_definitions_keys, $new_definition, $prefix);
      }
      // If the new definition type has no type then the current one is a
      // primitve type (for eg. string). If there is a label and this is
      // neither a mapping or a sequence then add it to Views data.
      elseif (!isset($definition['mapping']) && !isset($definition['sequence']) && isset($definition['label'])) {
        _views_views_config_data_add($views_config_data, $definition, $prefix);
        // As there is no mapping or sequence, this prefix is done.
        return;
      }
    }
    else {
      // For field.[%parent.type].settings, find field.email.settings etc.
      // For this, create a PCRE mask replacing the [] with "any string" for eg.
      // field\..*\.settings.
      $left = substr($type, 0, strpos($type, '['));
      $right = substr($type, strpos($type, ']') + 1);
      $mask = str_replace('.', '\.', $left) . '.*' . str_replace('.', '\.', $right);
      // Now find every possible type and recurse.
      foreach (preg_grep('/^' . $mask . '/', $all_definitions_keys) as $key) {
        _views_views_config_process_schema($views_config_data, $all_definitions, $all_definitions_keys, $all_definitions[$key], $prefix);
      }
    }
  }
  // Finished with the type-related recursions, now do the specific recursions.
  if (isset($definition['mapping'])) {
    foreach ($definition['mapping'] as $k => $new_definition) {
      _views_views_config_process_schema($views_config_data, $all_definitions, $all_definitions_keys, $new_definition, "$prefix$delimiter$k");
    }
  }
  elseif (isset($definition['sequence'])) {
    foreach ($definition['sequence'] as $new_definition) {
      if (!is_array($new_definition)) {
        continue;
      }
      _views_views_config_process_schema($views_config_data, $all_definitions, $all_definitions_keys, $new_definition, "$prefix$delimiter*");
    }
  }

}

/**
 * Adjust config data based on type.
 *
 * @param array $views_config_data
 *   Views config data structure.
 * @param array $definition
 *   The current definition.
 * @param string $prefix
 *   A prefix.
 */
function _views_views_config_data_add(array &$views_config_data, array $definition, $prefix) {
  // Some places label makes no sense than prefix.
  $title = $definition['label'];
  if (strtolower($title) == 'text') {
    $title = ucfirst($prefix);
  }
  $views_config_data[$prefix] = [
    'title' => $title,
    'help' => $prefix,
    'sort' => [
      'id' => 'standard',
    ],
  ];

  switch ($definition['type']) {
    case 'boolean':
      $views_config_data[$prefix]['field']['id'] = 'standard';
      $views_config_data[$prefix]['filter']['id'] = 'config_entity_boolean';
      $views_config_data[$prefix]['argument']['id'] = 'standard';
      break;

    case 'integer':
      $views_config_data[$prefix]['field']['id'] = 'numeric';
      $views_config_data[$prefix]['filter']['id'] = 'numeric';
      $views_config_data[$prefix]['argument']['id'] = 'numeric';
      break;

    default:
      $views_config_data[$prefix]['field']['id'] = 'standard';
      $views_config_data[$prefix]['filter']['id'] = 'config_entity_string';
      $views_config_data[$prefix]['argument']['id'] = 'standard';
      break;
  }
}

/**
 * Implements hook_views_data().
 */
function config_views_views_data_sample() {
  $data = [];

  $data['config_views']['table']['group'] = t('Configuration');
  $data['config_views']['table']['wizard_id'] = 'config_entity';
  $data['config_views']['table']['base'] = [
    'query_id' => 'config_entity_query',
    'field' => 'id',
    'title' => t('Configuration Entity'),
    'help' => t('Contains list of configuration entities.'),
  ];

  $data['config_views']['id'] = [
    'title' => t('Machine name'),
    'help' => t('Unique configuration entity ID.'),
    'field' => [
      'id' => 'config_entity_id',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'search' => [
      'id' => 'standard',
    ],
  ];

  $data['config_views']['label'] = [
    'title' => t('Label'),
    'help' => t('Label'),
    'field' => [
      'id' => 'config_entity_label',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'search' => [
      'id' => 'standard',
    ],
  ];

  $data['config_views']['type'] = [
    'title' => t('Type'),
    'help' => t('Configuration entity type.'),
    'field' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'search' => [
      'id' => 'standard',
    ],
  ];

  $data['config_views']['description'] = [
    'title' => t('Description'),
    'help' => t('Description'),
    'field' => [
      'id' => 'config_entity_description',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'search' => [
      'id' => 'standard',
    ],
  ];

  $data['config_views']['operation'] = [
    'title' => t('Operations'),
    'help' => t('Operations'),
    'field' => [
      'id' => 'config_entity_operations',
    ],
  ];

  return $data;

}
