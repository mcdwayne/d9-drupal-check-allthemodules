<?php

require_once drupal_get_path('module', 'field_ui') . '/field_ui.admin.inc';

function drealty_fieldmap_primary_form($form, &$form_state, $connection = NULL, $entity_type = NULL, $class_id = NULL) {
// Check to see if anything has been stored.
  if (!isset($form_state['stage'])) {
    $form_state['stage'] = 'select_rets_fields';
  }
  $form = array();
  $form = drealty_fieldmap_get_header($form, $form_state);
  switch ($form_state['stage']) {
    case 'select_rets_fields':
      return drealty_fieldmap_first_form($form, $form_state, $connection, $entity_type, $class_id);
      break;
    case 'create_rets_entity_fields':
      return drealty_fieldmap_second_form($form, $form_state, $connection, $entity_type, $class_id);
      break;
    default:
      return drealty_fieldmap_first_form($form, $form_state, $connection, $entity_type, $class_id);
      break;
  }
  return $form;
}

function drealty_fieldmap_get_header($form, &$form_state) {

  $form_state['stage'] = isset($form_state['stage']) ? $form_state['stage'] : 1;

  $form_stages = array(
    'select_rets_fields' => 1,
    'create_rets_entity_fields' => 2,
  );

  if (isset($form_stages[$form_state['stage']])) {
    $current_step = $form_stages[$form_state['stage']];
  }
  else {
    $current_step = 1;
  }

  $stages = array(
    1 => array('data' => 'Pick RETS fields you want to import'),
    2 => array('data' => 'Map RETS fields to entity fields'),
  );

  $stages[$current_step]['class'] = array('active');

  $stages_list = theme('item_list', array('items' => $stages, 'type' => 'ol'));

  $form['header'] = array(
    '#type' => 'fieldset',
    '#title' => 'Field Mapping process steps:',
    '#value' => $stages_list,
  );
  return $form;
}

function drealty_fieldmap_primary_form_validate($form, &$form_state) {
  if ($form_state['triggering_element']['#value'] == 'Back') {
    return;
  }

  switch ($form_state['stage']) {
    case 'select_rets_fields':
      return drealty_fieldmap_first_form($form, $form_state);
      break;
    case 'create_rets_entity_fields':
      return drealty_fieldmap_second_form($form, $form_state);
      break;
  }
}

function drealty_fieldmap_primary_form_submit($form, &$form_state) {

  switch ($form_state['stage']) {
    case 'create_rets_entity_fields':
      $form_state['multistep_values'][$form_state['stage']] = $form_state['values'];
      if ($form_state['triggering_element']['#value'] != 'Back') {
        drealty_fieldmap_second_form_submit($form, $form_state);
        $form_state['complete'] = TRUE;
      }
      break;
    default:
      $form_state['multistep_values'][$form_state['stage']] = $form_state['values'];
      $form_state['new_stage'] = drealty_fieldmap_move_to_next_stage($form, $form_state);
      break;
  }

  if (isset($form_state['complete'])) {
    drupal_goto('admin/drealty/' . $form_state['build_info']['args'][1] . 's');
    drupal_set_message('You can manage your fields now.', 'status', true);
  }

  if ($form_state['triggering_element']['#value'] == 'Back') {
    $form_state['new_stage'] = drealty_fieldmap_move_to_previous_stage($form, $form_state);
  }

  if (isset($form_state['multistep_values']['form_build_id'])) {
    $form_state['values']['form_build_id'] = $form_state['multistep_values']['form_build_id'];
  }
  $form_state['multistep_values']['form_build_id'] = $form_state['values']['form_build_id'];
  $form_state['stage'] = $form_state['new_stage'];
  $form_state['rebuild'] = TRUE;

}


