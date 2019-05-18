<?php

namespace Drupal\chrome_push_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\chrome_push_notification\Model\ChromeApiCall;

/**
 * Class ChromePushNotificationSendMessageForm.
 *
 * @package Drupal\chrome_push_notification\Form
 */
class ChromePushNotificationSendMessageForm extends ConfigFormBase {

  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'chrome_push_notification.sendMessage',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chrome_push_notification_send_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Get config.
    $config_gpn = $this->config('chrome_push_notification.sendMessage');

    // Google Chrome Messaging.
    $form['sendMessage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Data of the Notification'),
      '#description' => $this->t('Enter Data of the Notification.'),
    ];

    $form['sendMessage']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification Title'),
      '#description' => $this->t('Enter the Title of the Notification.'),
      '#default_value' => $config_gpn->get('chrome_notification_title'),
    ];

    $form['sendMessage']['image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification Image URL'),
      '#description' => $this->t('Enter the Image URL which will show in the Notification.'),
      '#default_value' => $config_gpn->get('chrome_notification_image_url'),
    ];

    $form['sendMessage']['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification URL'),
      '#description' => $this->t('Enter the URL on which user will redirect after clicking on Notification.'),
      '#default_value' => $config_gpn->get('chrome_notification_link'),
    ];

    $form['sendMessage']['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification Message'),
      '#description' => $this->t('Enter the Message of the Notification.'),
      '#default_value' => $config_gpn->get('chrome_notification_message'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Both the field is should be manatory.
    $title = strtolower($form_state->getValue('title'));
    if (empty($title)) {
      $form_state->setErrorByName('title', $this->t('Please enter title for notification.'));
    }

    $imageUrl = strtolower($form_state->getValue('image_url'));
    if (empty($imageUrl)) {
      $form_state->setErrorByName('image_url', $this->t('Please enter image url for notification.'));
    }

    $link = strtolower($form_state->getValue('link'));
    if (empty($link)) {
      $form_state->setErrorByName('link', $this->t('Please enter link for notification.'));
    }

    $message = strtolower($form_state->getValue('message'));
    if (empty($message)) {
      $form_state->setErrorByName('message', $this->t('Please enter message for notification.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Store GCM config.
    $config_gpn = $this->config('chrome_push_notification.sendMessage');
    $config_gpn->set('chrome_notification_title', $form_state->getValue('title'));
    $config_gpn->set('chrome_notification_image_url', $form_state->getValue('image_url'));
    $config_gpn->set('chrome_notification_link', $form_state->getValue('link'));
    $config_gpn->set('chrome_notification_message', $form_state->getValue('message'));
    $config_gpn->save();

    $registrationIdsData = $this->database->select(ChromeApiCall::$chromeNotificationTable)
      ->fields(ChromeApiCall::$chromeNotificationTable, ['id', 'register_id'])
      ->execute();
    $registrationIds = $registrationIdsData->fetchAll(\PDO::FETCH_OBJ);
    $batch = [
      'title' => $this->t('Send Push Notification...'),
      'operations' => [
        [
          '\Drupal\chrome_push_notification\Model\ChromeApiCall::sendNotificationStart',
          [$registrationIds],
        ],
      ],
      'finished' => '\Drupal\chrome_push_notification\Model\ChromeApiCall::sendNotificationFinished',
    ];
    batch_set($batch);
    drupal_set_message($this->t('Chrome Push Notification successfully sent to all registered device.'));
  }

}
