<?php

namespace Drupal\playwire\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PlayWireConfigForm.
 */
class PlaywireConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'playwire_configuration.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'playwire_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Get config.
    $config = $this->config('playwire_configuration.settings');

    $form['playwire'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Admin Configuration for Playwire.'),
    ];

    // Playwire id.
    $form['playwire']['playwire_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Playwire APP ID'),
      '#description' => $this->t("Playwire APP ID"),
      '#default_value' => ($config->get('playwire_app_id')) ? $config->get('playwire_app_id') : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('playwire_configuration.settings')
      ->set('playwire_app_id', $form_state->getValue('playwire_app_id'))->save();
    drupal_set_message($this->t('Playwire setting is saved.'));
  }

}
