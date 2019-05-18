<?php
/**
 * @file
 * Main module file for the pm_permission module.
 */

/**
 * Admin settings form.
 */
function pm_permission_admin() {
  $form = array();
  $form['pm_permission_node'] = array(
    '#type' => 'fieldset',
    '#title' => t('Select node bundles where you want to enable PM permissions'),
  );

  $form['pm_permission_field'] = array(
    '#type' => 'fieldset',
    '#title' => t('Configure reference fields'),
  );

  $node_types = array();

  foreach (node_type_get_types() as $type => $info) {
    $name = node_type_get_name($type);
    $node_types[$type] = $name;
    $form['pm_permission_node']["pm_permission_node_{$type}_enabled"] = array(
      '#type' => 'checkbox',
      '#title' => $name,
      '#default_value' => variable_get("pm_permission_node_{$type}_enabled", FALSE),
    );
    $options = _pm_permission_get_field_names_per_field_type('entityreference', 'node', $type);
    if (count($options['node']) < 2) {
      $form['pm_permission_field']["pm_permission_field_parent_reference_for_{$type}_message"] = array(
        '#type' => 'item',
        '#markup' => t('No configuration available for "@name"', array('@name' => $name)),
        '#states' => array(
          'visible' => array(
            ":input[name='pm_permission_node_{$type}_enabled']" => array(
              'checked' => TRUE,
            ),
          ),
        ),
      );
    }
    else {
      $form['pm_permission_field']["pm_permission_field_parent_reference_for_{$type}"] = array(
        '#type' => 'select',
        '#title' => t('Select parent reference field for "@name"', array('@name' => $name)),
        '#options' => $options['node'],
        '#default_value' => variable_get("pm_permission_field_parent_reference_for_{$type}", ''),
        '#states' => array(
          'visible' => array(
            ":input[name='pm_permission_node_{$type}_enabled']" => array(
              'checked' => TRUE,
            ),
          ),
        ),
      );
    }
  }

  $options = _pm_permission_get_field_names_per_field_type('entityreference');

  $form['pm_permission_field']['pm_permission_field_assigned_reference'] = array(
    '#type' => 'select',
    '#title' => t('Assigned reference field'),
    '#options' => $options['user'],
    '#disabled' => TRUE,
    '#default_value' => variable_get("pm_permission_field_assigned_reference", ''),
  );
  $form['pm_permission_field']['pm_permission_field_pm_reference'] = array(
    '#type' => 'select',
    '#title' => t('Project Manager reference field'),
    '#options' => $options['user'],
    '#disabled' => TRUE,
    '#default_value' => variable_get("pm_permission_field_pm_reference", ''),
  );
  $form['pm_permission_field']['pm_permission_field_org_member_reference'] = array(
    '#type' => 'select',
    '#title' => t('Organization member reference field'),
    '#options' => $options['user'],
    '#disabled' => TRUE,
    '#default_value' => variable_get("pm_permission_field_org_member_reference", ''),
  );

  $form['pm_permission_advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced Configuration'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['pm_permission_advanced']['pm_permission_override_core_permissions_agree'] = array(
    '#type' => 'checkbox',
    '#title' => t('I agree to override default core node permissions'),
    '#description' => t("Drupal core doesn't provide a UI to alter these settings. So make sure you revert the settings before you uninstall this module."),
    '#default_value' => FALSE,
  );
  $form['pm_permission_advanced']['pm_permission_override_core_permissions'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Node default core permission override'),
    '#default_value' => variable_get("pm_permission_override_core_permissions", array()),
    '#options' => $node_types,
    '#states' => array(
      'enabled' => array(
        ':input[name="pm_permission_override_core_permissions_agree"]' => array('checked' => TRUE),
      ),
    ),
  );

  $form = system_settings_form($form);

  $form['#submit'][] = 'pm_permission_admin_submit';

  return $form;
}

/**
 * Admin settings form submit handler.
 */
function pm_permission_admin_submit($form, $form_state) {
  $values = $form_state['values'];
  $agree = $values['pm_permission_override_core_permissions_agree'];
  $overrides = $values['pm_permission_override_core_permissions'];
  if ($agree AND is_array($overrides)) {
    foreach ($overrides as $type => $value) {
      if ($value) {
        variable_set('node_permissions_' . $type, 0);
      }
      else {
        variable_set('node_permissions_' . $type, 1);
      }
    }
  }
}

/**
 * Get a list of field names matching a specific type.
 *
 * This function works reliably for "entityreference" only.
 *
 * @param string $type
 *   Type of field e.g. entityreference.
 *
 * @return array $field_names
 *   Field names categorized by target entity type.
 */
function _pm_permission_get_field_names_per_field_type($type, $entity_type = NULL, $bundle_name = NULL) {
  $field_names = array(
    'user' => array('' => t('- None -')),
    'node' => array('' => t('- None -')),
  );
  // Get all entityreference field names.
  $query = db_select('field_config', 'f');
  $query->fields('f', array('field_name'));
  $query->condition('f.type', $type);
  $query->distinct();
  $rows = $query->execute();

  foreach ($rows as $row) {
    $field_name = $row->field_name;

    if ($bundle_name AND !field_info_instance($entity_type, $field_name, $bundle_name)) {
      continue;
    }

    $field_info = field_info_field($field_name);
    if (isset($field_info['settings']['target_type'])) {
      // Code...
      $field_names[$field_info['settings']['target_type']][$field_name] = $field_name;
    }
  }
  return $field_names;
}

/**
 * Returns a list of node bundles for which PM permission have been enabled.
 */
function pm_permission_get_enabled_types() {
  $enabled = array();
  foreach (node_type_get_types() as $type => $info) {
    if (variable_get("pm_permission_node_{$type}_enabled", FALSE)) {
      $enabled[] = $type;
    }
  }
  return $enabled;
}

/**
 * Creates an array of permission name for the given node bundle.
 *
 * @param string $bundle_name
 *   Node bundle name.
 *
 * @return array
 *   Array equivalent to the return of hook_permission().
 */
function pm_permission_permission_per_bundle($bundle_name) {
  $name = node_type_get_name($bundle_name);
  $bundle_name = check_plain($bundle_name);

  $permissions = array();

  if (!variable_get('node_permissions_' . $bundle_name, 1)) {
    $permissions += _pm_permission_permission_default($bundle_name, $name);
  }

  if (module_exists('pmorganization')) {
    if (_pm_permission_get_field_name($bundle_name, 'parent') OR $bundle_name == 'pmorganization') {
      $permissions += _pm_permission_permission_belonged($bundle_name, $name, 'Organization');
    }
  }
  if (module_exists('pmproject')) {
    if (_pm_permission_get_field_name($bundle_name, 'pm')) {
      $permissions += _pm_permission_permission_assigned($bundle_name, $name, 'Project Manager');
    }
  }
  if (_pm_permission_get_field_name($bundle_name, 'assigned')) {
    $permissions += _pm_permission_permission_assigned($bundle_name, $name, 'Assigned');
  }
  return $permissions;
}

/**
 * Helper function to generate default permissions.
 *
 * @param string $type
 *   Node bundle machine name.
 * @param string $name
 *   Node bundle name that could be used for display.
 *
 * @return array
 *   Array equivalent to the return of hook_permission().
 */
function _pm_permission_permission_default($type, $name) {
  $permissions = array(
    "PM permission $type: create" => array(
      'title' => t('%type_name: Create new content', array('%type_name' => $name)),
    ),
    "PM permission $type: view own" => array(
      'title' => t('%type_name: View own content', array('%type_name' => $name)),
    ),
    "PM permission $type: view all" => array(
      'title' => t('%type_name: View any content', array('%type_name' => $name)),
    ),
    "PM permission $type: update own" => array(
      'title' => t('%type_name: Edit own content', array('%type_name' => $name)),
    ),
    "PM permission $type: update all" => array(
      'title' => t('%type_name: Edit any content', array('%type_name' => $name)),
    ),
    "PM permission $type: delete own" => array(
      'title' => t('%type_name: Delete own content', array('%type_name' => $name)),
    ),
    "PM permission $type: delete all" => array(
      'title' => t('%type_name: Delete any content', array('%type_name' => $name)),
    ),
  );
  return $permissions;
}

/**
 * Helper function to generate belonged to permission set.
 *
 * @param string $type
 *   Node bundle machine name.
 * @param string $name
 *   Node bundle name that could be used for display.
 *
 * @return array $permissions
 *   Array equivalent to the return of hook_permission().
 */
function _pm_permission_permission_belonged($type, $name, $belonged) {
  $permissions["PM permission $type: view own $belonged"] = array(
    'title' => t('%type_name: View content in own %belonged', array('%type_name' => $name, '%belonged' => $belonged)),
    'description' => t('For %type_name assigned to the same %belonged as a user, allows the user to view the %type_name and see the %type_name in lists or dropdowns elsewhere on the site.', array('%type_name' => $name, '%belonged' => $belonged)),
  );
  $permissions["PM permission $type: update own $belonged"] = array(
    'title' => t('%type_name: Edit content in own %belonged', array('%type_name' => $name, '%belonged' => $belonged)),
    'description' => t('For %type_name assigned to the same %belonged as a user, allows the user to edit the %type_name.', array('%type_name' => $name, '%belonged' => $belonged)),
  );
  $permissions["PM permission $type: delete own $belonged"] = array(
    'title' => t('%type_name: Delete in own %belonged', array('%type_name' => $name, '%belonged' => $belonged)),
    'description' => t('For %type_name assigned to the same %belonged as a user, allows the user to delete the %type_name.', array('%type_name' => $name, '%belonged' => $belonged)),
  );
  return $permissions;
}

/**
 * Helper function to generate assigned permissions set.
 *
 * @param string $type
 *   Node bundle machine name.
 * @param string $name
 *   Node bundle name that could be used for display.
 *
 * @return array $permissions
 *   Equivalent to the return of hook_permission().
 */
function _pm_permission_permission_assigned($type, $name, $role) {
  $permissions["PM permission $type: view if $role"] = array(
    'title' => t('%type_name: View content if %role to %type_name', array('%type_name' => $name, '%role' => $role)),
    'description' => t('For %type_name with a user as %role, allows the user to view the %type_name.', array('%type_name' => $name, '%role' => $role)),
  );
  $permissions["PM permission $type: update if $role"] = array(
    'title' => t('%type_name: Edit content if %role to %type_name', array('%type_name' => $name, '%role' => $role)),
    'description' => t('For %type_name with a user as %role, allows the user to edit the %type_name.', array('%type_name' => $name, '%role' => $role)),
  );
  $permissions["PM permission $type: delete if $role"] = array(
    'title' => t('%type_name: Delete content if %role to %type_name', array('%type_name' => $name, '%role' => $role)),
    'description' => t('For %type_name with a user as %role, allows the user to delete the %type_name.', array('%type_name' => $name, '%role' => $role)),
  );
  return $permissions;
}

/**
 * A helper function to check if a field is present in a node.
 *
 * @param string $bundle_name
 *   Node bundle machine name.
 * @param string $category
 *   Category of permission {pm, assigned, parent}.
 *
 * @return string
 *   The actual name of the field if present, false otherwise.
 */
function _pm_permission_get_field_name($bundle_name, $category) {
  switch ($category) {
    case 'parent':
      $field_name = variable_get("pm_permission_field_parent_reference_for_{$bundle_name}", FALSE);
      break;

    case 'assigned':
      $field_name = variable_get("pm_permission_field_assigned_reference", FALSE);
      break;

    case 'pm':
      $field_name = variable_get("pm_permission_field_pm_reference", FALSE);
      break;

    default:
      $field_name = FALSE;
      break;

  }

  if ($field_name AND field_info_instance('node', $field_name, $bundle_name)) {
    return $field_name;
  }
  return FALSE;
}


/**
 * Generate default grants similar to drupal core.
 */
function _pm_permission_grants_default($node, $type) {
  // PM permission $type: view all.
  $grants[] = array(
    'realm' => "pm_permission_{$type}_view_all",
    'gid' => 0,
    'grant_view' => 1,
    'grant_update' => 0,
    'grant_delete' => 0,
    'priority' => 0,
  );
  // PM permission $type: view own.
  $grants[] = array(
    'realm' => "pm_permission_{$type}_view_own",
    'gid' => $node->uid,
    'grant_view' => 1,
    'grant_update' => 0,
    'grant_delete' => 0,
    'priority' => 0,
  );
  // PM permission $type: update all.
  $grants[] = array(
    'realm' => "pm_permission_{$type}_update_all",
    'gid' => 0,
    'grant_view' => 0,
    'grant_update' => 1,
    'grant_delete' => 0,
    'priority' => 0,
  );
  // PM permission $type: update own.
  $grants[] = array(
    'realm' => "pm_permission_{$type}_update_own",
    'gid' => $node->uid,
    'grant_view' => 0,
    'grant_update' => 1,
    'grant_delete' => 0,
    'priority' => 0,
  );
  // PM permission $type: delete all.
  $grants[] = array(
    'realm' => "pm_permission_{$type}_delete_all",
    'gid' => 0,
    'grant_view' => 0,
    'grant_update' => 0,
    'grant_delete' => 1,
    'priority' => 0,
  );
  // PM permission $type: delete own.
  $grants[] = array(
    'realm' => "pm_permission_{$type}_delete_own",
    'gid' => $node->uid,
    'grant_view' => 0,
    'grant_update' => 0,
    'grant_delete' => 1,
    'priority' => 0,
  );
  return $grants;
}

/**
 * Generate grants for belonged permission set.
 */
function _pm_permission_grants_belonged($node, $type, $belonged) {
  // PM permission $type: view own %belonged.
  $belonged_nid = _pm_permission_get_belonged_id($node, $type, $belonged);
  $grants = array();
  if ($belonged_nid) {
    $grants[] = array(
      'realm' => "pm_permission_{$type}_view_belonged_{$belonged}",
      'gid' => $belonged_nid,
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
      'priority' => 0,
    );
    $grants[] = array(
      'realm' => "pm_permission_{$type}_update_belonged_{$belonged}",
      'gid' => $belonged_nid,
      'grant_view' => 0,
      'grant_update' => 1,
      'grant_delete' => 0,
      'priority' => 0,
    );
    $grants[] = array(
      'realm' => "pm_permission_{$type}_delete_belonged_{$belonged}",
      'gid' => $belonged_nid,
      'grant_view' => 0,
      'grant_update' => 0,
      'grant_delete' => 1,
      'priority' => 0,
    );
  }
  return $grants;
}

/**
 * Generate grants for assigned permission set.
 */
function _pm_permission_grants_assigned($node, $type, $assigned) {
  // PM permission $type: view own %assigned.
  $assigned_nids = _pm_permission_get_assigned_id($node, $type, $assigned);
  $grants = array();
  if ($assigned_nids) {
    foreach ($assigned_nids as $assigned_nid) {
      if ($assigned_nid) {
        $grants[] = array(
          'realm' => "pm_permission_{$type}_view_assigned_{$assigned}",
          'gid' => $assigned_nid,
          'grant_view' => 1,
          'grant_update' => 0,
          'grant_delete' => 0,
          'priority' => 0,
        );
        $grants[] = array(
          'realm' => "pm_permission_{$type}_update_assigned_{$assigned}",
          'gid' => $assigned_nid,
          'grant_view' => 0,
          'grant_update' => 1,
          'grant_delete' => 0,
          'priority' => 0,
        );
        $grants[] = array(
          'realm' => "pm_permission_{$type}_delete_assigned_{$assigned}",
          'gid' => $assigned_nid,
          'grant_view' => 0,
          'grant_update' => 0,
          'grant_delete' => 1,
          'priority' => 0,
        );
      }
    }
  }
  return $grants;
}

/**
 * Finds the nid of the organization attached to a node.
 */
function _pm_permission_get_organization_id($node, $max_depth) {
  return pm_permission_get_parent_nid_of_node($node, 'pmorganization', $max_depth);
}

/**
 * Recursively hunts and find out the first parent node of given type.
 *
 * @param object $node
 *   The node for which the parent to be found.
 * @param string $parent_type
 *   The content type of parent node.
 * @param int $max_depth
 *   How many levels up should the function traverse.
 *
 * @return mixed
 *   nid of the parent node if found, FALSE otherwise.
 */
function pm_permission_get_parent_nid_of_node($node, $parent_type, $max_depth = NULL) {

  if ($max_depth == NULL) {
    $max_depth = variable_get('pm_permission_relation_search_depth', 10);
  }

  if ($node->type == $parent_type) {
    return $node->nid;
  }
  if ($max_depth) {
    --$max_depth;
    $bundle_name = $node->type;
    $field_name = _pm_permission_get_field_name($bundle_name, 'parent');
    if (empty($field_name)) {
      return FALSE;
    }
    try {
      $wrapper = entity_metadata_wrapper('node', $node);
      $n = $wrapper->$field_name->value();
      if (empty($n)) {
        return FALSE;
      }
      elseif ($n->type == $parent_type) {
        return $n->nid;
      }
      else {
        return pm_permission_get_parent_nid_of_node($n, $parent_type, $max_depth);
      }
    }
    catch (Exception $e) {
      _pm_permission_watchdog_log($e);
    }
  }
  else {
    return FALSE;
  }
}

/**
 * Recursively hunt for a particular parent and get its id.
 */
function _pm_permission_get_belonged_id($node, $type, $belonged) {

  $belonged_nid = FALSE;

  switch ($belonged) {
    case 'Organization':
      $belonged_nid = _pm_permission_get_organization_id($node, 4);
      break;

    default:
      break;

  }
  return $belonged_nid;
}

/**
 * Should recursively hunt for a particular parent and get its id.
 */
function _pm_permission_get_assigned_id($node, $type, $assigned) {

  $assigned_nids = FALSE;

  switch ($assigned) {
    case 'Project Manager':
      $field_name = variable_get("pm_permission_field_pm_reference", FALSE);
      break;

    case 'Assigned':
      $field_name = variable_get("pm_permission_field_assigned_reference", FALSE);
      break;

    default:
      break;

  }
  if ($field_name) {
    try {
      $wrapper = entity_metadata_wrapper('node', $node);
      $field_info = field_info_field($field_name);
      if ($field_info['cardinality'] == 1) {
        $account = $wrapper->$field_name->value();
        if ($account) {
          $assigned_nids[] = $account->uid;
        }
      }
      else {
        foreach ($wrapper->$field_name->getIterator() as $account_wrapper) {
          $assigned_nids[] = $account_wrapper->getIdentifier();
        }
      }
    }
    catch (Exception $e) {
      _pm_permission_watchdog_log($e);
    }
  }
  return $assigned_nids;
}


/**
 * Helper function for pm_permission_node_grants.
 */
function pm_permission_grants_list($account, $op, $type) {
  $grants = array();

  if (user_access("PM permission $type: $op all", $account)) {
    $grants["pm_permission_{$type}_{$op}_all"] = array(0);
  }

  if (user_access("PM permission $type: $op own", $account)) {
    $grants["pm_permission_{$type}_{$op}_own"] = array($account->uid);
  }

  if (module_exists('pmorganization')) {
    if (_pm_permission_get_field_name($type, 'parent') OR $type == 'pmorganization') {
      $field_name = variable_get("pm_permission_field_org_member_reference", '');
      $pmorganization_nids = _pm_permission_get_entity_id_referenced_to_user('node', 'pmorganization', $field_name, $account->uid);
      $belonged = 'Organization';
      if (user_access("PM permission $type: $op own $belonged", $account) AND !empty($pmorganization_nids)) {
        $grants["pm_permission_{$type}_{$op}_belonged_{$belonged}"] = $pmorganization_nids;
      }
    }
  }

  if (module_exists('pmproject')) {
    if (_pm_permission_get_field_name($type, 'pm')) {
      $assigned = 'Project Manager';
      if (user_access("PM permission $type: $op if $assigned", $account)) {
        $grants["pm_permission_{$type}_{$op}_assigned_{$assigned}"] = array($account->uid);
      }
    }
  }

  if (_pm_permission_get_field_name($type, 'assigned')) {
    $assigned = 'Assigned';
    if (user_access("PM permission $type: $op if $assigned", $account)) {
      $grants["pm_permission_{$type}_{$op}_assigned_{$assigned}"] = array($account->uid);
    }
  }
  return $grants;
}

/**
 * Get entity ids having a reference field pointing towards the user id.
 */
function _pm_permission_get_entity_id_referenced_to_user($entity_type, $bundle, $field_name, $uid) {
  $ids = FALSE;

  if ($entity_type AND $bundle AND $field_name AND $uid) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type)
      ->entityCondition('bundle', $bundle)
      ->propertyCondition('status', 1)
      ->fieldCondition($field_name, 'target_id', $uid)
      ->addMetaData('account', user_load(1));
    $result = $query->execute();
    if (isset($result['node'])) {
      $ids = array_keys($result['node']);
    }
  }
  return $ids;
}