function drealty_fieldmap_first_form($form, &$form_state, $conn_name = NULL, $entity_type = NULL, $class_id = NULL) {
  $form['#validate'][] = 'drealty_fieldmap_first_form_validate';
  $form['#attached']['css'] = array(drupal_get_path('module', 'drealty_fieldmap') . '/drealty_fieldmap.css' => array(
    'group' => CSS_DEFAULT,),);

  $rets_fields_array = array();
  $dc = new drealtyConnection();
  $dm = new drealtyMetaData();

  // setup the property types for the dropdown
  $bundle_types = entity_load($entity_type . '_type');
  $type_opts = array();
  $type_opts['none'] = '-- Select --';
  foreach ($bundle_types as $type) {
    $type_opts[$type->type] = $type->label;
  }

  $connections = $dc->FetchConnections();
  foreach ($connections as $connection) {
    if (!empty($connection->nomap_mode)) {
      continue;
    }

    $query = db_select('drealty_classes', 'c');
    $query->condition('c.conid', $connection->conid)
      ->condition('c.configured', 1)
      ->condition('c.cid', $class_id)
      ->fields('c');
    if ($class = $query->execute()->fetchAll()) {
      $class = reset($class);
    }

    if ($class && isset($class->bundle) /*&& $class->bundle == $form['#instance']['bundle']*/) {
      $resource = $dm->FetchResource($class->rid);
      $fields = $dm->FetchFields($connection->conid, $resource, $class);

      $db_mapped = db_select('drealty_field_mappings', 'dfm')
        ->fields('dfm', array('systemname'))
        ->condition('conid', $connection->conid)
        ->condition('cid', $class_id)
        ->execute()
        ->fetchAllAssoc('systemname');

      foreach ($fields as $rets_field) {
        $rets_fields_array['fields'][$rets_field->systemname] = $rets_field->systemname . '   (Type: ' . $rets_field->datatype . ')';
        if($db_mapped && in_array($rets_field->systemname, array_keys($db_mapped))) {
          $rets_fields_array['classes'][] = $rets_field->systemname;
        }
      }

      $form_state['multistep_values']['rets_fields'] = array(
        'connection' => $connection,
        'class' => $class,
        'resource' => $resource,
        'class_fields' => $fields,   //or    $class->systemname => $fields,  @TODO: decide which will be more flexible
      );
      $values = isset($form_state['multistep_values']['select_rets_fields']) ? $form_state['multistep_values']['select_rets_fields'] : array();

      if (count($type_opts) > 1) {
        $form['select_rets_fields']['bundle'] = array(
          '#type' => 'select',
          '#title' => t('Entity Bundle'),
          '#description' => t('Select the entity bundle to associate this resource class with. You can add new bundles <a href="/admin/drealty/' . $entity_type . '/add">here</a>.<br>Fields with ORANGE background are already mapped at least once.'),
          '#options' => $type_opts,
          '#default_value' => isset($class->bundle) ? $class->bundle : 'none',
        );

        $form['select_rets_fields']['rets_fields'] = array(
          '#title' => 'Pick RETS fields you would like to import from this class:',
          '#default_value' => isset($values['rets_fields']) ? $values['rets_fields'] : array(),
          '#type' => 'checkboxes',
          '#theme' => 'drealty_fieldmap_rets_fields_options',
          '#options' => $rets_fields_array['fields'],
          '#attributes' => array('class' => array('field-mapped'), 'fields_list' =>$rets_fields_array['classes']),
        );

        $form['next'] = array(
          '#type' => 'submit',
          '#value' => t('Next')
        );
      }
      else {
        $form['select_rets_fields']['bundle'] = array(
          '#type' => 'item',
          '#markup' => '<h3> First, you need to have a bundle created to map the fields to.</h3><p>You can add new bundles <a href="/admin/drealty/' . $entity_type . '/add">here</a>.</p>',
        );
        $form_state['bundle'] = $entity_type;
      }

    }
  }

  return $form;
}

function drealty_fieldmap_first_form_validate($form, &$form_state) {
  $values = $form_state['values'];
  if(!empty($values) && $values['bundle'] == 'none') {
    form_set_error('bundle', t('You need to select a bundle to map the fields to.'));
  }
  else if (!empty($values['rets_fields']) && count($values['rets_fields']) > 0 && count(array_filter($values['rets_fields'])) == 0) {
    form_set_error('rets_fields', t('You need to select at least one field for mapping.'));
  }
}

