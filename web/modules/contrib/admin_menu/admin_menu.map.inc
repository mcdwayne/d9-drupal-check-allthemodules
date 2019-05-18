<?php

/**
 * @file
 * Implements hook_admin_menu_map() on behalf of core modules.
 */

/**
 * Implements hook_admin_menu_map() on behalf of Node module.
 *
 * @todo admin_menu_admin_menu_output_build() needs to expand the partial tree
 *   it creates, so that this definition takes effect.
 */
function node_admin_menu_map() {
  if (user_access('administer content types')) {
    $map['admin/structure/types/manage/%node_type'] = [
      'parent' => 'admin/structure/types',
      'arguments' => [
        ['%node_type' => array_keys(node_type_get_types())],
      ],
    ];
  }
  $map['node/add/%node_type'] = [
    'parent' => 'node/add',
    'arguments' => [
      ['%node_type' => array_keys(node_type_get_types())],
    ],
  ];
  return $map;
}

/**
 * Implements hook_admin_menu_map() on behalf of all config entity type modules.
 */
function admin_menu_admin_menu_map() {
  $map = [];
  $entity_info = entity_get_info();
  foreach ($entity_info as $entity_type => $info) {
    // @todo Core: Allow to identify... config entity types, but exclude 'block'...
    if (!isset($info['config_prefix']) || $entity_type == 'block') {
      continue;
    }
    $entities = entity_load_multiple($entity_type);
    if (empty($entities)) {
      continue;
    }
    // For now, we assume that all config entities share the same URI pattern.
    // @todo Core: Add 'router path' to entity info.
    $entity = reset($entities);
    $uri = $entity->uri();
    if (!isset($uri['path'])) {
      continue;
    }
    // We cannot use menu_get_item() here, since that returns FALSE when access
    // is denied for an entity, which can be bogus; e.g., in case of disabled
    // filter formats.
    // @see menu_get_item()
    $original_map = explode('/', $uri['path']);
    $parts = array_slice($original_map, 0, MENU_MAX_PARTS);
    $ancestors = menu_get_ancestors($parts);
    $router_item = db_query_range('SELECT * FROM {menu_router} WHERE path IN (:ancestors) ORDER BY fit DESC', 0, 1, [':ancestors' => $ancestors])->fetchAssoc();
    $router_item['load_functions'] = unserialize($router_item['load_functions']);

    // If no corresponding router item could be found, or if it does not specify
    // dynamic arguments, skip this entity type.
    if (empty($router_item['load_functions'])) {
      continue;
    }

    // The router path contains e.g. 'admin/structure/types/manage/%'.
    $parts = explode('/', $router_item['path']);
    // Regenerate the entity menu argument name without '_load' suffix.
    // If multiple dynamic arguments are contained, then we assume that the last
    // one is the actual one.
    $entity_arg = NULL;
    array_walk($router_item['load_functions'], function ($function, $pos) use (&$entity_arg, &$parts) {
      $entity_arg = '%' . substr($function, 0, -5);
      $parts[$pos] = $entity_arg;
    });
    // $entity_arg contains e.g. '%node_type' now.
    // $path contains e.g. 'admin/structure/types/manage/%node_type' now.
    $path = implode('/', $parts);

    // Find the parent path.
    // For now, we assume that removing the dynamic argument in the path (which
    // yields e.g. 'admin/structure/types/manage') and retrieving the router
    // item of that will yield e.g. 'admin/structure/types'.
    // @todo Core: Add 'admin list path' to entity info.
    array_pop($parts);
    $parent_path = implode('/', $parts);
    $parent_router_item = menu_get_item($parent_path);
    if (empty($parent_router_item)) {
      continue;
    }
    $map[$path] = [
      'parent' => $parent_router_item['path'],
      'arguments' => [
        [$entity_arg => array_keys($entities)],
      ],
    ];
  }
  return $map;
}

/**
 * Implements hook_admin_menu_map() on behalf of Field UI module.
 */
function field_ui_admin_menu_map() {
  $map = [];
  $complete_bundle_info = entity_get_bundles();
  foreach ($complete_bundle_info as $entity_type => $bundles) {
    foreach ($bundles as $bundle_name => $bundle_info) {
      if (!isset($bundle_info['admin'])) {
        continue;
      }
      // @see field_ui_permission()
      if (user_access('administer ' . $entity_type . ' fields')) {
        $fields = [];
        foreach (field_info_instances($entity_type, $bundle_name) as $field) {
          $fields[] = $field['field_name'];
        }
        if ($fields) {
          $path = $bundle_info['admin']['path'];
          $arguments = [];
          // Extract the bundle argument name from the bundle info.
          // If no bundle argument is defined, then the entity type has a single
          // bundle only, in which case Field UI attaches itself to the static/
          // non-dynamic path in $bundle_info['admin']['path'].
          // @see field_ui_menu()
          if (isset($bundle_info['admin']['bundle argument'])) {
            $bundle_pos = $bundle_info['admin']['bundle argument'];
            $parts = explode('/', $path);
            $bundle_arg = $parts[$bundle_pos];

            // Special-casing for comments.
            // @todo Core: Rename 'node bundle' to 'parent bundle'.
            // @todo Doesn't work yet. Why?
            if ($entity_type == 'comment') {
              $bundle_name = $bundle_info['node bundle'];
            }
            $arguments[$bundle_arg] = [$bundle_name];
          }
          $arguments['%field_ui_instance'] = $fields;

          $map["$path/fields/%field_ui_instance"]['parent'] = "$path/fields";
          $map["$path/fields/%field_ui_instance"]['arguments'][] = $arguments;
        }
      }
    }
  }
  return $map;
}