/**
 * Log and display the error.
 *
 * @param string $error
 *   Error to report.
 * @param bool $backtrace
 *   TRUE if watchdog log should include full backtrace of the error.
 */
function _pm_permission_watchdog_log($error, $backtrace = TRUE) {
  $err_msg = t("Error message: %error", array('%error' => $error));
  watchdog('PHP', $err_msg, WATCHDOG_ERROR);
  drupal_set_message($err_msg, 'error');
}

/**
 * Check and performs access update if supplied pmorganization.
 */
function pm_permission_hunt_and_update_all_child_permission($node) {
  $nids = pm_permission_get_all_child_nids($node->nid);
  if ($nids) {
    $batch = array(
      'title' => t('Rebuilding access permissions for the @type "@title"', array('@type' => $node->type, '@title' => $node->title)),
      'operations' => array(
        array('_pm_permission_node_access_rebuild_batch_operation', array($nids)),
      ),
      'finished' => '_pm_permission_node_access_rebuild_batch_finished',
    );
    batch_set($batch);
  }
}

/**
 * Check if current node is directly under pmorganization, and has changed.
 */
function pm_permission_check_if_child_permission_needs_update($node) {
  $type = $node->type;
  $wrapper = entity_metadata_wrapper('node', $node);
  $parent_field = variable_get("pm_permission_field_parent_reference_for_{$type}", NULL);
  // No update required if parent field is absent.
  if (empty($parent_field)) {
    return FALSE;
  }
  $parent_new = $wrapper->$parent_field->value();
  $new_id = (isset($parent_new->nid)) ? $parent_new->nid : NULL;

  $wrapper = entity_metadata_wrapper('node', $node->original);
  $parent_old = $wrapper->$parent_field->value();

  $old_id = (isset($parent_old->nid)) ? $parent_old->nid : NULL;

  return ($new_id != $old_id);
}

