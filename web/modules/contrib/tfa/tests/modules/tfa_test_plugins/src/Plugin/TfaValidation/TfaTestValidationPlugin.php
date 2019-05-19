<?php

namespace Drupal\tfa_test_plugins\Plugin\TfaValidation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaValidationInterface;

/**
 * Class TfaTestValidationPlugin.
 *
 * @package Drupal\tfa_test_plugins
 *
 * @TfaValidation(
 *   id = "tfa_test_plugins_validation",
 *   label = @Translation("TFA Test Validation Plugin"),
 *   description = @Translation("TFA Test Validation Plugin"),
 *   fallbacks = { },
 *   isFallback = FALSE
 * )
 */
class TfaTestValidationPlugin extends TfaBasePlugin implements TfaValidationInterface {

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbacks() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isFallback() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return TRUE;
  }

}
