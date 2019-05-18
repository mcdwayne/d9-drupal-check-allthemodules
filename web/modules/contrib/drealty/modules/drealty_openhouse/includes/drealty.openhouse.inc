<?php

class DrealtyOpenHouse extends Entity {
  public $id;
  public $type;
  public $conid;
  public $hash;
  public $rets_key;
  public $rets_id;
  public $class;

  public function __construct(array $values = array()) {
    parent::__construct($values, 'drealty_openhouse');
  }

  protected function defaultUri() {
    return array('path' => 'drealty_openhouse/' . $this->id);
  }

  public function path() {
    $uri = $this->entity();
    return $uri['path'];
  }

  public function url() {
    $uri = $this->uri();
    return url($uri['path'], $uri);
  }
}

/**
 * Entity controller for Drealty OpenHouse.
 */
class DrealtyOpenHouseController extends EntityAPIController {
  public function __construct($entityType) {
    parent::__construct($entityType);
  }

  public function save($entity, DatabaseTransaction $transaction = NULL) {
    $entity->changed = REQUEST_TIME;

    if (empty($entity->{$this->idKey}) || !empty($entity->is_new)) {
      // Set the creation timestamp if not set, for new entities.
      if (empty($entity->created)) {
        $entity->created = REQUEST_TIME;
      }
    }

    return parent::save($entity, $transaction);
  }

  public function view($entities, $view_mode = '', $langcode = NULL, $page = NULL) {
    return parent::view($entities, $view_mode = '', $langcode = NULL, $page = NULL);
  }
}

class DrealtyOpenHouseType extends Entity {

  public $type;
  public $label;
  public $weight = 0;

  public function __construct(array $values = array()) {
    parent::__construct($values, 'drealty_openhouse_type');
  }

  /**
   * Returns whether the profile type is locked, thus may not be deleted or renamed.
   *
   * Profile types provided in code are automatically treated as locked, as well
   * as any fixed profile type.
   */
  public function isLocked() {
    return isset($this->status) && empty($this->is_new) && (($this->status & ENTITY_IN_CODE) || ($this->status & ENTITY_FIXED));
  }

  public function delete() {
    parent::delete();
    field_attach_delete_bundle('drealty_openhouse', $this->type);
    field_purge_batch(10);
  }

}

class DrealtyOpenHouseTypeController extends EntityAPIControllerExportable {

  public function delete($ids, DatabaseTransaction $transaction = NULL) {
    parent::delete($ids, $transaction);
    foreach ($ids as $id) {
      field_attach_delete_bundle('drealty_openhouse', $id);
      //check to see if any classes are using this bundle if so disable the class and set it's bundle to null
      db_update('drealty_classes')
        ->condition('bundle', $id)
        ->fields(array('bundle' => 'none', 'enabled' => 0))
        ->execute();
    }
  }

  /**
   *
   * @param DrealtyListingType $entity
   * @param string $prefix
   * @return string
   */
  public function export($entity, $prefix = '') {
    $vars = get_object_vars($entity);
    unset($vars[$this->statusKey], $vars[$this->moduleKey], $vars['is_new'], $vars['type'], $vars['label']);
    if ($this->nameKey != $this->idKey) {
      unset($vars[$this->idKey]);
    }
    if ($entity->type) {
      $info = $entity->entityInfo();
      $instances = field_info_instances($info['bundle of'], $entity->type);
      $fields = array();
      foreach ($instances as &$instance) {
        $field = field_info_field($instance['field_name']);
        unset($field['id'], $field['bundles'], $field['storage']);
        $fields[$instance['field_name']] = $field;
        unset($instance['bundle']);
      }

      $vars['fields'] = $fields;
      $vars['field_instances'] = $instances;
    }

    return entity_var_json_export($vars, $prefix);
  }

  public function save($entity, DatabaseTransaction $transaction = NULL) {
    parent::save($entity, $transaction);

    if (isset($entity->fields)) {
      foreach ($entity->fields as $field) {
        if (!field_read_field($field['field_name'], array('include_inactive' => TRUE))) {
          field_create_field($field);
        } else {
          drupal_set_message(t('Skipped creating field %field. A field with this name already exists.', array('%field' => $field['field_name'])), 'warning');
        }
      }

      if (isset($entity->field_instances)) {
        foreach ($entity->field_instances as $instance) {
          $instance['bundle'] = $entity->type;
          if (!field_info_instance($instance['entity_type'], $instance['field_name'], $instance['bundle'])) {
            field_create_instance($instance);
          }
        }
      }
    }
  }
}

/**
 * UI Controller
 */
class DrealtyOpenHouseTypeUIController extends EntityDefaultUIController {

  /**
   * Overrides hook_menu() defaults.
   */
  public function hook_menu() {
    $items = parent::hook_menu();
    $items[$this->path]['description'] = 'Manage drealty agents, including fields.';
    return $items;
  }

