<?php

/**
 * @file
 * Define the admin form for setting pseudo-field options.
 */

/**
 * Implements hook_form().
 *
 * @see field_concat_display_settings_form_submit()
 * @see field_concat_display_update_field_validate()
 * @see field_concat_display_new_field_validate()
 * @see field_concat_display_update_field()
 * @see field_concat_display_remove_field()
 *
 * @ingroup form
 */
function field_concat_display_settings_form($form, &$form_state, $type) {

  // Catch the node type (see hook_menu... %node_type).
  // Let the form know about it.
  $form['#node_type'] = $type;

  $this_node_type = $form['#node_type']->type;

  // Get all the field settings for this content type.
  $settings = variable_get("field_concat_display_settings_{$this_node_type}", array());

  if (!empty($settings)) {
    foreach ($settings as $instance => $data) {

      // Display a table for each concatenated field and their subfields.
      // $instance refers to a concatenated field.
      $form[$instance]["prefix_suffix_table_{$instance}"] = array(
        '#type' => 'container',
        '#tree' => TRUE,
        '#theme' => 'table',
        '#header' => array($instance, '', ''),
        '#rows' => array(),
      );

      foreach ($data as $key => $el) {
        // $el refers to a sub-field -- 2 or more sub-fields make up a
        // concatenated field.
        if (!empty($el)) {

          // Make sure the prefix/suffix/weight have a value.
          if (!isset($el['prefix'])) {
            $default_prefix = '';
          }
          else {
            $default_prefix = $el['prefix'];
          }
          $prefix = array(
            '#type' => 'textfield',
            '#title' => t('@key: PREFIX', array('@key' => $key)),
            '#default_value' => $default_prefix,
          );

          if (!isset($el['suffix'])) {
            $default_suffix = '';
          }
          else {
            $default_suffix = $el['suffix'];
          }
          $suffix = array(
            '#type' => 'textfield',
            '#title' => t('@key: SUFFIX', array('@key' => $key)),
            '#default_value' => $default_suffix,
          );

          if (!isset($el['weight'])) {
            $default_weight = 0;
          }
          else {
            $default_weight = $el['weight'];
          }
          $weight = array(
            '#type' => 'textfield',
            '#title' => t('@key: WEIGHT', array('@key' => $key)),
            '#description' => t('Must be zero or greater'),
            '#default_value' => $default_weight,
            '#size' => 2,
            '#maxlength' => 2,
          );

          $form[$instance]["prefix_suffix_table_{$instance}"][$key] = array(
            "prefix_{$key}" => &$prefix,
            "suffix_{$key}" => &$suffix,
            "weight_{$key}" => &$weight,
          );

          $form[$instance]["prefix_suffix_table_{$instance}"]['#rows'][$key] = array(
            array('data' => &$prefix),
            array('data' => &$suffix),
            array('data' => &$weight),
          );

          unset($prefix);
          unset($suffix);
          unset($weight);
        }
      }

      // Setup the remove and update buttons.
      $remove = array(
        '#type' => 'submit',
        '#name' => "remove_{$instance}",
        '#value' => t('Remove @instance', array('@instance' => $instance)),
        '#submit' => array('field_concat_display_remove_field'),
      );
      $form[$instance][] = array(//["prefix_suffix_table_{$instance}"]['remove'][] = array(
        "remove_{$instance}" => &$remove,
      );

      $update = array(
        '#type' => 'submit',
        '#name' => "update_{$instance}",
        '#value' => t('Update @instance', array('@instance' => $instance)),
        '#submit' => array('field_concat_display_update_field'),
        '#validate' => array('field_concat_display_update_field_validate'),
      );

      $form[$instance][] = array(//["prefix_suffix_table_{$instance}"]['update'][] = array(
        "update_{$instance}" => &$update,
      );
      /*
      $form[$instance]['remove_update'] = array(//["prefix_suffix_table_{$instance}"]['#rows']['remove_update'] = array(
        array('data' => &$remove),
        array('data' => &$update),
        array('data' => ''),
      );
       */
      unset($remove);
      unset($update);
    }
  }

  // Create the form elements for adding new concatenated fields.
  $form['new_field'] = array(
    '#type' => 'container',
  );

  $form['new_field']['label'] = array(
    '#type' => 'item',
    '#title' => t('Create a new field:'),
  );

  // Get the list of fields which appears on this content type
  // so we can select fields to concatenate.
  $fields = field_info_instances('node', $this_node_type);
  $field_names = array();
  foreach ($fields as $key => $val) {
    $field_names[$key] = $key;
  }

  $form['new_field']['field_concat_display_field_name'] = array(
    '#type' => 'textfield',
    '#title' => t('What should the machine_name/label of this field be?'),
    '#default_value' => (isset($settings['field_name'])) ? $settings['field_name'] : '',
  );

  $form['new_field']['field_concat_display_select_fields'] = array(
    '#type' => 'checkboxes',
    '#options' => $field_names,
    '#title' => 'Select the fields you wish to concatenate: ',
  );

  $form['field_concat_display_new_field_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save New Field'),
    '#validate' => array('field_concat_display_new_field_validate'),
  );

  return $form;

}