/**
 * Helper function to get all child nids under current node.
 */
function pm_permission_get_all_child_nids($nid) {
  $parents = array($nid);
  $result = NULL;
  _pm_permission_get_all_child_nids($parents, $result);
  return $result;
}

/**
 * Recursively hunt for child nodes of pmorganization.
 */
function _pm_permission_get_all_child_nids($nids, &$result = NULL) {
  static $depth = 0;
  $depth++;
  if ($depth >= 10) {
    return;
  }
  if ($result === NULL) {
    $result = array();
    $new_ids = $nids;
  }
  else {
    $new_ids = array_diff($nids, $result);
    $result = array_merge($result, $new_ids);
    if (empty($new_ids)) {
      return;
    }
  }
  $ids = array();
  foreach ($new_ids as $nid) {
    $enabled = pm_permission_get_enabled_types();
    if ($enabled) {
      foreach ($enabled as $type) {
        $parent_field = variable_get("pm_permission_field_parent_reference_for_{$type}", NULL);
        if ($parent_field) {
          $query = new EntityFieldQuery();
          $query->entityCondition('entity_type', 'node')
            ->entityCondition('bundle', array($type), 'IN')
            ->fieldCondition($parent_field, 'target_id', $nid)
            ->addMetaData('account', user_load(1));
          $r = $query->execute();
          if (isset($r['node'])) {
            $keys = array_keys($r['node']);
            $ids = array_merge($ids, $keys);
          }
        }
      }
    }
  }
  _pm_permission_get_all_child_nids($ids, $result);
}

