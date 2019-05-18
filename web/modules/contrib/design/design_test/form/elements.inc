<?php

/**
 * @file
 * Form element design test.
 */

/**
 * Elements.
 */
function design_test_form_elements($form, &$form_state) {
  $options = array(
    'one' => design_test_create_greeking(3, FALSE),
    'two' => design_test_create_greeking(3, FALSE),
    'three' => design_test_create_greeking(3, FALSE),
  );
  $prefix = 'Prefix';
  $suffix = 'Suffix';

  // Regular form elements.
  $form['elements'] = array(
    '#type' => 'item',
    '#title' => 'Form elements',
    '#field_prefix' => 'Standard form elements without a container.',
  );
  $form['elements']['checkbox'] = array(
    '#type' => 'checkbox',
    '#title' => 'Checkbox',
    '#description' => design_test_create_greeking(6),
  );
  $form['elements']['checkbox2'] = array(
    '#type' => 'checkbox',
    '#title' => 'Checkbox',
    '#description' => design_test_create_greeking(6),
  );
  $form['elements']['checkboxes'] = array(
    '#type' => 'checkboxes',
    '#title' => 'Checkboxes',
    '#description' => design_test_create_greeking(6),
    '#options' => $options,
  );
  $form['elements']['checkboxes']['two'] = array(
    '#description' => design_test_create_greeking(6),
  );
  $form['elements']['file'] = array(
    '#type' => 'file',
    '#title' => 'File',
  );
  $form['elements']['machine_name_source'] = array(
    '#type' => 'textfield',
    '#title' => 'Machine name source',
  );
  $form['elements']['machine_name'] = array(
    '#type' => 'machine_name',
    '#machine_name' => array(
      'source' => array('elements', 'machine_name_source'),
    ),
  );
  $form['elements']['managed_file'] = array(
    '#type' => 'managed_file',
    '#title' => 'Managed file',
  );
  $form['elements']['markup'] = array(
    '#markup' => '<p>This is a markup element.</p>',
  );
  $form['elements']['password'] = array(
    '#type' => 'password',
    '#description' => design_test_create_greeking(6),
  );
  $form['elements']['password_confirm'] = array(
    '#type' => 'password_confirm',
    '#description' => design_test_create_greeking(6),
  );
  $form['elements']['radio'] = array(
    '#type' => 'radio',
    '#title' => 'Radio',
    '#description' => design_test_create_greeking(6) . 'But why would you ever use a single radio?',
  );
  $form['elements']['radios'] = array(
    '#type' => 'radios',
    '#title' => 'Radios',
    '#options' => $options,
    '#description' => design_test_create_greeking(6),
  );
  $form['elements']['radios']['two'] = array(
    '#description' => design_test_create_greeking(6),
  );
  $form['elements']['select'] = array(
    '#type' => 'select',
    '#title' => 'Select',
    '#description' => design_test_create_greeking(6),
    '#options' => $options,
  );
  $form['elements']['select_multiple'] = array(
    '#type' => 'select',
    '#title' => 'Select (multiple)',
    '#description' => design_test_create_greeking(6),
    '#options' => $options,
    '#multiple' => TRUE,
  );
  $form['elements']['textfield'] = array(
    '#type' => 'textfield',
    '#title' => 'Textfield',
    '#default_value' => design_test_create_greeking(6, FALSE),
    '#description' => design_test_create_greeking(6),
  );
  $form['elements']['textarea'] = array(
    '#type' => 'textarea',
    '#title' => 'Textarea',
    '#default_value' => design_test_create_greeking(40),
    '#description' => design_test_create_greeking(6),
  );
  $form['elements']['text_format'] = array(
    '#type' => 'text_format',
    '#title' => 'Text format',
    '#default_value' => design_test_create_greeking(40),
    '#description' => design_test_create_greeking(6),
  );

  // Prefixes and suffixes.
  $form['surrounding'] = array(
    '#type' => 'item',
    '#title' => 'Prefixes and suffixes',
  );
  $form['surrounding']['select'] = array(
    '#type' => 'select',
    '#title' => 'Select label',
    '#options' => $options,
    '#field_prefix' => $prefix,
    '#field_suffix' => $suffix,
    '#description' => design_test_create_greeking(6),
  );
  $form['surrounding']['textfield'] = array(
    '#type' => 'textfield',
    '#title' => 'Textfield label',
    '#size' => 5,
    '#field_prefix' => $prefix,
    '#field_suffix' => $suffix,
    '#description' => design_test_create_greeking(6),
  );
  $form['surrounding']['textfield_default'] = array(
    '#type' => 'textfield',
    '#title' => 'Textfield label',
    '#field_prefix' => $prefix,
    '#field_suffix' => $suffix,
    '#description' => design_test_create_greeking(6),
  );
  $form['surrounding']['textarea'] = array(
    '#type' => 'textarea',
    '#title' => 'Textarea label',
    '#field_prefix' => $prefix,
    '#field_suffix' => $suffix,
    '#description' => design_test_create_greeking(6),
  );

  // Inline elements.
  $form['inline'] = array(
    '#type' => 'item',
    '#title' => 'Inline elements',
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['inline']['container'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['inline']['container']['select'] = array(
    '#type' => 'select',
    '#title' => 'Select',
    '#options' => $options,
  );
  $form['inline']['container']['textfield'] = array(
    '#type' => 'textfield',
    '#title' => 'Textfield',
    '#options' => $options,
  );

  // Use the current form and turn the containers into vertical tabs.
  $form['tabs']['#type'] = 'vertical_tabs';
  foreach (element_children($form) as $key) {
    $form['tabs'][$key] = $form[$key];
    $form['tabs'][$key]['#type'] = 'fieldset';
    $form['tabs'][$key]['#group'] = 'tabs';
    // #type 'machine_name' relies on the internal #array_parents of the form.
    if (isset($form['tabs'][$key]['machine_name'])) {
      $form['tabs'][$key]['machine_name']['#machine_name']['source'] = array('tabs', $key, 'machine_name_source');
    }
  }

  // Form actions.
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => 'Submit');
  $form['actions']['preview'] = array('#type' => 'button', '#value' => 'Button');
  $form['actions']['cancel'] = array('#type' => 'link', '#title' => 'Cancel', '#href' => '');

  return $form;
}
