<?php

namespace Drupal\sms_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsGateway;

class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_ui_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('sms_ui.settings');

    $sender_id_filter = $config->get('sender_id_filter');
    $form['sender_id_filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Sender ID Security'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['sender_id_filter']['include_superuser'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include superuser (user #1) in sender id blocking (for testing purposes)'),
      '#cols' => 40,
      '#default_value' => $sender_id_filter['include_superuser'],
    ];

    $form['sender_id_filter']['excluded'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded sender IDs'),
      '#description' => $this->t('Comma separated list of sender IDs that are not allowed for general use.
    		The wildcard \'%\' can be used to represent any character of any length.'),
      '#cols' => 40,
      '#default_value' => $sender_id_filter['excluded'],
    ];

    $form['sender_id_filter']['included'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sender IDs allowed for specific users'),
      '#placeholder' => $this->t('List of specific usernames and allowed sender IDs for them, this supercedes the exclusion list for those users.'
          . "\n" . 'Format user1: senderID1, senderID2, senderID3'
          . "\n" . '       user2: senderIDx, senderIDy, senderIDz, etc'
          . "\n" . '       user3, user4, user5: senderID4.'
          . "\n" . 'Use \'*\' instead of username to apply exception to all users.'
      ),
      '#cols' => 40,
      '#default_value' => $sender_id_filter['included'],
    ];

    $history_settings = $config->get('message_history');
    $form['message_history'] = [
      '#type' => 'details',
      '#title' => $this->t('Message History'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['message_history']['retention'] = [
      '#type' => 'select',
      '#title' => $this->t('Duration to retain message history'),
      '#options' => [
        '1'  => $this->t('1 day'),
        '4'  => $this->t('4 days'),
        '7'  => $this->t('7 days'),
        '14' => $this->t('2 weeks'),
        '21' => $this->t('3 weeks'),
        '30' => $this->t('1 month'),
        '91' => $this->t('3 months'),
        '183' => $this->t('6 months'),
        '365' => $this->t('1 year'),
        '0' => $this->t('Indefinite'),
      ],
      '#default_value' => $history_settings['retention'],
    ];

    $form['message_history']['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Message status notifications'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['message_history']['notifications']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send message status to users after SMS'),
      '#default_value' => $history_settings['notifications']['enable'],
    ];

    $form['message_history']['notifications']['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method to send message delivery reports'),
      '#options' => [
        'none' => $this->t('No delivery reports sent'),
        'sms' => $this->t('SMS (charges apply)'),
        'email' => $this->t('Email'),
        'both' => $this->t('Email and SMS (charges apply)'),
      ],
      '#default_value' => $history_settings['notifications']['method'],
    ];

    $form['message_history']['notifications']['threshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email / SMS Threshold'),
      '#description' => $this->t('Number of recipients above which message status will be sent to user.'),
      '#size' => 10,
      '#maxlength' => 20,
      '#default_value' => $history_settings['notifications']['threshold'],
    ];

    $form['message_history']['notifications']['format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Format to send delivery reports.'),
      '#options' => [
        'success_count' => $this->t('Quantity delivered'),
        'success_detail' => $this->t('Recipients delivered'),
        'fail_count' => $this->t('Quantity failed'),
        'fail_detail' => $this->t('Recipients failed'),
        'summary' => $this->t('Summary of successful and failed'),
      ],
      '#default_value' => $history_settings['notifications']['format'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){
    $this->config('sms_ui.settings')
      ->set('sender_id_filter', $form_state->getValue('sender_id_filter'))
      ->set('message_history', $form_state->getValue('message_history'))
      ->save();

    /** @var \Drupal\sms\Entity\SmsGatewayInterface[] $gateways */
    $gateways = SmsGateway::loadMultiple();
    foreach ($gateways as $gateway) {
      // Update retention times of all gateways.
      $gateway
        ->setRetentionDuration(Direction::OUTGOING, $form_state->getValue(['message_history', 'retention']) * 86400)
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms_ui.settings'];
  }

}