/**
 * Submit function for adding new fields.
 *
 * @see field_concat_display_new_field_validate()
 */
function field_concat_display_settings_form_submit($form, &$form_state) {
  $this_node_type = $form['#node_type']->type;
  $settings = variable_get("field_concat_display_settings_{$this_node_type}", array());

  $new_field_name = $form_state['values']['field_concat_display_field_name'];

  // Count the number of viable fields... as we increment, we want to use
  // this counter as the value for the field weights.
  $field_counter = 0;
  foreach ($form_state['values']['field_concat_display_select_fields'] as $old_field_name) {
    if (!empty($old_field_name)) {
      $new_field[$old_field_name] = array(
        'weight' => $field_counter,
        'prefix' => '',
        'suffix' => '',
      );
      $field_counter++;
    }
  }

  $settings[$new_field_name] = $new_field;

  variable_set("field_concat_display_settings_{$this_node_type}", $settings);

  $var_names = variable_get('field_concat_display_var_names', array());
  if (!in_array($this_node_type, $var_names)) {
    $var_names[] = $this_node_type;
    variable_set('field_concat_display_var_names', $var_names);
  }

  drupal_set_message(t("Field $new_field_name has been saved!"));

}

/**
 * Validater for updating existing fields.
 *
 * @see field_concat_display_update_field()
 */
function field_concat_display_update_field_validate($form, &$form_state) {

  $triggering_element = $form_state['triggering_element'];
//jprint($triggering_element);
  $which_table = 'prefix_suffix_table_' . $triggering_element['#array_parents'][0];

  // Prevent duplicate weights so the data doesn't get overwritten.
  $weights = array();
//jprint($form_state['values']);
  foreach ($form_state['values'][$which_table] as $key => $el) {
    if ($key != 'remove' && $key != 'update') {
      $weights[$key] = $el["weight_{$key}"];
    }
  }
  $counted_weights = array_count_values($weights);

  $weight_error = FALSE;
  foreach ($counted_weights as $key => $cw) {
    if ($cw > 1) {
      foreach ($form_state['values'][$which_table] as $name => $data) {
        if ($name != 'remove' && $name != 'update') {
          if ($data["weight_{$name}"] == $key) {
            $weight_error = TRUE;
            form_set_error("$which_table][$name][weight_{$name}");
          }
        }
      }
    }
  }
  if ($weight_error) {
    drupal_set_message(t('Weights must be unique, positive integers.'),
    'error');
  }
  /**/

  foreach ($form_state['values'][$which_table] as $key => $el) {
    if ($key != 'remove' && $key != 'update') {
      if (!is_numeric($el["weight_{$key}"])) {
        form_set_error("$which_table][$key][weight_{$key}", t('Weight must be a positive integer.'));
      }

      if ($el["weight_$key"] < 0) {
        form_set_error("$which_table][$key][weight_{$key}", t('Weight must be zero or greater.'));
      }
    }
  }
}

/**
 * Validater for adding new fields.
 *
 * @see field_concat_display_settings_form_submit()
 */
