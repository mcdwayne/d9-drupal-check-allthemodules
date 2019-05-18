<?php

namespace Drupal\hipa\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * HiPa settings class.
 */
class HipaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hipa_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hipa.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hipa.settings');
    $form['hipa_salt'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Hide Path - hash salt'),
      '#description' => $this->t('Please enter a salt hash to encrypt the image URLs.'),
      '#default_value' => $config->get('hipa.hipa_salt'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('hipa.settings');
    $config->set('hipa.hipa_salt', $values['hipa_salt'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
