<?php

namespace Drupal\graphql_string_translation\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\locale\Form\TranslateEditForm;

class TranslateForm extends TranslateEditForm {

  /**
   * {@inheritdoc}
   */
  protected function translateFilterValues($reset = FALSE) {
    $filter_values = parent::translateFilterValues($reset);
    $filter_values['context'] = 'graphql';
    return static::$filterValues = $filter_values;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('graphql_string_translation.translate_page');
  }

}
