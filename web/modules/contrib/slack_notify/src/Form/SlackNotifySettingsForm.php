<?php

namespace Drupal\slacklognotification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Configure example settings for this site.
 */
class SlackNotifySettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slacklognotification_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'slacklognotification.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_checkbox = [];
    $config = $this->config('slacklognotification.settings');
    $levels = RfcLogLevel::getLevels();
    $severity = $config->get('severity');
    $enable_default_value = !empty($config->get('enable')) ? $config->get('enable') : 0;
    foreach ($severity as $key => $value) {
      if ($value) {
        $default_checkbox[] = $key;
      }
    }
    foreach ($levels as $key => $value) {
      $options[$key] = $value->getUntranslatedString();
    }
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Slack Notification?'),
      '#default_value' => $enable_default_value,
    ];
    $form['channel_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Channel Link'),
      '#required' => TRUE,
      '#default_value' => $config->get('channel_link'),
    ];
    $form['severity'] = [
      '#type' => 'checkboxes',
      '#title' => t('Severity'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $default_checkbox,
      '#description' => '<p>' . t('Choose Severity Level') . '</p>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('slacklognotification.settings')
      ->set('channel_link', $form_state->getValue('channel_link'))
      ->set('severity', $form_state->getValue('severity'))
      ->set('enable', $form_state->getValue('enable'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
