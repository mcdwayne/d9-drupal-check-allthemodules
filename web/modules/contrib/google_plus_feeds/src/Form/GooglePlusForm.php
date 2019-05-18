<?php
/**
 * @file
 * Contains Drupal\google_plus_feeds\Form\GooglePlusForm.
 */
namespace Drupal\google_plus_feeds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class GooglePlusForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_plus_feeds.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'Google plus account setting';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_plus_feeds.adminsettings');

    $form['google_plus_account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google plus account ID'),
      '#description' => $this->t('Google Account ID to fetch your post.'),
      '#default_value' => $config->get('google_plus_account_id'),
      '#required' => TRUE,
    ];

    $form['google_plus_account_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google API Key'),
      '#description' => $this->t('Google Plus API key from developer account from above URL.'),
      '#default_value' => $config->get('google_plus_account_api_key'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('google_plus_feeds.adminsettings')
      ->set('google_plus_account_id', $form_state->getValue('google_plus_account_id'))
      ->set('google_plus_account_api_key', $form_state->getValue('google_plus_account_api_key'))
      ->save();
  }

}