function field_concat_display_new_field_validate($form, &$form_state) {

  // Make sure the field name is not empty.
  if (empty($form_state['values']['field_concat_display_field_name'])) {
    form_set_error('field_concat_display_field_name', t('New fields must have a name!'));
  }

  // Make sure the field name contains only alphanumerics and underscores.
  if (preg_match('/^[_a-zA-Z]+[_a-zA-Z0-9]*$/',
    $form_state['values']['field_concat_display_field_name']) === 0) {
    form_set_error("field_concat_display_field_name", t('Field name must contain only alphanumeric characters and underscores.'));
  }

  // Make sure at least 2 fields are selected to be concatenated.
  $counter = 0;
  foreach ($form_state['values']['field_concat_display_select_fields'] as $val) {
    if ($val !== 0) {
      $counter++;
    }
  }
  if ($counter < 2) {
    form_set_error('', t('Please select at least 2 fields to concatenate!'));
  }
}

/**
 * Submit function for updating a field.
 *
 * @see field_concat_display_update_field_validate()
 */
function field_concat_display_update_field($form, &$form_state) {

  $this_node_type = $form['#node_type']->type;
  $settings = variable_get("field_concat_display_settings_{$this_node_type}", array());

  $triggering_element = $form_state['triggering_element'];
  $trigger = $triggering_element['#name'];
  $trigger = preg_replace('/update_/', '', $trigger);
  $which_table = 'prefix_suffix_table_' . $triggering_element['#array_parents'][0];
  $new_values = $form_state['values'];

  unset($new_values[$which_table]['remove']);
  unset($new_values[$which_table]['update']);

  foreach ($new_values[$which_table] as $field_name => $field) {
    foreach ($field as $el => $val) {
      $parts = explode('_', $el);
      $settings[$trigger][$field_name][$parts[0]] = $val;
    }
  }

  variable_set("field_concat_display_settings_{$this_node_type}", $settings);
  drupal_set_message(t("Field $trigger has been updated!"));
}

/**
 * Submit function for removing existing fields via the admin form.
 */
function field_concat_display_remove_field($form, &$form_state) {
  $this_node_type = $form['#node_type']->type;
  $settings = variable_get("field_concat_display_settings_{$this_node_type}", array());

  $triggering_element = $form_state['triggering_element'];
  $field = $triggering_element['#array_parents'][0];

  unset($settings[$field]);

  variable_set("field_concat_display_settings_{$this_node_type}", $settings);
  drupal_set_message(t("Field $field has been removed!"));
}

/**
 * Implements hook_form().
 *
 * The form displayed when the 'delete' is clicked from the 'Manage Fields' UI.
 *
 * @see field_concat_display_confirm_delete_form_submit()
 */
function field_concat_display_confirm_delete_form($form, $form_state, $node_type, $field) {

  $message = t("Do you really want to delete '@node_type:@field'?", array('@node_type' => $node_type, '@field' => $field));
  $base_path = 'admin/structure/types/manage';
  $referer_path = "{$base_path}/{$node_type}/fields";

  $form['hidden_node_type'] = array(
    '#type' => 'hidden',
    '#value' => $node_type,
  );
  $form['hidden_field'] = array(
    '#type' => 'hidden',
    '#value' => $field,
  );

  return confirm_form($form, $message, $referer_path);
}

/**
 * Submit handler for field_concat_display_confirm_delete_form().
 */
function field_concat_display_confirm_delete_form_submit($form, &$form_state) {
  $node_type = $form_state['values']['hidden_node_type'];
  $field = $form_state['values']['hidden_field'];

  $settings = variable_get("field_concat_display_settings_{$node_type}", array());

  if (!is_null($settings)) {
    unset($settings[$field]);
    variable_set("field_concat_display_settings_{$node_type}", $settings);
    $node_type = preg_replace('/_/', '-', $node_type);
    drupal_set_message(t("Field: @field has been deleted.", array('@field' => $field)));
    $form_state['redirect'] = "admin/structure/types/manage/{$node_type}/fields";
  }
}
