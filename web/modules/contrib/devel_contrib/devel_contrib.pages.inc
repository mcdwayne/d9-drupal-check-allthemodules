<?php
/**
 * @file devel_contrib.pages.inc
 * Contains page callbacks.
 */

/**
 * Helper for outputting data from info functions.
 *
 * @param $info_hooks
 *  An array whose keys are the names of info functions to call, and whose
 *  values are labels to show with the data from each function.
 *  For example: entity_get_info => 'Entity info'.
 */
function devel_contrib_info_page($info_hooks) {
  $output = '';

  foreach ($info_hooks as $function => $label) {
    $info = $function();
    $output .= kprint_r($info, TRUE, $label);
  }

  return $output;
}

/**
 * Page callback for the bundle info page.
 */
function devel_contrib_field_info_page($entity_type, $bundle) {
  $output = kprint_r($bundle, TRUE, t('Bundle'));

  return $output;
}

/**
 * Page callback for the block display page.
 */
function devel_contrib_block_page($module = NULL, $delta = NULL) {
  if (is_null($module)) {
    // Return a list of all blocks, with links to see each block rendered.
    $build = array();
    $build['modules'] = array(
      '#theme' => 'item_list',
      '#items' => array(),
    );

    foreach (module_implements('block_info') as $module) {
      $build['modules']['#items'][$module]['data'] = $module;
      $module_blocks = module_invoke($module, 'block_info');
      foreach ($module_blocks as $delta => $block) {
        $build['modules']['#items'][$module]['children'][] = l($delta, "devel/block/$module/$delta");
      }
    }

    return $build;
  }

  if (is_null($delta)) {
    $block_info = module_invoke($module, 'block_info');
    $output = kprint_r($block_info, TRUE, t('Blocks'));

    return $output;
  }

  $block = module_invoke($module, 'block_view', $delta);

  // TODO: invoke alter hook.

  // Block subject may be empty.
  if (!isset($block['subject'])) {
    $block['subject'] = '';
  }
  drupal_set_title($block['subject']);

  $output = drupal_render($block['content']);
  return $output;
}

/**
 * Page callback for permissions info.
 */
function devel_contrib_permissions_info_page() {
  $output = '';

  $roles = user_roles();
  $output .= kprint_r($roles, TRUE, t('Roles'));

  $modules = array();
  foreach (module_implements('permission') as $module) {
    $modules[] = $module;
  }
  asort($modules);

  foreach ($modules as $module) {
    if ($permissions = module_invoke($module, 'permission')) {
      $info[$module] = $permissions;
    }
  }

  $output .= kprint_r($info, TRUE, t('All permissions'));

  $info = user_role_permissions($roles);
  $output .= kprint_r($info, TRUE, t('Permissions by role'));

  return $output;
}

/**
 * Page callback for language and translation info.
 */
function devel_contrib_language_info_page() {
  $info = array(
    'language_types_info' => t('Language types'),
    'language_negotiation_info' => t('Language negotiation'),
  );
  if (module_exists('i18n')) {
    $info += array(
      'i18n_object_info' => t('i18n object types'),
    );

    $info += array(
      'i18n_string_group_info' => t('i18n string types'),
    );
  }

  return devel_contrib_info_page($info);
}

/**
 * Page callback for the hook info page.
 */
function devel_contrib_hook_info_page() {
  $implementations = cache_get('module_implements', 'cache_bootstrap');

  $output = t('Registered hook implementations. This may not contain data on all hooks, only those which have been cached so far.');

  if (isset($implementations->data)) {
    $output .= kprint_r($implementations->data, TRUE, t('Registered hook implementations'));
  }

  return $output;
}

/**
 * Page callback for devel menu item: Token.
 */
function devel_contrib_token_info_page() {
  $output = theme('token_tree', array(
    'token_types' => 'all',
  ));

  return $output;
}

/**
 * Page callback for devel menu item: Libraries.
 */
function devel_contrib_libraries_info_page() {
  // Get the info by reference, so we get the changes that libraries_detect()
  // makes.
  $info =& libraries_info();
  // Detect each library, so the info gets loaded up.
  foreach (array_keys($info) as $library_name) {
    libraries_detect($library_name);
  }

  $output = kprint_r($info, TRUE, t('Libraries'));

  return $output;
}

/**
 * Page callback for devel menu item: CTools.
 */
function devel_contrib_ctools_info_page() {
  ctools_include('plugins');
  $plugin_types_info = ctools_plugin_get_plugin_type_info();
  $output = kprint_r($plugin_types_info, TRUE, t('CTools plugin types'));

  $plugins = array();
  foreach ($plugin_types_info as $module => $module_info) {
    foreach ($module_info as $type => $plugin_info) {
      $plugins[$module][$type] = ctools_get_plugins($module, $type);
    }
  }

  $output .= kprint_r($plugins, TRUE, t('CTools plugins'));

  return $output;
}

/**
 * Page callback for devel menu item.
 */
