<?php

namespace Drupal\drupal_to_slack\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_to_slack_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'drupal_to_slack.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drupal_to_slack.settings');
    $form['drupal_to_slack_incoming_webhook_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Incoming webhook url'),
      '#default_value' => $config->get('drupal_to_slack_incoming_webhook_url'),
      '#description' => t('Enter incoming webhook url from slack account'),
    );
    $form['drupal_to_slack_channel_for_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Slack channel'),
      '#default_value' => $config->get('drupal_to_slack_channel_for_message'),
      '#description' => t('Enter slack channel for notification'),
    );
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    $form['node_to_notify_on_slack'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Content type to notify on slack'),
      '#default_value' => $config->get('node_to_notify_on_slack'),
      '#description' => t('Content type to notify on slack eg. blog'),
      '#options' => $contentTypesList,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('drupal_to_slack.settings');
    $config->set('drupal_to_slack_incoming_webhook_url', $form_state->getValue('drupal_to_slack_incoming_webhook_url'));
    $config->set('drupal_to_slack_channel_for_message', $form_state->getValue('drupal_to_slack_channel_for_message'));
    $config->set('node_to_notify_on_slack', $form_state->getValue('node_to_notify_on_slack'))
        ->save();

    parent::submitForm($form, $form_state);
  }

}
