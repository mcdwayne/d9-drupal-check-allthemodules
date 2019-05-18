<?php
/**
 * @file
 * Administrative pages for the OG SM Breadcrumb module.
 */

/**
 * Default administration form when the og_sm_feature module is not enabled.
 *
 * @param array $form
 *   The form structure.
 * @param array $form_state
 *   The form state.
 *
 * @return array
 *   The form structure.
 */
function og_sm_breadcrumb_admin(array $form, array &$form_state) {
  $form['info'] = array(
    '#markup' => t('Global settings for Site breadcrumbs.'),
    '#prefix' => '<p>',
    '#suffix' => '</p>',
  );
  $form['og_sm_breadcrumb_enable'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable breadcrumbs for Sites'),
    '#description' => t('Enabling this feature will add the breadcrumb to the Site pages.'),
    '#default_value' => variable_get('og_sm_breadcrumb_enable'),
  );

  $defaults = array(
    'hide_on_frontpage' => variable_get('og_sm_breadcrumb_hide_on_frontpage', 1),
    'force_home' => variable_get('og_sm_breadcrumb_force_home', 1),
    'append_title' => variable_get('og_sm_breadcrumb_append_title', 1),
  );

  $form = _og_sm_breadcrumb_admin_form($form, $defaults);
  $form['options']['#states'] = array(
    'invisible' => array(
      ':input[name="og_sm_breadcrumb_enable"]' => array('checked' => FALSE),
    ),
  );

  return system_settings_form($form);
}

/**
 * Global feature settings form.
 *
 * @param array $form
 *   The form structure.
 * @param array $form_state
 *   The form state.
 *
 * @return array
 *   The form structure.
 */
function og_sm_breadcrumb_admin_feature(array $form, array &$form_state) {
  $form['info'] = array(
    '#markup' => t('Default settings for the Breadcrumb feature.'),
    '#prefix' => '<p>',
    '#suffix' => '</p>',
  );

  $defaults = array(
    'hide_on_frontpage' => variable_get('og_sm_breadcrumb_hide_on_frontpage', 1),
    'force_home' => variable_get('og_sm_breadcrumb_force_home', 1),
    'append_title' => variable_get('og_sm_breadcrumb_append_title', 1),
  );

  return system_settings_form(_og_sm_breadcrumb_admin_form($form, $defaults));
}

/**
 * Site specific settings form.
 *
 * @param array $form
 *   The form structure.
 * @param array $form_state
 *   The form state.
 * @param object $site
 *   The Site object.
 *
 * @return array
 *   The form structure.
 */
function og_sm_breadcrumb_og_admin_feature(array $form, array &$form_state, $site) {
  if (module_exists('og_sm_feature')) {
    og_sm_feature_set_breadcrumb($site);
  }
  $sid = $site->nid;

  $form['info'] = array(
    '#markup' => t('Site settings for the Breadcrumb feature.'),
    '#prefix' => '<p>',
    '#suffix' => '</p>',
  );

  $defaults = array(
    'hide_on_frontpage' => og_sm_variable_get($sid, 'og_sm_breadcrumb_hide_on_frontpage', 0),
    'force_home' => og_sm_variable_get($sid, 'og_sm_breadcrumb_force_home', 1),
    'append_title' => og_sm_variable_get($sid, 'og_sm_breadcrumb_append_title', 1),
  );

  $form = _og_sm_breadcrumb_admin_form($form, $defaults);

  // Append the breadcrumb settings part.
  _og_sm_breadcrumb_og_admin_form_custom_breadcrumb($form, $form_state, $site);

  // Create the variable settings form.
  $form = og_sm_variable_settings_form(
    $form,
    $form_state,
    $site
  );

  // Prepend & append submit functions to:
  // - Cleanup the values.
  // - Clear the Site cache.
  $form['#submit'] = array_merge(
    array('og_sm_breadcrumb_og_admin_submit_before'),
    $form['#submit']
  );

  return $form;
}

/**
 * Shared form elements for the Global & OG settings.
 *
 * @param array $form
 *   The form structure.
 * @param array $defaults
 *   The default values for the form.
 *
 * @return array
 *   The form structure.
 */
function _og_sm_breadcrumb_admin_form(array $form, array $defaults) {
  // Options.
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Options'),
  );
  $form['options']['og_sm_breadcrumb_hide_on_frontpage'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hide on Site frontpage'),
    '#description' => t('Do not show the breadcrumb on the Site homepage.'),
    '#default_value' => $defaults['hide_on_frontpage'],
  );
  $form['options']['og_sm_breadcrumb_force_home'] = array(
    '#type' => 'checkbox',
    '#title' => t('Home link to Site frontpage'),
    '#description' => t('Force the root part of the breadcrumb to link to the Site frontpage, this option will be ignored when the breadcrumb root has been overridden.'),
    '#default_value' => $defaults['force_home'],
  );
  $form['options']['og_sm_breadcrumb_append_title'] = array(
    '#type' => 'checkbox',
    '#title' => t('Append the page title to the breadcrumb trail'),
    '#description' => t('The page title will be appended to the breadcrumb trail.'),
    '#default_value' => $defaults['append_title'],
  );

  return $form;
}

