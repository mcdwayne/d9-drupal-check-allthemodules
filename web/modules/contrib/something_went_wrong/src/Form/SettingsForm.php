<?php

namespace Drupal\something_went_wrong\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'something_went_wrong.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'something_went_wrong_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('something_went_wrong.settings');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Enter your site name or similar site identifier.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('label'),
      '#required' => TRUE,
    ];

    $form['slack'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Slack notification'),
      '#description' => $this->t('Send a message when exception occurs.'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $config->get('slack'),
    ];

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook URL'),
      '#description' => $this->t('Go to your <a target="_blank" href="https://slack.com/services/new/incoming-webhook">Slack settings</a> and add a Webhook integration. After you create the integration you will get a url, which you need to enter in this field.'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $config->get('webhook_url'),
      '#states' => [
        'invisible' => [
          ':input[name="slack"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mail notification'),
      '#description' => $this->t('Send mail when exception occurs.'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $config->get('mail'),
    ];

    $form['mail_custom_address'] = [
      '#type' => 'email',
      '#title' => $this->t('Custom mail address'),
      '#description' => $this->t('You can define a custom mail address. Leave the field empty if you want to send the mails to the site mail.'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $config->get('mail_custom_address'),
      '#states' => [
        'invisible' => [
          ':input[name="mail"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['ignore_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ignore list'),
      '#description' => $this->t('Comma separated list of exception classes to ignore. For example: NotFoundHttpException'),
      '#default_value' => $config->get('ignore_list'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('slack') && !$form_state->getValue('webhook_url')) {
      $form_state->setErrorByName('webhook_url', 'This field is required if you want to receive Slack notifications.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('something_went_wrong.settings')
      ->set('label', $form_state->getValue('label'))
      ->set('slack', $form_state->getValue('slack'))
      ->set('webhook_url', $form_state->getValue('webhook_url'))
      ->set('mail', $form_state->getValue('mail'))
      ->set('mail_custom_address', $form_state->getValue('mail_custom_address'))
      ->set('ignore_list', $form_state->getValue('ignore_list'))
      ->save();
  }

}
