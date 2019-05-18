<?php
/**
 * @file
 * Administration page for adding and editing scripts.
 */

/**
 * Menu Callback function for managing scripts.
 * @param array $form_state
 * @param array $script
 * @param string $op
 * @return string
 */
function scriptjunkie_manage($form_state, $script = NULL, $op = 'add') {
  $roles = user_roles();
  $role_options = array();
  foreach ($roles as $rid => $name) {
    $role_options[$rid] = $name;
  }

  $script = (!$script) ? scriptjunkie_get_settings() : $script;

  $access = user_access('use PHP for scriptjunkie visibility');
  $mode = $script['pages']['mode'];
  $pages = $script['pages']['list'];

  $form = array(
    '#tree' => TRUE
  );

  $form['general'] = array(
    '#type' => 'fieldset',
    '#title' => t('General settings'),
    '#collapsible' => FALSE,
  );
  $form['general']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Namespace'),
    '#description' => t('For internal use, a unique namespace, this can only be set once.  Must be Unique and may only use lowercase alphanumeric characters and underscores (_).'),
    '#disabled' => !empty($script['name']),
    '#tree' => FALSE,
    '#max_length' => 25,
    '#required' => empty($script['name']),
    '#default_value' => $script['name']
  );
  $form['general']['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#description' => t('A title to be used within the administration interface.'),
    '#required' => TRUE,
    '#default_value' => $script['general']['title']
  );
  $form['general']['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#description' => t('Used to better identify the scripts use.'),
    '#default_value' => $script['general']['description']
  );
  $form['code'] = array(
    '#type' => 'fieldset',
    '#title' => t('Code'),
    '#collapsible' => FALSE
  );
  $form['code']['scope'] = array(
    '#type' => 'select',
    '#title' => t('Scope'),
    '#description' => t('The location in which to place the script (header or footer).'),
    '#options' => array(
      'footer' => t('Footer'),
      'header' => t('Header')
    ),
    '#default_value' => $script['scope'],
    '#required' => TRUE,
    '#tree' => FALSE
  );
  $form['code']['script'] = array(
    '#type' => 'textarea',
    '#title' => t('Script'),
    '#rows' => 10,
    '#cols' => 45,
    '#required' => TRUE,
    '#description' => '<p>' . t('Enter the code or script you wish to load on the bottom of your page(s).') . '</p>',
    '#default_value' => $script['script'],
    '#tree' => FALSE
  );
  $form['roles'] = array(
    '#type' => 'fieldset',
    '#title' => t('Roles'),
    '#collapsible' => FALSE,
  );
  $form['roles']['visibility'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Remove script for specific roles'),
    '#default_value' => $script['roles']['visibility'],
    '#options' => $role_options,
    '#description' => t('Remove script only for the selected role(s). If none of the roles are selected, all roles will have the script. If a user has none of the roles checked, that user will have the script.'),
  );
  $form['pages'] = array(
    '#type' => 'fieldset',
    '#title' => t('Page Visibility'),
    '#collapsible' => FALSE,
  );
  if ($mode == 2 && !$access) {
    $form['pages'] = array();
    $form['pages']['mode'] = array('#type' => 'value', '#value' => 2);
    $form['pages']['list'] = array('#type' => 'value', '#value' => $pages);
  }
  else {
    $options = array(t('Add to every page except the listed pages.'), t('Add to the listed pages only.'));
    $description = t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>'));

    if ($access) {
      $options[] = t('Add if the following PHP code returns <code>TRUE</code> (PHP-mode, experts only).');
      $description .= ' '. t('If the PHP-mode is chosen, enter PHP code between %php. Note that executing incorrect PHP-code can break your Drupal site.', array('%php' => '<?php ?>'));
    }
    $form['pages']['mode'] = array(
      '#type' => 'radios',
      '#title' => t('Add script to specific pages'),
      '#options' => $options,
      '#default_value' => $mode,
    );
    $form['pages']['list'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#default_value' => $pages,
      '#description' => $description,
      '#wysiwyg' => FALSE,
    );
  }
  if ($op == 'edit') { // editing script
    $form['script_id'] = array(
      '#value' => $script['name'],
      '#type' => 'value'
    );

    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update'),
      '#submit' => array('scriptjunkie_manage_submit'),
    );


    $form['buttons']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => array('scriptjunkie_form_delete'),
    );
  }
  else { // adding new script
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add'),
      '#submit' => array('scriptjunkie_manage_submit'),
    );
  }

  return $form;
}

/**
 * Validation function for scriptjunkie_manage form.
 * @param array $form
 * @param array $form_state
 */
function scriptjunkie_manage_validate($form, &$form_state) {
  foreach ($form_state['values'] as $id => $value) {
    if (in_array($id, array('op', 'buttons', 'form_build_id', 'form_token', 'form_id'))) {
      unset($form_state['values'][$id]);
    }
    elseif (is_array($value)) {
      $form_state['values'][$id] = serialize($form_state['values'][$id]);
    }
  }
  $name = $form_state['values']['name'];
  if (!empty($name) && !scriptjunkie_is_valid_namespace($name)) {
    form_set_error('name', t('Script namespace may only contain lowercase letters, numbers and underscores (a-z, 0-9, and _).'));
  }
  if (!empty($name) && $existing = scriptjunkie_not_unique_namespace($name)) {
    form_set_error('name', t('Script namespace must be unique, the namespace "%name" has already been used for the script titled "%title".', array('%name' => $name, '%title' => $existing['title'])));
  }
}

/**
 * Form submission callback for scriptjunkie_manage form.
 * @param array $form
 * @param array $form_state
 */
function scriptjunkie_manage_submit($form, &$form_state) {
  if (isset($form_state['values']['script_id'])) {
    scriptjunkie_update_script($form_state['values']);
  }
  else {
    scriptjunkie_insert_script($form_state['values']);
  }
  $form_state['redirect'] = 'admin/settings/scriptjunkie';
}