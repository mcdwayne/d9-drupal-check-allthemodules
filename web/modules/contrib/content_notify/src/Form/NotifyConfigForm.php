<?php

namespace Drupal\content_notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\content_notify\ContentNotifyManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure content notification settings for this site.
 */
class NotifyConfigForm extends ConfigFormBase {

  /**
   * Module handler service object.
   *
   * @var \Drupal\content_notify\ContentNotifyManager
   */
  protected $contentNotifyManager;

  /**
   * Constructs a new GeneralConfForm object.
   */
  public function __construct(ContentNotifyManager $content_notify_manager) {
    $this->contentNotifyManager = $content_notify_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_notify.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_notify_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_notify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('content_notify.settings');
    if ($this->contentNotifyManager->checkSchedulerExists()) {

      $form['notify'] = [
        '#title' => $this->t('Notifications about content about to be unpublished'),
        '#description' => $this->t('You need to set which bundles notices should be sent on. The bundles you choose need to have scheduler settings enabled.'),
        '#type' => 'details',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['notify']['notify_unpublish_bundles'] = [
        '#title' => $this->t('Bundles to find unpublish dates in'),
        '#type' => 'checkboxes',
        '#options' => node_type_get_names(),
        '#default_value' => $config->get('notify_unpublish_bundles'),
      ];

      $form['notify']['set_unpublish_time'] = [
        '#title' => $this->t('Days from creation date to auto expire node'),
        '#type' => 'number',
        '#field_suffix' => $this->t('Days'),
        '#default_value' => $config->get('set_unpublish_time'),
        '#description' => $this->t('if the user does not actively set an unpublish date then you can set how many days from the creation of the node should auto expired? If user has set an unpublish date of the node then this value will not be used.'),
      ];

      $form['notify']['notify_unpublish_time'] = [
        '#title' => $this->t('Days before unpublishing to send notification'),
        '#type' => 'number',
        '#default_value' => $config->get('notify_unpublish_time'),
        '#field_suffix' => $this->t('Days'),
        '#description' => $this->t('How many days before unpublishing a notification e-mail be sent to the user?'),
      ];

      $form['notify']['email_settings'] = [
        '#type' => 'details',
        '#description' => $this->t('Mail will always go as digest email with all nodes per specific user'),
        '#title' => $this->t('Mail settings'),
        '#collapsed' => FALSE,
      ];

      $form['notify']['email_settings']['notify_unpublish_receiver'] = [
        '#title' => $this->t('Receiver email address for notification'),
        '#type' => 'email',
        '#default_value' => $config->get('notify_unpublish_receiver'),
        '#description' => $this->t('this email address will get notification. If you want owner of node to  get email then leave this field empty'),
      ];

      $form['notify']['email_settings']['notify_unpublish_subject'] = [
        '#title' => $this->t('Subject'),
        '#type' => 'textfield',
        '#default_value' => $config->get('notify_unpublish_subject'),
        '#description' => $this->t('What text should be sent as subject of notification.'),
      ];

      $form['notify']['email_settings']['notify_unpublish_body'] = [
        '#title' => $this->t('Body'),
        '#type' => 'textarea',
        '#default_value' => $config->get('notify_unpublish_body'),
        '#description' => $this->t('What text should be sent as notification. Tokens [content-notify:digest-nodes] is only available.'),
      ];
    }

    $form['invalid'] = [
      '#title' => $this->t('Notify user of old content'),
      '#description' => $this->t('At creation of a node we automatically register a date in the future to remind the creator of the node to "check in" on the node to help the editor keep the site up to date.'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['invalid']['notify_invalid_bundles'] = [
      '#title' => $this->t('Bundles to automatically send notification of old content about'),
      '#type' => 'checkboxes',
      '#options' => node_type_get_names(),
      '#default_value' => $config->get('notify_invalid_bundles'),
      '#description' => $this->t('On what bundles should we notify about old content.'),
    ];

    $form['invalid']['notify_invalid_time'] = [
      '#title' => $this->t('Days from publish date to set send mail about content validity.'),
      '#type' => 'number',
      '#field_suffix' => $this->t('Days'),
      '#default_value' => $config->get('notify_invalid_time'),
      '#description' => $this->t('How many days after publishing should a mail go out?.'),
    ];

    $form['invalid']['email_settings'] = [
      '#type' => 'details',
      '#description' => $this->t('Mail will always go as digest email with all nodes per specific user'),
      '#title' => $this->t('Mail settings'),
      '#collapsed' => FALSE,
    ];
    $form['invalid']['email_settings']['notify_invalid_digest_duration'] = [
      '#title' => $this->t('Interval of digest email'),
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('Immediately'),
        '7' => $this->t('Weekly'),
        '30' => $this->t('Monthly'),
      ],
      '#default_value' => $config->get('notify_invalid_digest_duration'),
      '#description' => $this->t('What should be interval of sending digest email.'),
    ];

    $form['invalid']['email_settings']['notify_invalid_receiver'] = [
      '#title' => $this->t('Receiver email address for notification old content'),
      '#type' => 'email',
      '#default_value' => $config->get('notify_invalid_receiver'),
      '#description' => $this->t('this email address will get notification. If you want content owner get email then leave this field empty'),
    ];

    $form['invalid']['email_settings']['notify_invalid_subject'] = [
      '#title' => $this->t('Subject'),
      '#type' => 'textfield',
      '#default_value' => $config->get('notify_invalid_subject'),
      '#description' => $this->t('What text should be sent as subject notification.'),
    ];

    $form['invalid']['email_settings']['notify_invalid_body'] = [
      '#title' => $this->t('Body'),
      '#type' => 'textarea',
      '#default_value' => $config->get('notify_invalid_body'),
      '#description' => $this->t('What text should be sent as notification. Tokens [content-notify:digest-nodes] is only available'),
    ];

    $form['array_filter'] = ['#type' => 'value', '#value' => TRUE];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $notify_unpublish_bundles = array_filter($form_state->getValue('notify_unpublish_bundles'));
    $notify_invalid_bundles = array_filter($form_state->getValue('notify_invalid_bundles'));

    sort($notify_unpublish_bundles);
    sort($notify_invalid_bundles);

    $values = $form_state->getValues();

    $this->config('content_notify.settings')
      ->set('notify_invalid_bundles', $notify_invalid_bundles)
      ->set('notify_invalid_digest_duration', $values['notify_invalid_digest_duration'])
      ->set('notify_invalid_receiver', $values['notify_invalid_receiver'])
      ->set('notify_invalid_time', $values['notify_invalid_time'])
      ->set('notify_invalid_subject', $values['notify_invalid_subject'])
      ->set('notify_invalid_body', $values['notify_invalid_body'])
      ->save();

    if ($this->contentNotifyManager->checkSchedulerExists()) {
      $this->config('content_notify.settings')
        ->set('notify_unpublish_bundles', $notify_unpublish_bundles)
        ->set('notify_unpublish_receiver', $values['notify_unpublish_receiver'])
        ->set('notify_unpublish_time', $values['notify_unpublish_time'])
        ->set('notify_unpublish_subject', $values['notify_unpublish_subject'])
        ->set('notify_unpublish_body', $values['notify_unpublish_body'])
        ->set('set_unpublish_time', $values['set_unpublish_time'])
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