  public function operationForm($form, &$form_state, $entity, $op) {
    switch ($op) {
      case 'revert':
        $label = entity_label($this->entityType, $entity);
        $confirm_question = t('Are you sure you want to revert the %entity %label?', array('%entity' => $this->entityInfo['label'], '%label' => $label));
        return confirm_form($form, $confirm_question, $this->path);

      case 'delete':
        $label = entity_label($this->entityType, $entity);
        $confirm_question = t('Are you sure you want to delete the %entity %label?', array('%entity' => $this->entityInfo['label'], '%label' => $label));
        return confirm_form($form, $confirm_question, $this->path);

      case 'export':
        if (!empty($this->entityInfo['exportable'])) {
          $export = entity_export($this->entityType, $entity);
          $form['export'] = array(
            '#type' => 'textarea',
            '#title' => t('Export'),
            '#description' => t('For importing copy the content of the text area and paste it into the import page.'),
            '#rows' => 25,
            '#default_value' => $export,
          );
          return $form;
        }

      case 'import':
        $form['label'] = array(
          '#type' => 'textfield',
          '#title' => t('Property Type Label'),
          '#description' => t('The human-readable name of this property type.'),
          '#required' => TRUE,
          '#size' => 80,
          '#default_value' => '',
        );
        $form['type'] = array(
          '#type' => 'machine_name',
          '#title' => t('Property Type Name'),
          '#machine_name' => array(
            'exists' => 'drealty_openhouse_type_name_exists',
            'source' => array('label'),
          ),
          '#description' => t('A unique machine-readable name for this Property Type. It must only contain lowercase letters, numbers, and underscores.'),
          '#size' => 80,
          '#default_value' => '',
        );

        $form['import'] = array(
          '#type' => 'textarea',
          '#title' => t('Import'),
          '#description' => t('Paste an exported %entity_type here.', array('%entity_type' => $this->entityInfo['label'])),
          '#rows' => 20,
        );
        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Import'),
        );
        return $form;
    }
    drupal_not_found();
    exit;
  }

  /**
   * Operation form validation callback.
   */
  public function operationFormValidate($form, &$form_state) {
    if ($form_state['op'] == 'import') {

      if ($entity = entity_import($this->entityType, $form_state['values']['import'])) {
        // Store the successfully imported entity in $form_state.
        $form_state[$this->entityType] = $entity;
        $form_state[$this->entityType]->type = $form_state['values']['type'];
        $form_state[$this->entityType]->label = $form_state['values']['label'];

        // Check for existing entities with the same identifier.
        $id = entity_id($this->entityType, $entity);
        $entities = entity_load($this->entityType, array($id));
        if (!empty($entities)) {
          $label = entity_label($this->entityType, $entity);
          $vars = array('%entity' => $this->entityInfo['label'], '%label' => $label);
          form_set_error('import', t('Import of %entity %label failed, a %entity with the same machine name already exists. Check the overwrite option to replace it.', $vars));
        }
      } else {
        form_set_error('import', t('Import failed.'));
      }
    }
  }

}

/**
 * Generates the listing type editing form.
 */
function drealty_openhouse_type_form($form, &$form_state, DrealtyOpenHouseType $drealty_openhouse_type, $op = 'edit') {

  if ($op == 'clone') {
    $drealty_openhouse_type->label .= ' (cloned)';
    $drealty_openhouse_type->type = '';
  }

  $form['label'] = array(
    '#title' => t('Label'),
    '#type' => 'textfield',
    '#default_value' => $drealty_openhouse_type->label,
    '#description' => t('The human-readable name of this drealty listing type.'),
    '#required' => TRUE,
    '#size' => 30,
  );
  // Machine-readable type name.
  $form['type'] = array(
    '#type' => 'machine_name',
    '#default_value' => isset($drealty_openhouse_type->type) ? $drealty_openhouse_type->type : '',
    '#maxlength' => 32,
    '#disabled' => $drealty_openhouse_type->isLocked() && $op != 'clone',
    '#machine_name' => array(
      'exists' => 'drealty_listings_get_types',
      'source' => array('label'),
    ),
    '#description' => t('A unique machine-readable name for this drealty agent type. It must only contain lowercase letters, numbers, and underscores.'),
  );


  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save drealty agent type'),
    '#weight' => 40,
  );

  if (!$drealty_openhouse_type->isLocked() && $op != 'add' && $op != 'clone') {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete agent type'),
      '#weight' => 45,
      '#limit_validation_errors' => array(),
      '#submit' => array('drealty_openhouse_type_form_submit_delete')
    );
  }
  return $form;
}

/**
 * Form API submit callback for the type form.
 */
function drealty_openhouse_type_form_submit(&$form, &$form_state) {
  $drealty_openhouse_type = entity_ui_form_submit_build_entity($form, $form_state);
  // Save and go back.
  $drealty_openhouse_type->save();
  $form_state['redirect'] = 'admin/drealty/drealty_openhouse';
}

/**
 * Form API submit callback for the delete button.
 */
function drealty_openhouse_type_form_submit_delete(&$form, &$form_state) {
  $form_state['redirect'] = 'admin/drealty/drealty_openhouse/manage/' . $form_state['drealty_openhouse_type']->type . '/delete';
}