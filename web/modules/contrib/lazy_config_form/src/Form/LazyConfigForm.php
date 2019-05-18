<?php

namespace Drupal\lazy_config_form\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LazyConfigForm.
 */
abstract class LazyConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Cleaning the values.
    $form_state->cleanValues();
    // Getting the values.
    $values = $form_state->getValues();
    // Getting the config.
    $config = $this->config($this->getEditableConfigNames()[0]);

    // Setting the values.
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }

    // Saving the values.
    $config->save();
  }

}
