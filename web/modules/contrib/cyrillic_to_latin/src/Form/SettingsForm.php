<?php

namespace Drupal\cyrillic_to_latin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures Cyrillic to Latin settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cyrillic_to_latin_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cyrillic_to_latin.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cyrillic_to_latin_config = $this->config('cyrillic_to_latin.settings');

    $form['enabled'] = [
      '#type' => 'select',
      '#title' => $this->t('Enabled'),
      '#default_value' => $cyrillic_to_latin_config->get('enabled'),
      '#description' => $this->t('Enable or disable the module. You must clear the cache for the change to take effect.'),
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('cyrillic_to_latin.settings')
      ->set('enabled', $values['enabled'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