/**
 * Custom batch operation for rebuilding permission.
 *
 * @see pm_permission_hunt_and_update_all_child_permission()
 */
function _pm_permission_node_access_rebuild_batch_operation($nids, &$context) {
  if (empty($context['sandbox'])) {
    // Initiate multistep processing.
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nids);
    $context['sandbox']['nids'] = drupal_map_assoc($nids);
    if (empty($nids)) {
      $context['finished'] = 1;
      return;
    }
  }

  $nids = array_slice($context['sandbox']['nids'], 0, 20);
  $nodes = node_load_multiple($nids, array(), TRUE);
  foreach ($nodes as $nid => $node) {
    // To preserve database integrity, only acquire grants if the node
    // loads successfully.
    if (!empty($node)) {
      node_access_acquire_grants($node);
    }
    unset($context['sandbox']['nids'][$nid]);
    $context['sandbox']['progress']++;
    $context['message'] = t('Now processing %node', array('%node' => $node->title));
  }

  // Multistep processing : report progress.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Custom batch finish callback.
 *
 * @see pm_permission_hunt_and_update_all_child_permission()
 */
function _pm_permission_node_access_rebuild_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('The <em>Project Management</em> permissions have been rebuilt.'));
  }
  else {
    drupal_set_message(t('The <em>Project Management</em> permissions have not been properly rebuilt.'), 'error');
  }
  cache_clear_all();
}