/**
 * Add the extra form parts to add custom breadcrumb starting point(s).
 *
 * @param array $form
 *   The form.
 * @param array $form_state
 *   The form state.
 * @param object $site
 *   The Site object.
 */
function _og_sm_breadcrumb_og_admin_form_custom_breadcrumb(array &$form, array &$form_state, $site) {
  // Save these in form_state for later.
  $form_state['site'] = $site;

  $override_root = og_sm_variable_get($site->nid, 'og_sm_breadcrumb_override_root', 0);
  $override_parts = og_sm_variable_get($site->nid, 'og_sm_breadcrumb_override_root_parts', array());

  // Initialize our form state.
  if (!isset($form_state['input']['og_sm_breadcrumb_override_root_parts'])) {
    $form_state['input']['og_sm_breadcrumb_override_root_parts']['parts'] = $override_parts;
    $form_state['row_count'] = count($override_parts);

    if ($form_state['row_count'] == 0) {
      ++$form_state['row_count'];
    }
  }

  $form['root'] = array(
    '#type' => 'fieldset',
    '#title' => t('Custom breadcrumb'),
  );
  $form['root']['og_sm_breadcrumb_override_root'] = array(
    '#type' => 'checkbox',
    '#title' => t('Manually set the breadcrumb root'),
    '#description' => t('If checked, you can specify the root of the breadcrumb for this theme.'),
    '#default_value' => $override_root,
  );

  $wrapper_id = 'breadcrumb-parts-wrapper';
  $form['root']['og_sm_breadcrumb_override_root_parts'] = array(
    '#type' => 'container',
    // Hide the settings when the override root checkbox is disabled.
    '#states' => array(
      'invisible' => array(
        ':input[name="og_sm_breadcrumb_override_root"]' => array('checked' => FALSE),
      ),
    ),
    '#tree' => TRUE,
  );

  $parts_table = array(
    '#theme' => 'og_sm_breadcrumb_og_admin_root_parts',
    '#prefix' => '<div id="' . $wrapper_id . '">',
    '#suffix' => '</div>',
  );

  // Sort the parts first, this to keep sorting in ajax callbacks (add/delete).
  if (is_array($form_state['input']['og_sm_breadcrumb_override_root_parts']['parts'])) {
    usort($form_state['input']['og_sm_breadcrumb_override_root_parts']['parts'], 'drupal_sort_weight');
  }

  // Add the root parts.
  for ($i = 0; $i < $form_state['row_count']; $i++) {
    // Set the default weight to the number of parts. Since the delta of the
    // draggable table is 100, already ordered parts will start at -100. New
    // parts will always be placed at the end of the weights stack,
    // e.g. 2,3,4, ...
    $default_values = isset($form_state['input']['og_sm_breadcrumb_override_root_parts']['parts'][$i])
      ? $form_state['input']['og_sm_breadcrumb_override_root_parts']['parts'][$i]
      : array('text' => '', 'path' => '', 'weight' => $form_state['row_count']);

    $parts_table[$i]['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text'),
      '#title_display' => 'invisible',
      '#default_value' => $default_values['text'],
      '#element_validate' => array('og_sm_breadcrumb_og_admin_form_validate_root_parts_text'),
    );
    $parts_table[$i]['path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path'),
      '#title_display' => 'invisible',
      '#default_value' => $default_values['path'],
      '#element_validate' => array('og_sm_breadcrumb_og_admin_form_validate_root_parts_path'),
    );
    $parts_table[$i]['operations'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#name' => 'delete_breadcrumb_part_' . $i,
      '#validate' => array(),
      '#limit_validation_errors' => array(),
      '#submit' => array('og_sm_breadcrumb_og_admin_delete_item'),
      '#ajax' => array(
        'callback' => 'og_sm_breadcrumb_og_admin_js_callback',
        'wrapper' => $wrapper_id,
      ),
    );
    $parts_table[$i]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#title_display' => 'invisible',
      '#default_value' => $default_values['weight'],
      '#delta' => 100,
      '#attributes' => array('class' => array('item-weight')),
    );
  }

  // Add the parts to the form.
  $form['root']['og_sm_breadcrumb_override_root_parts']['parts'] = $parts_table;

  // Add an Add new item button to the form.
  $form['root']['og_sm_breadcrumb_override_root_parts']['add'] = array(
    '#type' => 'submit',
    '#value' => t('Add part'),
    '#validate' => array(),
    '#limit_validation_errors' => array(),
    '#submit' => array('og_sm_breadcrumb_og_admin_add_item'),
    '#ajax' => array(
      'callback' => 'og_sm_breadcrumb_og_admin_js_callback',
      'wrapper' => $wrapper_id,
    ),
  );

  $form['#validate'][] = 'og_sm_breadcrumb_og_admin_form_validate';
}

/**
 * Validate handler for the breadcrumb form.
 *
 * @param array $form
 *   The form.
 * @param array $form_state
 *   The form state.
 */
