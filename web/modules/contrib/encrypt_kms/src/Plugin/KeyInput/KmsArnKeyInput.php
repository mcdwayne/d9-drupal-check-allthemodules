<?php

namespace Drupal\encrypt_kms\Plugin\KeyInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyInput\TextFieldKeyInput;

/**
 * Defines a key input that provides a simple text field.
 *
 * @KeyInput(
 *   id = "aws_kms_arn",
 *   label = @Translation("KMS ARN"),
 *   description = @Translation("A simple text field with ARN-specific help text.")
 * )
 */
class KmsArnKeyInput extends TextFieldKeyInput {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['key_value']['#title'] = $this->t('KMS Key ARN');
    $form['key_value']['#description'] = $this->t('The ARN of the KMS key you wish to configure.');

    return $form;
  }

}
