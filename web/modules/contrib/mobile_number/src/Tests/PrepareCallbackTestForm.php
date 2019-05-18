<?php

namespace Drupal\mobile_number\Tests;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * A test form used for the prepareCallback() tests.
 */
class PrepareCallbackTestForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return Crypt::randomBytesBase64();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
