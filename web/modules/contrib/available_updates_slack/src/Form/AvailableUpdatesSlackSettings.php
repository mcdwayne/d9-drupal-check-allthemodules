<?php

namespace Drupal\available_updates_slack\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\available_updates_slack\Manager\SlackNotificationTypePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

use Drupal\Component\Utility\UrlHelper;


/**
 * Settings form for social content feed module.
 *
 * @package Drupal\img_content_feed
 */
class AvailableUpdatesSlackSettings extends ConfigFormBase {

  /** @var SlackNotificationTypePluginManager */
  private $plugin_manager;

  /**
   * Constructor for AvailableUpdatesSlackSettings
   *
   * @param ConfigFactoryInterface $config_factory
   * @param SlackNotificationTypePluginManager $plugin_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, SlackNotificationTypePluginManager $plugin_manager) {
    parent::__construct($config_factory);
    $this->plugin_manager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('available_updates_slack.slack_notification_type_manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'available_updates_slack.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'available_updates_slack_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('available_updates_slack.settings');
    $notification_type_map = $this->plugin_manager->getIdLabelMapping();
    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slack Webhook URL'),
      '#description' => $this->t('The Slack endpoint as defined by your webhook'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('webhook_url'),
      '#element_validate' => [
        [$this, 'validateUrl'],
      ],
    ];

    $form['notification_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Notification Type'),
      '#description' => $this->t('Select the notification type'),
      '#required' => true,
      '#default_value' => $config->get('notification_type'),
      '#options' => $notification_type_map,
      '#element_validate' => [
        [$this, 'validatedOption']
      ]
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate URL
   */
  public function validateUrl($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    // Entry must be a valid absolute URL
    if(!UrlHelper::isValid($value, TRUE)) {
      $form_state->setError($element, $this->t('API method must be a valid absolute URL!'));
    }
  }

  /**
   * Validate option is in array
   */
  public function validatedOption($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    // Validate option
    if (!array_key_exists($value, $this->plugin_manager->getIdLabelMapping())) {
      $form_state->setError($element, $this->t('Invalid Option selected'));
    } 
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('available_updates_slack.settings')
      // Global settings
      ->set('webhook_url', $form_state->getValue('webhook_url'))
      ->set('notification_type', $form_state->getValue('notification_type'))
      ->save();
  }

}