function drealty_fieldmap_second_form($form, &$form_state, $conn_name = NULL, $entity_type = NULL, $class_id = NULL) {
 $form['#attributes']['class'] = array('fieldmap-form');
 $form['#attached']['css'] = array(drupal_get_path('module', 'drealty_fieldmap') . '/drealty_fieldmap.css' => array(
      'group' => CSS_DEFAULT,
    ),
  );
  $values = isset($form_state['multistep_values']['create_rets_entity_fields']) ? $form_state['multistep_values']['create_rets_entity_fields'] : array();
  $selected_fields = isset($form_state['multistep_values']['select_rets_fields']) ? $form_state['multistep_values']['select_rets_fields']['rets_fields'] : array();
  $bundle = isset($form_state['multistep_values']['select_rets_fields']) ? $form_state['multistep_values']['select_rets_fields']['bundle'] : '';

  $taxonomy_voc_options = array();
  $vocabulary = taxonomy_get_vocabularies();
  foreach ($vocabulary as $item) {
    $key = $item->vid;
    $value = $item->name;
    $taxonomy_voc_options[$key] = $value;
  }

  $form['#validate'][] = 'drealty_fieldmap_second_form_validate';

  $form['create_rets_entity_fields'] = array(
    '#tree' => TRUE,
  );

  if (count(array_filter($selected_fields)) > 0) {
    $i = 0;
    $field_type_options = field_ui_field_type_options();
    $widget_type_options = field_ui_widget_type_options(NULL, TRUE);

    $table = array(
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => array(
        'class' => array('field-ui-overview'),
        'id' => 'field-overview',
      ),
    );

    if ($field_type_options && $widget_type_options) {
      $name = '_add_new_field';
      $table[$name] = array(
        '#attributes' => array('class' => array('fieldmap-wrapper', 'add-new')),
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        'label' => array(
          '#type' => 'textfield',
          '#size' => 25,
          '#description' => t('Label'),
        ),
        'field_name' => array(
          '#type' => 'machine_name',
          '#title' => t('New field name'),
          '#title_display' => 'invisible',
          '#size' => 25,
          '#description' => t('A unique machine-readable name containing letters, numbers, and underscores.'),
          '#prefix' => '<span class="field-prefix">field_</span>',
          // 32 characters minus the 'field_' prefix:
          '#maxlength' => 26,
          '#machine_name' => array(
            'source' => array('fields', $name, 'label'),
            'exists' => '_field_ui_field_name_exists',
            'standalone' => TRUE,
            'label' => '',
          ),
          '#required' => FALSE,
        ),
        'type' => array(
          '#type' => 'select',
          '#options' => $field_type_options,
          '#empty_option' => t('- Select a field type -'),
          '#description' => t('Type of data to store.'),
          '#attributes' => array('class' => array('field-type-select')),
          '#default_value' => 'text',
        ),
        'widget_type' => array(
          '#type' => 'select',
          '#options' => $widget_type_options,
          '#empty_option' => t('- Select a widget -'),
          '#description' => t('Form element to edit the data.'),
          '#attributes' => array('class' => array('widget-type-select')),
          '#suffix' => '</div>',
          '#default_value' => 'text_textfield',
        ),
        'taxonomy_voc' => array(
          '#type' => 'select',
          '#options' => $taxonomy_voc_options,
          '#empty_option' => t('- Select a vocabulary -'),
          '#description' => t('Taxonomy vocabulary associated with field.'),
          '#attributes' => array('class' => array('field-taxonomy-select')),
        ),
        'translatable' => array(
          '#type' => 'value',
          '#value' => FALSE,
        ),
      );
    }

    // Additional row: add existing field.
    $existing_fields = field_ui_existing_field_options($entity_type, $bundle); //
    if ($existing_fields && $widget_type_options) {
      // Build list of options.
      $existing_field_options = array();
      foreach ($existing_fields as $field_name => $info) {
        $text = t('@type: @field (@label)', array(
          '@type' => $info['type_label'],
          '@label' => $info['label'],
          '@field' => $info['field'],
        ));
        $existing_field_options[$field_name] = truncate_utf8($text, 80, FALSE, TRUE);
      }
      asort($existing_field_options);
      $name = '_add_existing_field';
      $table[$name] = array(
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#attributes' => array(
          'class' => array('fieldmap-wrapper', 'add-existing'),
        ),
        'label' => array(
          '#type' => 'textfield',
          '#title' => t('Existing field label'),
          '#title_display' => 'invisible',
          '#size' => 25,
          '#description' => t('Label'),
          '#attributes' => array('class' => array('label-textfield')),
        ),
        'field_name' => array(
          '#type' => 'select',
          '#title' => t('Existing field to share'),
          '#title_display' => 'invisible',
          '#options' => $existing_field_options,
          '#empty_option' => t('- Select an existing field -'),
          '#description' => t('Field to share'),
          '#attributes' => array('class' => array('field-select')),
          '#cell_attributes' => array('colspan' => 2),
        ),
        'widget_type' => array(
          '#type' => 'select',
          '#title' => t('Widget for existing field'),
          '#title_display' => 'invisible',
          '#options' => $widget_type_options,
          '#empty_option' => t('- Select a widget -'),
          '#description' => t('Form element to edit the data.'),
          '#attributes' => array('class' => array('widget-type-select')),
          '#cell_attributes' => array('colspan' => 3),
          '#suffix' => '</div>',
        ),
      );
    }

    foreach (array_filter($selected_fields) as $field) {
      $form['create_rets_entity_fields'][$field]['fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => $field,
        '#description' => t('Data Type: ' . $form_state['multistep_values']['rets_fields']['class_fields'][$field]->datatype . ', Max Length: ' . $form_state['multistep_values']['rets_fields']['class_fields'][$field]->max_length),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
      );

      $form['create_rets_entity_fields'][$field]['fieldset']['create_new_field'] = array(
        '#type' => 'radios',
        '#title' => t('How do you want to handle this field mapping?'),
        '#options' => array(0 => 'Create a new field', 1 => 'Use existing field'),
        '#description' => t(''),
        '#default_value' => 0,
      );

      $form['create_rets_entity_fields'][$field]['fieldset']['add_field'] = $table;
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_new_field']['label']['#default_value'] = $field;
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_existing_field']['label']['#default_value'] = $field;
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_new_field']['field_name']['#default_value'] = strtolower($field);
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_new_field']['widget_type']['#prefix'] = '<div class="widget-select-wrapper" id="widget_wrap_' . $i . '">';
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_existing_field']['widget_type']['#prefix'] = '<div class="widget-select-wrapper" id="existing_widget_wrap_' . $i . '">';
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_new_field']['type']['#ajax'] = array(
        'callback' => '_drealty_fieldmap_ajax_widget_field_callback',
        'wrapper' => 'widget_wrap_' . $i,
        'parameters' => array('field' => $field),
        'progress' => 'none',
      );
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_existing_field']['field_name']['#ajax'] = array(
        'callback' => '_drealty_fieldmap_ajax_widget_field_callback',
        'wrapper' => 'existing_widget_wrap_' . $i,
        'parameters' => array('field' => $field),
        'progress' => 'none',
      );

      /*      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_new_field']['#states'] = array(
              'visible' => array(':input[name="create_rets_entity_fields[' . $field . '][fieldset][create_new_field]"]' => array('checked' => true))
            );*/
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_new_field']['#states'] = array(
        'visible' => array(':input[name="create_rets_entity_fields[' . $field . '][fieldset][create_new_field]"]' => array('value' => '0'))
      );
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_existing_field']['#states'] = array(
        'visible' => array(':input[name="create_rets_entity_fields[' . $field . '][fieldset][create_new_field]"]' => array('value' => '1'))
      );

      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_new_field']['taxonomy_voc']['#states'] = array(
        'visible' => array(':input[name="create_rets_entity_fields[' . $field . '][fieldset][add_field][_add_new_field][type]"]' => array('value' => 'taxonomy_term_reference'))
      );

      $i++;
    }
  }
  else {
    $form['create_rets_entity_fields']['empty_selection'] = array(
      '#type' => 'item',
      '#markup' => '<h3>You have no RETS fields selected.</h3>',
      '#weight' => -20
    );

  }

  $form['back'] = array(
    '#type' => 'submit',
    '#value' => t('Back')
  );

  $form['next'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );
  return $form;
}