function og_sm_breadcrumb_og_admin_form_validate($form, &$form_state) {
  $values = $form_state['values'];

  // If root override is selected, there should be at least 1 parts.
  if ($values['og_sm_breadcrumb_override_root']
    && empty($values['og_sm_breadcrumb_override_root_parts']['parts'])
  ) {
    form_set_error('root', t('Add at least one breadcrumb part.'));
    return;
  }
}

/**
 * Validator for the breadcrumb text field.
 *
 * @param array $element
 *   The element to validate.
 * @param array $form_state
 *   The form state.
 * @param array $form
 *   The form structure.
 */
function og_sm_breadcrumb_og_admin_form_validate_root_parts_text(array $element, array &$form_state, array $form) {
  // Validate only if the root is overridden.
  if (empty($form_state['values']['og_sm_breadcrumb_override_root'])) {
    return;
  }

  $value = trim($element['#value']);
  if ($value === '') {
    form_error(
      $element,
      t('The field %field is required.', array('%field' => t('Text')))
    );
  }
}

/**
 * Form element validator for the custom_home_url field.
 *
 * @param array $element
 *   The element to validate.
 * @param array $form_state
 *   The form state.
 * @param array $form
 *   The form structure.
 */
function og_sm_breadcrumb_og_admin_form_validate_root_parts_path(array $element, array &$form_state, array $form) {
  // Validate only if the root is overridden.
  if (empty($form_state['values']['og_sm_breadcrumb_override_root'])) {
    return;
  }

  $value = trim($element['#value']);

  // Path is required.
  if ($value === '') {
    form_error(
      $element,
      t('The field %field is required.', array('%field' => t('Path')))
    );
    return;
  }

  // Is external URL?
  if (url_is_external($value)) {
    return;
  }

  // Is valid Drupal path?
  $path_source = drupal_get_normal_path($value);
  if ($path_source && drupal_valid_path($path_source, FALSE)) {
    return;
  }

  // The provided URL is not valid.
  form_error(
    $element,
    t('The URL should be either a valid external URL or an existing internal path.')
  );
}

/**
 * Submit handler to clean up the values.
 *
 * @param array $form
 *   The form.
 * @param array $form_state
 *   The form state.
 */
function og_sm_breadcrumb_og_admin_submit_before($form, &$form_state) {
  form_state_values_clean($form_state);

  // Remove non value elements of the root_parts.
  $parts = $form_state['values']['og_sm_breadcrumb_override_root_parts']['parts'];
  foreach ($parts as $key => $part) {
    unset($part['operation']);
  }

  // Sort the items.
  usort($parts, 'drupal_sort_weight');

  // Update the values in the form_state.
  $form_state['values']['og_sm_breadcrumb_override_root_parts'] = $parts;
}

/**
 * Submit function for the add item button.
 *
 * @param array $form
 *   The form.
 * @param array $form_state
 *   The form state.
 */
function og_sm_breadcrumb_og_admin_add_item($form, &$form_state) {
  $form_state['row_count']++;
  $form_state['rebuild'] = TRUE;
}

/**
 * Submit function for the delete item button.
 *
 * @param array $form
 *   The form.
 * @param array $form_state
 *   The form state.
 */
function og_sm_breadcrumb_og_admin_delete_item($form, &$form_state) {
  if (!preg_match('#^delete_breadcrumb_part_(\d+)$#', $form_state['triggering_element']['#name'], $matches)) {
    return;
  }
  if (isset($form_state['input']['og_sm_breadcrumb_override_root_parts']['parts'][$matches[1]])) {
    unset($form_state['input']['og_sm_breadcrumb_override_root_parts']['parts'][$matches[1]]);
    $form_state['input']['og_sm_breadcrumb_override_root_parts']['parts'] = array_values(
      $form_state['input']['og_sm_breadcrumb_override_root_parts']['parts']
    );
    $form_state['row_count']--;
  }

  $form_state['rebuild'] = TRUE;
}

/**
 * AJAX callback that returns the updated component.
 *
 * @param array $form
 *   The form.
 * @param array $form_state
 *   The form state.
 *
 * @return array
 *   The element to update.
 */
function og_sm_breadcrumb_og_admin_js_callback($form, $form_state) {
  return $form['root']['og_sm_breadcrumb_override_root_parts']['parts'];
}

/**
 * Theme function for the breadcrumb parts table.
 *
 * @param array $variables
 *   The variables array.
 *
 * @return string
 *   The rendered content.
 */
function theme_og_sm_breadcrumb_og_admin_root_parts($variables) {
  $element = $variables['element'];
  drupal_add_tabledrag('breadcrumb-parts-table', 'order', 'sibling', 'item-weight');

  $header = array(
    'text' => t('Text'),
    'path' => t('Path'),
    'operations' => t('Operations'),
    'weight' => t('Weight'),
  );

  $rows = array();
  foreach (element_children($element) as $key) {
    $row = array();
    $row['data'] = array();
    foreach ($header as $field_name => $title) {
      $row['data'][] = drupal_render($element[$key][$field_name]);
      $row['class'] = array('draggable');
    }
    $rows[] = $row;
  }

  return theme('table', array(
    'header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'breadcrumb-parts-table'),
    'empty' => t('There are no parts added yet.'),
  ));
}
