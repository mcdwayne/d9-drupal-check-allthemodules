<?php

namespace Drupal\data\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TableAdoptForm.
 *
 * @package Drupal\data\Form
 */
class TableAdoptForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'table_adopt_form';
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