function drealty_fieldmap_second_form_validate($form, &$form_state) {
  $values = $form_state['values']['create_rets_entity_fields'];

  if (!empty($values) && count($values) > 0) {
    foreach ($values as $f_label => $data) {
      //  Validate the machine names, length, type, etc.
      switch ($data['fieldset']['create_new_field']) {
        case '0':
          $field = $data['fieldset']['add_field']['_add_new_field'];
          $field_label = '_add_new_field';
          $subfields = array(
            $field['label'],
            $field['field_name'],
            $field['type'],
            $field['widget_type'],
            $field['taxonomy_voc']
          );
          if (array_filter($subfields)) {
            // Missing field type.
            if (!$field['type']) {
              form_set_error('create_rets_entity_fields][' . $f_label . '][fieldset][add_field][' . $field_label . '][type', t('Add new ' . $f_label . ' field: you need to select a field type.'));
            }

            // Field name validation.
            if ($field['field_name']) {
              $field_name = $field['field_name'];

              // Add the 'field_' prefix.
              $field_name = 'field_' . $field_name;
              form_set_value($form['create_rets_entity_fields'][$f_label]['fieldset']['add_field'][$field_label]['field_name'], $field_name, $form_state);
            }

            // Missing taxonomy vocabulary.
            if ($field['type'] && $field['type'] == 'taxonomy_term_reference' && !$field['taxonomy_voc']) {
              form_set_error('create_rets_entity_fields][' . $f_label . '][fieldset][add_field][' . $field_label . '][taxonomy_voc', t('Add new ' . $f_label . ' field: you need to select a taxonomy vocabulary.'));
            }
          }
          break;

        case '1':
          $field_label = '_add_existing_field';
          $field = $data['fieldset']['add_field']['_add_existing_field'];
          $subfields = array($field['label'], $field['field_name'], $field['widget_type']);
          break;
      }
      // Validate if any information was provided in the 'add new field' row.
      if (array_filter($subfields)) {
        // Missing label.
        if (!$field['label']) {
          form_set_error('create_rets_entity_fields][' . $f_label . '][fieldset][add_field][' . $field_label . '][label', t('Add new ' . $f_label . ' field: you need to provide a label.'));
        }

        // Missing field name.
        if (!$field['field_name']) {
          form_set_error('create_rets_entity_fields][' . $f_label . '][fieldset][add_field][' . $field_label . '][field_name', t('Add new ' . $f_label . ' field: you need to provide a field name.'));
        }

        // Missing widget type.
        if (!$field['widget_type']) {
          form_set_error('create_rets_entity_fields][' . $f_label . '][fieldset][add_field][' . $field_label . '][widget_type', t('Add new ' . $f_label . ' field: you need to select a widget.'));
        }
        // Wrong widget type.
        elseif ($field['type']) {
          $widget_types = field_ui_widget_type_options($field['type']);
          if (!isset($widget_types[$field['widget_type']])) {
            form_set_error('create_rets_entity_fields][' . $f_label . '][fieldset][add_field][' . $field_label . '][widget_type', t('Add new ' . $f_label . ' field: invalid widget.'));
          }
        }
      }
    }
  }
}

