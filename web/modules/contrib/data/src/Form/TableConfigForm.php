<?php

namespace Drupal\data\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TableConfigForm.
 *
 * @package Drupal\data\Form
 */
class TableConfigForm extends EntityForm {

  /** @var int $step 0 - name and number of fields 1 - fields edit. */
  protected $step;

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $data_table_config = $this->entity;

    if ($data_table_config->isNew()) {
      $number_of_fields = $form_state->getValue('field_num');
      $this->step = $number_of_fields ? 1 : 0;
    }
    // No need to ask number of columns for existing field.
    else {
      $number_of_fields = count($data_table_config->table_schema);
      $this->step = 1;
    }

    // Multistep form.
    if (!$this->step) {
      // First form, ask for the database table name.
      $form['title'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Table title'),
        '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
        '#default_value' => $data_table_config->label(),
        '#description' => $this->t('Table title.'),
        '#required' => TRUE,
      );

      $form['id'] = array(
        '#type' => 'machine_name',
        '#default_value' => $data_table_config->id(),
        '#machine_name' => array(
          'exists' => '\Drupal\data\Entity\TableConfig::load',
          'source' => array('title'),
        ),
        '#description' => $this->t('Machine readable name of the table - e. g. "my_table". Must only contain lower case letters and _.'),
        '#disabled' => !$data_table_config->isNew(),
      );

      $form['field_num'] = array(
        '#type' => 'textfield',
        '#title' => t('Number of fields'),
        '#description' => t('The number of fields this table should contain.'),
        '#default_value' => 1,
        '#required' => TRUE,
      );
      $form['actions']['submit']['#value'] = t('Next');
    }
    else {
      // Second form, ask for the database field names.
      $form['help']['#markup'] = t('Define the fields of the new table.');
      $form['table_schema'] = array(
        '#type' => 'table',
        '#header' => array(
          t('Name'),
          t('Label'),
          t('Type'),
          t('Size'),
          t('Length'),
          t('Unsigned'),
          t('Index'),
          t('Primary key'),
        ),
      );
      for ($i = 0; $i < $number_of_fields; $i++) {
        $form['table_schema'][$i] = $this->fieldForm($i, TRUE);
      }
      $form['actions']['submit']['#value'] = $this->entity->isNew()
        ? t('Create') : t('Update');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->entity->isNew() && $this->entity->exists()) {
      $form_state->setErrorByName('id', t('Table @name already exists.',
        array('@name' => $this->entity->id())));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->step) {
      parent::submitForm($form, $form_state);
    }
    else {
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // No need to save entity at the first step.
    if (!$this->step) {
      return;
    }
    $data_table_config = $this->entity;

    try {
      $status = $data_table_config->save();
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return;
    }

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Data Table.', [
          '%label' => $data_table_config->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Data Table.', [
          '%label' => $data_table_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($data_table_config->urlInfo('collection'));
  }

  /**
   * Helper function that generates a form snippet for defining a field.
   *
   * formerly known as _data_ui_field_form().
   *
   * @param int $index
   * Index of column definition in table schema.
   * @param bool $required.
   */
  protected function fieldForm($index, $required = FALSE) {
    $defaults = array_fill_keys(array(
      'name',
      'label',
      'type',
      'size',
      'length',
      'unsigned',
      'index',
      'primary',
    ), '');
    $current = isset($this->entity->table_schema[$index])
      ? $this->entity->table_schema[$index] : $defaults;
    $form = array();
    $form['#tree'] = TRUE;
    $form['name'] = array(
      '#type' => 'textfield',
      '#size' => 20,
      '#required' => $required,
      '#default_value' => $current['name'],
    );
    $form['label'] = array(
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $current['label'],
    );
    $form['type'] = array(
      '#type' => 'select',
      '#options' => data_get_field_types(),
      '#default_value' => $current['type'],
    );
    $form['size'] = array(
      '#type' => 'select',
      '#options' => data_get_field_sizes(),
      '#default_value' => $current['size'],
    );
    $form['length'] = array(
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $current['length'],
      '#states' => array(
        'visible' => [
          ["#edit-table-schema-$index-type" => ['value' => 'varchar']],
          ["#edit-table-schema-$index-type" => ['value' => 'char']],
          ["#edit-table-schema-$index-type" => ['value' => 'text']],
        ],
      ),
    );
    $form['unsigned'] = array(
      '#type' => 'checkbox',
      '#default_value' => $current['unsigned'],
    );
    $form['index'] = array(
      '#type' => 'checkbox',
      '#default_value' => $current['index'],
    );
    $form['primary'] = array(
      '#type' => 'checkbox',
      '#default_value' => $current['primary'],
    );
    return $form;
  }

}
