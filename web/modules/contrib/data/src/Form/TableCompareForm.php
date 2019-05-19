<?php

namespace Drupal\data\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TableCompareForm.
 *
 * @package Drupal\data\Form
 */
class TableCompareForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'data_table_compare';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