function devel_contrib_views_info_page() {
  $info = views_fetch_data();
  ksort($info);
  $output = kprint_r($info, TRUE, t('Views data'));

  $info = views_fetch_plugin_data();
  ksort($info);
  $output .= kprint_r($info, TRUE, t('Views plugins'));

  return $output;
}

/**
 * Devel tab on a single View.
 *
 * @param $view
 *  The View object.
 */
function devel_contrib_views_devel_page($view) {
  $output = kprint_r($view, TRUE);
  return $output;
}

/**
 * Page callback for devel menu item: Entity API.
 */
function devel_contrib_entity_info_page() {
  $info = entity_get_property_info();
  ksort($info);
  $output = kprint_r($info, TRUE, t('Entity API property info'));

  return $output;
}

/**
 * Page callback for devel menu item: Rules.
 */
function devel_contrib_rules_info_page() {
  $output = '';
  foreach(array('data_info', 'plugin_info', 'event_info') as $hook) {
    $info = rules_fetch_data($hook);
    ksort($info);

    $output .= kprint_r($info, TRUE, t('Rules !hook data', array(
      '!hook' => $hook,
    )));
  }
  return $output;
}

/**
 * Page callback for devel menu item: Media module.
 */
function devel_contrib_media_info_page() {
  $browser_plugin_info = media_get_browser_plugin_info();
  ksort($browser_plugin_info);
  $output = kprint_r($browser_plugin_info, TRUE, t('Media browser plugins'));

  return $output;
}

/**
 * Page callback for devel menu item: VBO.
 */
function devel_contrib_vbo_info_page() {
  $info = views_bulk_operations_get_operation_types();
  $output = kprint_r($info, TRUE, t('VBO operation types'));

  $info = views_bulk_operations_get_operation_info();
  $output .= kprint_r($info, TRUE, t('VBO operations info'));

  return $output;
}

/**
 * Page callback for devel menu item: Features.
 */
function devel_contrib_features_page() {
  $components = features_get_components();
  $output = kprint_r($components, TRUE, t('Features components'));

  $exports = array();
  foreach ($components as $component => $component_info) {
    if ($options = features_invoke($component, 'features_export_options')) {
      foreach ($options as $name => $title) {
        $exports[$component][$name] = $title;
      }
    }
  }
  ksort($exports);
  $output .= kprint_r($exports, TRUE, t('Features exportables'));

  return $output;
}

/**
 * Page callback for devel menu item: Variable module.
 */
function devel_contrib_variable_page() {
  $output = '';

  variable_include();
  $info = variable_build_info('variable', array());
  ksort($info);
  $output .= kprint_r($info, TRUE, t('Defined variables'));

  $info = variable_build_info('group', array());
  ksort($info);
  $output .= kprint_r($info, TRUE, t('Variable groups'));

  $info = variable_build_info('type', array());
  ksort($info);
  $output .= kprint_r($info, TRUE, t('Variable types'));

  if (module_exists('variable_realm')) {
    $info = variable_realm_info();
    ksort($info);
    $output .= kprint_r($info, TRUE, t('Variable realms'));
  }

  return $output;
}

/**
 * Page callback for devel menu item: OG.
 */
function devel_contrib_og_page() {
  $info = og_fields_info();
  $output = kprint_r($info, TRUE, t('OG Field Info'));

  $info = og_get_all_group_bundle();
  $output .= kprint_r($info, TRUE, t('OG Group types'));

  $info = og_get_all_group_content_bundle();
  $output .= kprint_r($info, TRUE, t('OG Group content types'));

  $info = og_context_negotiation_info();
  $output .= kprint_r($info, TRUE, t('OG Group context providers'));

  // There is no API function to get all OG roles!
  $query = db_select('og_role', 'ogr')
    ->fields('ogr')
    ->condition('gid', 0, '=')
    ->orderBy('rid', 'ASC');

  $roles = $query
    ->execute()
    ->fetchAllAssoc('rid');
  $output .= kprint_r($roles, TRUE, t('OG Roles data (raw query, no API)'));

  $info = og_get_permissions();
  $output .= kprint_r($info, TRUE, t('OG permissions'));

  return $output;
}

/**
 * Page callback for devel menu item: Commerce order statuses.
 */
function devel_contrib_commerce_order_states_info_page() {
  $states = commerce_order_states();
  foreach ($states as $name => $state) {
    $states[$name]['statues'] = commerce_order_statuses(array('state' => $name));
  }

  $output = kprint_r($states, TRUE, t('Commerce order states and statuses'));

  return $output;
}

/**
 * Page callback for devel menu item: Commerce checkout panes.
 */
function devel_contrib_commerce_checkout_panes_info_page() {
  $panes = commerce_checkout_panes();

  $output = kprint_r($panes, TRUE, t('Commerce checkout panes'));

  return $output;
}

/**
 * Page callback for devel menu item.
 */