function drealty_fieldmap_second_form_submit($form, &$form_state) {
//  Create fields/instances, assign mapping

  $rets_conn = $form_state['multistep_values']['rets_fields'];
  $values = isset($form_state['multistep_values']['create_rets_entity_fields']) ? $form_state['multistep_values']['create_rets_entity_fields'] : array();

  try {
    // Create field mapping
    $dc = new drealtyConnection();
    $connection = $dc->FetchConnection($rets_conn['connection']->conid);

    foreach ($values['create_rets_entity_fields'] as $sysname => $new) {

      // Need this to see if the RETS field is multi-valued:
      $query = db_select('drealty_fields', 'f');
      $query->condition('f.conid', $rets_conn['connection']->conid)
        ->condition('f.rid', $rets_conn['resource']->rid)
        ->condition('f.class', $rets_conn['class']->systemname)
        ->condition('f.systemname', $sysname)
        ->fields('f', array('interpretation'));
      $multi_value = $query->execute()->fetchField();

      if ($new['fieldset']['create_new_field'] == 0) {
        $data = $new['fieldset']['add_field']['_add_new_field'];

        // Create the field.
        $field = array(
          'field_name' => $data['field_name'],
          'type' => $data['type'],
          'settings' => array( /*'max_length' => 64,*/),
          'entity_types' => array('drealty_listing', 'drealty_office', 'drealty_agent', 'drealty_openhouse'),
        );
        if ($multi_value == 'LookupMulti') {
          $field['cardinality'] = -1;
        }
        // @TODO:   don't know if it can be accomplished
        /*  if(in_array($data['type'], array('list_text', 'list_integer', 'list_boolean'))) {
            $list_options = _drealty_fieldmap_get_rets_metadata_options($dc, $rets_conn['connection']->conid, $rets_conn['resource']->systemname, $rets_conn['class']->systemname);
            $field['settings'] = array(
              'allowed_values' => $list_options,
            );
          }*/
        if ($data['type'] == 'taxonomy_term_reference') {
          $vocabulary = taxonomy_vocabulary_load($data['taxonomy_voc']);
          $field['settings'] = array(
            'allowed_values' => array(
              array(
                'vocabulary' => $vocabulary->machine_name,
                'parent' => 0,
              ),
            ),
          );
        }
        if($data['type'] == 'list_boolean') {
          $field['settings'] = array(
            'allowed_values' => array(
                0 => 'No',
                1 => 'Yes',
            ),
          );
        }
        if(field_info_field($data['field_name']) == NULL)
          field_create_field($field);
        else
          drupal_set_message(t('The field ' . $data['field_name'] . ' creation was skipped, because machine name is not unique'), 'status', TRUE);
      }
      if ($new['fieldset']['create_new_field'] == 1) {
        $data = $new['fieldset']['add_field']['_add_existing_field'];
        $orig_field = field_info_field($data['field_name']);
        /*
        // @TODO:  if the field doesn't have ['entity_types'] value matching DREALTY pre-set values, it won't display "Field_mapping table" on the field edit form but will still be mapped
        */
        $data['type'] = $orig_field['type'];
      }

      // Create the instance.
      $instance = array(
        'field_name' => $data['field_name'],
        'entity_type' => $form_state['build_info']['args'][1],
        'bundle' => $form_state['multistep_values']['select_rets_fields']['bundle'],
        'label' => $data['label'],
        'description' => '',
        'required' => FALSE,
      );
      if($data['widget_type'] == 'options_onoff') {
        $instance['widget'] = array(
          'type' => 'options_onoff',
          'settings' => array('display_label' => 1),
        );
      }
      field_create_instance($instance);

      $connection->CreateFieldMapMapping($rets_conn['resource']->rid, $data['field_name'], $sysname, $rets_conn['class']->cid, $data['type'], NULL, 0);

    }
  } catch (Exception $e) {
    watchdog("drealty", $e->getMessage(), array(), WATCHDOG_ERROR);
    drupal_set_message($e->getMessage(), 'error', TRUE);
    return FALSE;
  }
}

