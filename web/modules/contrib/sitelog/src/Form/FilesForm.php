<?php

namespace Drupal\sitelog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller.
 */
class FilesForm extends FormBase {

  /**
   * Form builder.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['files'] = array(
      '#type' => 'radios',
      '#title' => t('Files'),
      '#options' => array(
        'uploaded' => 'Uploaded',
        'storage' => 'Storage',
      ),
      '#default_value' => 'uploaded',
    );
    return $form;
  }

  /**
   * Form identifier getter method.
   */
  public function getFormId() {}

  /**
   * Submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