function devel_contrib_flag_info_page() {
  $info = flag_fetch_definition();
  $output = kprint_r($info, TRUE, t('Flag types'));

  $info = flag_get_link_types();
  $output .= kprint_r($info, TRUE, t('Flag link types'));

  $info = flag_get_flags();
  $output .= kprint_r($info, TRUE, t('Defined flags'));

  return $output;
}

/**
 * Page callback for devel menu item.
 */
function devel_contrib_menu_block_info_page() {
  $deltas = variable_get('menu_block_ids', array());
  foreach (array_keys(module_invoke_all('menu_block_blocks')) as $delta) {
    $deltas[] = $delta;
  }

  $info = array();
  foreach ($deltas as $delta) {
    $info[$delta] = menu_block_get_config($delta);
  }

  $output = kprint_r($info, TRUE, t('Defined flags'));

  return $output;
}

/**
 * Page callback for devel menu item: Services
 */
function devel_contrib_services_info_page() {
  $resources = services_get_resources();

  $output = kprint_r($resources, TRUE, t('Services resources'));

  return $output;
}

/**
 * Clients info.
 */
function devel_contrib_clients_info_page() {
  $info = clients_get_connection_types();
  $output = kprint_r($info, TRUE, t('Client connection types'));

  $info = clients_get_resource_types();
  $output .= kprint_r($info, TRUE, t('Client resource types'));

  return $output;
}

/**
 * Data tables info.
 */
function devel_contrib_data_info_page() {
  $info = data_get_all_tables();
  $output = kprint_r($info, TRUE, t('Data tables'));

  return $output;
}

/**
 *
 */
function devel_contrib_migrate_info_page() {
  $info = migrate_migrations();
  $output = kprint_r($info, TRUE, t('Migrations'));

  if (module_exists('migrate_ui')) {
    $wizards = migrate_ui_wizards();
    $output .= kprint_r($wizards, TRUE, t('Migrate UI wizards'));
  }

  return $output;
}

/**
 * Page callback for devel menu item: FacetAPI info.
 */
function devel_contrib_facetapi_info_page() {
  $searchers = facetapi_get_searcher_info();
  $output = kprint_r($searchers, TRUE, t('Searchers'));

  $info = facetapi_get_realm_info();
  $output .= kprint_r($info, TRUE, t('Realms'));

  foreach ($searchers as $searcher) {
    $info = facetapi_get_facet_info($searcher['name']);
    $output .= kprint_r($info, TRUE, t('Facets for @searcher', array('@searcher' => $searcher['label'])));
  }

  return $output;
}

/**
 * Page callback for devel menu item: Remote Entity table info.
 */
function devel_contrib_remote_entity_info_page() {
  $output = '';

  $resources = clients_resource_load_all('remote_entity');
  $output .= kprint_r($resources, TRUE, t('Remote entity resources'));

  // Build a list of relevant connections.
  $connections = array();
  foreach ($resources as $resource) {
    $connections[$resource->connection] = $resource->connection;
  }

  // Show the table info for each connection.
  foreach ($connections as $connection) {
    $table_info = remote_entity_get_query_table_info($connection);
    $output .= kprint_r($table_info, TRUE, t("Remote table info for connection '@connection'", array(
      '@connection' => $connection,
    )));
  }

  // Show the property shadowing for each remote entity.
  $info = array();
  $property_info = entity_get_property_info();
  foreach ($resources as $resource) {
    $entity_type = $resource->component;
    foreach ($property_info[$entity_type]['properties'] as $property_name => $property) {
      if (isset($property['remote property shadowing'])) {
        $info[$entity_type][$property_name] = $property;
      }
    }
  }
  $output .= kprint_r($info, TRUE, t('Remote shadowed entity properties'));

  return $output;
}

/**
 * Page callback for devel menu item: Entity Operations info.
 */
function devel_contrib_entity_operations_info_page() {
  $op_info = entity_operations_get_operation_info();
  $output = kprint_r($op_info, TRUE, t('Entity Operation info'));
  return $output;
}

/**
 * Page callback for devel menu item: Module builder hook data.
 */
function devel_contrib_module_builder_info_page() {
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrupalUI');

  // Get our task handler, which checks hook data is ready.
  try {
    $mb_task_handler_report = $mb_factory->getTask('ReportHookData');
  }
  catch (ModuleBuilderException $e) {
    if ($e->needs_hooks_download) {
      drupal_set_message(t('No hooks were found. Please check the documentation path specified in the <a href="!settings-url">%administer >> %config >> %develop >> %modulebuilder</a> page.',
        array(
          '!settings-url' => url('admin/config/development/module_builder'),
          '%administer' => 'Administer',
          '%config' => 'Site configuration',
          '%develop' => 'Development',
          '%modulebuilder' => "Module builder",
        )
      ), 'warning');
      return '';
    }
  }

  $hook_groups = $mb_task_handler_report->listHookData();
  $output = kprint_r($hook_groups, TRUE, t('Hook groups'));
  return $output;
}
