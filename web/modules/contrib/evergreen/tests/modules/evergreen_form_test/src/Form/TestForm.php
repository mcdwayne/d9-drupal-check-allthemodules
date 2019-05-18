<?php

namespace Drupal\evergreen_form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class TestForm extends FormBase {

  public function getFormId() {
    return 'test_form_id';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
