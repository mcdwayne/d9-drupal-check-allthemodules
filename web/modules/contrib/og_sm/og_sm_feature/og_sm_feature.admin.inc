<?php

/**
 * @file
 * Global Administrative pages for og_sm_feature.
 */

/**
 * Form to set the default feature status for new Sites.
 *
 * @param array $form
 *   The form structure.
 * @param array $form_state
 *   The current form state.
 *
 * @return array
 *   The form structure.
 */
function og_sm_feature_admin_overview(array $form, array &$form_state) {
  // Information above the form.
  $form['info'] = array(
    '#markup' => t('Select the features that will be enabled by default when a new Site is created.'),
    '#prefix' => '<p>',
    '#suffix' => '</p>',
  );

  // Get a list of all available features.
  $features = og_sm_feature_info();
  if (!$features) {
    drupal_set_message(t('No features available on this platform.'), 'warning');
    $form['no_features'] = array(
      '#markup' => t('There are no features available.'),
      '#prefix' => '<div class="warning">',
      '#suffix' => '</div>',
    );
    return $form;
  }

  // Features list.
  $options = array();
  $defaults = array();
  foreach ($features as $name => $info) {
    $options[$name] = _og_sm_feature_admin_overview_row($info);

    $key = og_sm_feature_variable_key($name);
    $defaults[$name] = (int) variable_get($key, 0);
  }

  // Add the table data to the form.
  $form['features'] = array(
    '#type' => 'tableselect',
    '#header' => array(
      'name' => t('Feature'),
      'config' => NULL,
    ),
    '#options' => $options,
    '#default_value' => $defaults,
    '#multiple' => TRUE,
    '#js_select' => FALSE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Create a feature row for the features overview form.
 *
 * @param array $info
 *   The feature information.
 *
 * @return array
 *   The row structure.
 */
function _og_sm_feature_admin_overview_row(array $info) {
  // Create the row data array.
  $row_data = array(
    'feature' => array(
      '#markup' => $info['name'],
      '#prefix' => '<div class="feature"><strong>',
      '#suffix' => '</strong></div>',
    ),
  );
  if (!empty($info['description'])) {
    $row_data['description'] = array(
      '#markup' => $info['description'],
      '#prefix' => '<div class="description">',
      '#suffix' => '</div>',
    );
  }

  // Create the config link (if any).
  $config = NULL;
  if (!empty($info['global configuration'])) {
    $config = array(
      '#theme' => 'link',
      '#text' => t('Configuration'),
      '#path' => $info['global configuration'],
      '#options' => array(
        'attributes' => array(),
        'query' => drupal_get_destination(),
        'html' => FALSE,
      ),
    );
  }

  // Return the structure.
  return array(
    'name' => array('data' => $row_data),
    'config' => array('data' => $config),
  );
}

/**
 * Submit handler for the features overview form.
 *
 * @param array $form
 *   The form structure.
 * @param array $form_state
 *   The form state.
 */
function og_sm_feature_admin_overview_submit(array $form, array $form_state) {
  $features = $form_state['values']['features'];

  foreach ($features as $name => $state) {
    $key = og_sm_feature_variable_key($name);
    variable_set($key, (int) !empty($state));
  }

  drupal_set_message(t('Saved default Feature statuses.'));
}
