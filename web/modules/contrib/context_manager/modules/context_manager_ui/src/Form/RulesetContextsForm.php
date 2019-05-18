<?php

namespace Drupal\context_manager_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

class RulesetContextsForm extends FormBase implements FormInterface {

  public function getFormId() {
    return 'context_manager_ui_ruleset_context_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
