<?php

namespace Drupal\yandex_oauth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides module configuration form class.
 */
class AppSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yandex_oauth_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yandex_oauth.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options['query'] = ['hauth.done' => 'Yandex'];
    $callback_url = Url::fromRoute('yandex_oauth.endpoint', [], $options)->setAbsolute();

    $form['callback_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback URL'),
      '#description' => $this->t('Copy this URL and paste it to the <em>Callback URL</em> field in the client application settings form at <a href="https://oauth.yandex.com" target="_blank">https://oauth.yandex.com</a>.'),
      '#default_value' => $callback_url->toString(),
      '#disabled' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
      '#description' => $this->t('Application ID.'),
      '#default_value' => $this->config('yandex_oauth.settings')->get('id'),
      '#required' => TRUE,
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Application password.'),
      '#default_value' => $this->config('yandex_oauth.settings')->get('secret'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('yandex_oauth.settings')
      ->set('id', $form_state->getValue('id'))
      ->set('secret', $form_state->getValue('secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
