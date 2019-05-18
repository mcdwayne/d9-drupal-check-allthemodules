<?php

namespace Drupal\mass_contact\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form base class.
 */
abstract class SettingsFormBase extends ConfigFormBase {

  /**
   * Get config keys to save.
   *
   * @return string[]
   *   An array of config keys to save.
   */
  abstract protected function getConfigKeys();

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mass_contact.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->configFactory->getEditable('mass_contact.settings');
    foreach ($this->getConfigKeys() as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();
  }

}
