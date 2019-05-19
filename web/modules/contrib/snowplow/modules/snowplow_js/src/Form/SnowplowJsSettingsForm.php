<?php

namespace Drupal\snowplow\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\snowplow\Form
 */
class SnowplowJsSettingsForm extends ConfigFormBase {

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a \Drupal\snowplow_js\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'snowplow_js_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('snowplow.settings');
    $form['app'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
    ];

    $form['app']['snowplow_app_id'] = [
      '#title' => $this->t('Snowplow App ID'),
      '#type' => 'textfield',
      '#default_value' => $config->get('app_id'),
      '#size' => 15,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#description' => $this->t('This id will be set on the tracker.'),
    ];

    $form['app']['snowplow_app_endpoint'] = [
      '#title' => $this->t('Endpoint URL'),
      '#type' => 'textfield',
      '#default_value' => $config->get('app_endpoint'),
      '#size' => 128,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => $this->t('Enter the endpoint where the events will be tracked, the format of this url is important and will be the most common cause of missing events if incorrectly tracked. (Leave off the http://)'),
    ];

    // Visibility settings.
    $form['tracking_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Tracking scope'),
    ];

    $form['tracking'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['snowplow/snowplow.admin'],
      ],
    ];

    $form['tracking']['login_tracking'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Login'),
    ];

    $form['tracking']['login_tracking']['snowplow_track_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track logins?'),
      '#default_value' => $config->get('track_login'),
      '#description' => $this->t('Track user logins to snowplow.'),
    ];

    $form['tracking']['logout_tracking'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Logout'),
    ];

    $form['tracking']['logout_tracking']['snowplow_track_logout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track logouts?'),
      '#default_value' => $config->get('track_logout'),
      '#description' => $this->t('Track user logouts to snowplow.'),
    ];

    $form['tracking']['new_user_tracking'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('New User'),
    ];

    $form['tracking']['new_user_tracking']['snowplow_track_new_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track new users?'),
      '#default_value' => $config->get('track_new_user'),
      '#description' => $this->t('Track new user registrations to snowplow'),
    ];

    // Message specific configurations.
    $form['tracking']['messagetracking'] = [
      '#type' => 'fieldset',
      '#title' => t('Messages'),
    ];

    $form['tracking']['messagetracking']['snowplow_trackmessages'] = [
      '#type' => 'checkboxes',
      '#title' => t('Track messages of type'),
      '#default_value' => $config->get('trackmessages'),
      '#description' => t('Track user messages to snowplow'),
      '#options' => [
        'status' => t('Status message'),
        'warning' => t('Warning message'),
        'error' => t('Error message'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('snowplow.settings');
    $values = $form_state->getValues();
    $config->set('app_id', $values['snowplow_app_id'])
      ->set('app_endpoint', $values['snowplow_app_endpoint'])
      ->set('track_login', $values['snowplow_track_login'])
      ->set('track_logout', $values['snowplow_track_logout'])
      ->set('track_new_user', $values['snowplow_track_new_user'])
      ->set('trackmessages', $values['snowplow_trackmessages'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['snowplow.settings'];
  }

}
