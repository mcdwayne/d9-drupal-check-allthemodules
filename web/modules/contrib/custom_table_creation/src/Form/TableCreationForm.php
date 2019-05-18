<?php

/**
 * @file
 * Contains \Drupal\custom_table_creation\Form\TableCreationForm.
 */

namespace Drupal\custom_table_creation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

class TableCreationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public $size = array();
  public $type = array();

  //Define Construct
  public function __construct() {
    
  }

  public function getFormId() {
    return 'table_creation_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $type = array('serial' => 'Serial', 'int' => 'Integer', 'float' => 'Float', 'varchar' => 'Varchar', 'text' => 'Text');
    $size = array(
      'tiny' => 'Tiny',
      'normal' => 'Normal',
      'small' => 'Small',
      'medium' => 'Medium',
      'big' => 'Big',
    );

    $total_column = $form_state->getValue('column');
    $table_name = $form_state->getValue('table_name');
    $table_title = $form_state->getValue('table_title');
    if (!isset($total_column)) {
      $form['table_name'] = array(
        '#type' => 'textfield',
        '#title' => 'Table Name',
        '#required' => 1
      );
      $form['table_title'] = array(
        '#type' => 'textfield',
        '#title' => 'Table Title',
        '#required' => 1
      );
      $form['column'] = array(
        '#type' => 'textfield',
        '#title' => 'No. of Column',
        '#required' => 1
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Next',
      );
    }
    else {
      $form['cus_table'] = array(
        '#type' => 'table',
        '#caption' => $this->t('Sample Table'),
        '#header' => array($this->t('Column Name'),
          $this->t('Label'),
          $this->t('Type'),
          $this->t('Size'),
          $this->t('Unsigned'),
          $this->t('Index'),
          $this->t('Unique')
        ),
        '#attributes' => array('style' => array('border:solid 1px',))
      );
      $form['table_name_hid'] = array(
        '#type' => 'hidden',
        '#title' => 'Table Name',
        '#value' => $table_name,
        '#required' => 1
      );
      $form['table_title_hid'] = array(
        '#type' => 'hidden',
        '#title' => 'Table Title',
        '#value' => $table_title,
        '#required' => 1
      );
      $form['column_hid'] = array(
        '#type' => 'hidden',
        '#title' => 'No. of Column',
        '#value' => $total_column,
        '#required' => 1
      );
      for ($i = 0; $i < $total_column; $i++) {
        $form['cus_table'][$i]['column_name_' . $i] = array(
          '#type' => 'textfield',
          '#attributes' => array('style' => array('width:250px;')),
        );
        $form['cus_table'][$i]['column_label_' . $i] = array(
          '#type' => 'textfield',
          '#attributes' => array('style' => array('width:250px;')),
        );
        $form['cus_table'][$i]['column_type_' . $i] = array(
          '#type' => 'select',
          '#options' => $type,
        );
        $form['cus_table'][$i]['column_size_' . $i] = array(
          '#type' => 'select',
          '#options' => $size,
        );
        $form['cus_table'][$i]['column_unsigned_' . $i] = array(
          '#type' => 'checkbox',
          '#attributes' => array('style' => array('width:70px;')),
        );
        $form['cus_table'][$i]['column_index_' . $i] = array(
          '#type' => 'checkbox',
          '#attributes' => array('style' => array('width:70px;')),
        );
        $form['cus_table'][$i]['column_unique_' . $i] = array(
          '#type' => 'checkbox',
          '#attributes' => array('style' => array('width:70px;')),
        );
      }

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Create',
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button_sub = $form_state->getValue('op');
    if ($button_sub == 'Next') {
      $form_state->setRebuild();
    }
    else if ($button_sub == 'Create') {
      $form_values = $form_state->getValues();
      $table_name = $form_state->getValue('table_name_hid');
      $table_title = $form_state->getValue('table_title_hid');

      $values = $form_values['cus_table'];      
      foreach ($values as $i => $val) {
        $column_name = $val['column_name_' . $i];
        $field_desc = isset($val['column_label_' . $i]) ? $val['column_label_' . $i] : '';
        $field_type = $val['column_type_' . $i];
        $field_size = $val['column_size_' . $i];
        $field_unsigned = isset($val['column_unsigned_' . $i]) ? TRUE : FALSE;
        $field_unique = isset($val['column_unique_' . $i]) ? $column_name : NULL;
        $fields[$column_name] = array(
          'description' => $field_desc,
          'type' => $field_type,
          'size' => $field_size,
          'not null' => TRUE,
          'unsigned' => $field_unsigned,          
        );

        if($field_type == 'varchar') {
          unset($fields[$column_name]['size']);
          unset($fields[$column_name]['unsigned']);
          $fields[$column_name]['length'] = 128;
        }
        if ($field_unique != NULL) {
          $primary_key[] = $field_unique;
        }
      }

      $schema = array();
      $schema[$table_name]['description'] = $table_name;
      $schema[$table_name]['fields'] = $fields;
      if (!empty($primary_key)) {
        $schema[$table_name]['primary key'] = $primary_key;
      }
      
      $connection = \Drupal\Core\Database\Database::getConnection();
      foreach ($schema as $name => $table) {
        $connection->schema()->createTable($name, $table);
      }

      //Insert Table Info 
      $time = time();
      $table_fields = array(
        'label' => $table_title,
        'table_name' => $table_name,
        'created' => $time,
        'changed' => $time,
      );
      db_insert('cus_table_list')->fields($table_fields)->execute();
    }
  }

  public function create_cus_tab() {
    $schema['abc_cc'] = array(
      'description' => 'cus table str',
      'fields' => array(
        'f_count' => array(
          'description' => 'F count',
          'type' => 'serial',
          'size' => 'medium',
          'not null' => TRUE,
          'unsigned' => TRUE,
        ),
      ),
      'primary key' => array('f_count'),
    );
    return $schema;
  }

}
