<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\custom_table_creation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

class TableDeletionForm extends FormBase {

  //Define Construct
  public function __construct() {
    
  }

  public function getFormId() {
    return 'table_deletion_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['drop'] = array(
      '#type' => 'submit',
      '#value' => 'Drop',
    );
    $form['cancel'] = array(
      '#type' => 'submit',
      '#value' => 'Cancel',
    );
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
    $op = $form_state->getValue('op');
    if ($op == 'Cancel') {
      $form_state->setRedirect('custom_table_creation.cus_table_list');
      return;
    }
    elseif ($op == 'Drop') {
      Database::getConnection()->schema()->dropTable('student_login_data_tab');
      $form_state->setRedirect('custom_table_creation.cus_table_list');
      return;
    }
  }

}
