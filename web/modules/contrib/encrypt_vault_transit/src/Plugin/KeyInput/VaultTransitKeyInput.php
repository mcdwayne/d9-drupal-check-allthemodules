<?php

namespace Drupal\encrypt_vault_transit\Plugin\KeyInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyInput\TextFieldKeyInput;

/**
 * Defines a key input that provides a simple text field.
 *
 * @KeyInput(
 *   id = "vault_transit_key",
 *   label = @Translation("Vault Transit Key"),
 *   description = @Translation("The transit encryption key you wish to use.")
 * )
 */
class VaultTransitKeyInput extends TextFieldKeyInput {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['key_value']['#title'] = $this->t('Vault Transit Key');
    $form['key_value']['#description'] = $this->t('The name of the Vault transit key you wish to configure.');

    return $form;
  }

}
