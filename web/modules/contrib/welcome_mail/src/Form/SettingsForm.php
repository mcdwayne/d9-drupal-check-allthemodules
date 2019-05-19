<?php

namespace Drupal\welcome_mail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Welcome mail settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'welcome_mail_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['welcome_mail.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('welcome_mail.settings');
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Welcome mail'),
      '#default_value' => $config->get('enable'),
    ];
    $form['time'] = [
      '#type' => 'number',
      '#title' => $this->t('Time to wait for sending out welcome mail, in hours'),
      '#default_value' => $config->get('time'),
      '#states' => [
        'visible' => [
          ':input[name="enable"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('subject'),
      '#states' => [
        'visible' => [
          ':input[name="enable"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $mail_config = $config->get('body');
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Mail to send'),
      '#format' => !empty($mail_config) ? $mail_config['format'] : 'plain_text',
      '#default_value' => !empty($mail_config) ? $mail_config['value'] : '',
      '#states' => [
        'visible' => [
          ':input[name="enable"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('welcome_mail.settings')
      ->set('enable', $form_state->getValue('enable'))
      ->set('time', $form_state->getValue('time'))
      ->set('subject', $form_state->getValue('subject'))
      ->set('body', $form_state->getValue('body'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