// Form navigation between the process steps:
function drealty_fieldmap_move_to_next_stage($form, &$form_state) {
  switch ($form_state['stage']) {
    case 'select_rets_fields':
      return 'create_rets_entity_fields';
      break;
  }
}

function drealty_fieldmap_move_to_previous_stage($form, &$form_state) {
  switch ($form_state['stage']) {
    case 'create_rets_entity_fields':
      return 'select_rets_fields';
      break;
  }
}

/**
 * Theme callback, render the RETS fields checkboxes as a table grid, instead of stacked (default).
 * @param $variables
 * @return string
 */
function theme_drealty_fieldmap_rets_fields_options(&$variables) {
  $elements = element_children($variables['form']);
  $mapped = FALSE;
  $index = 0;
  $list = array();
  for ($i = 0;$i < count($variables['form']['#options']);$i++) {
    unset($variables['form'][$elements[$i]]['#attributes']['fields_list']);
    unset($variables['form'][$elements[$i]]['#attributes']['class']);

    if(in_array($elements[$i], array_values($variables['form']['#attributes']['fields_list']))) {
      $class = 'field-mapped';
    } else $class = '';

    if ($i % 4 != 0) {
      $list[$index][] = array('data' => drupal_render($variables['form'][$elements[$i]]), 'class' => $class);
    }
    else {
      $index++;
      $list[$index][] = array('data' => drupal_render($variables['form'][$elements[$i]]), 'class' => $class);
    }
  }
  foreach ($list as $key => $options) {
    $rows[$key] = array('data' => $options);
  }
  $output = theme('table', array('rows' => $rows));
  return $output;
}

function _drealty_fieldmap_ajax_widget_field_callback($form, $form_state) {
  $field = $form_state['triggering_element']['#ajax']['parameters']['field'];
  if (in_array('_add_existing_field', array_values($form_state['triggering_element']['#array_parents']))) {
    $field_type = field_info_field($form_state['triggering_element']['#value']);
    if ($field_type) {
      $field_type = $field_type['type'];
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_existing_field']['widget_type']['#options'] = field_ui_widget_type_options($field_type, FALSE);
    }
    return $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_existing_field']['widget_type'];
  }
  else {
    if (in_array('_add_new_field', array_values($form_state['triggering_element']['#array_parents']))) {
      $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_new_field']['widget_type']['#options'] = field_ui_widget_type_options($form_state['triggering_element']['#value'], FALSE);
      return $form['create_rets_entity_fields'][$field]['fieldset']['add_field']['_add_new_field']['widget_type'];
    }
  }
}

/*function _drealty_fieldmap_get_rets_metadata_options($dc, $conid, $resource, $class) {
  $values = array();
  if ($dc->connect($conid))
    $metadata = $dc->rets->GetMetadataTable($resource, $class);
  return $values;
}*/

