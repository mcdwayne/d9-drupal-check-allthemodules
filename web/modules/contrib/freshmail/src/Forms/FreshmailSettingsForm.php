<?php

namespace Drupal\freshmail\Forms;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FreshmailSettingsForm.
 *
 * Provides the add form to add settings freshmail.
 *
 * @ingroup freshmail
 */
class FreshmailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'freshmail_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'freshmail.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('freshmail.settings');

    $form['freshmail_list_id'] = array(
      '#type' => 'textfield',
      '#group' => 'config',
      '#title' => $this->t('List id'),
      '#default_value' => $config->get('freshmail_list_id'),
    );

    $form['freshmail_api_key'] = array(
      '#type' => 'textfield',
      '#group' => 'config',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('freshmail_api_key'),
    );

    $form['freshmail_api_secret_key'] = array(
      '#type' => 'textfield',
      '#group' => 'config',
      '#title' => $this->t('Secret API Key'),
      '#default_value' => $config->get('freshmail_api_secret_key'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('freshmail.settings');
    $config->set('freshmail_api_key', $form_state->getValue('freshmail_api_key'))->save();
    $config->set('freshmail_api_secret_key', $form_state->getValue('freshmail_api_secret_key'))->save();
    $config->set('freshmail_list_id', $form_state->getValue('freshmail_list_id'))->save();

    parent::submitForm($form, $form_state);
  }

}
