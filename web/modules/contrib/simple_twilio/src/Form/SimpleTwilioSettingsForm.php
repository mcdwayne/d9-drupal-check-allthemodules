<?php

namespace Drupal\simple_twilio\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for Simple Twilio settings.
 */
class SimpleTwilioSettingsForm extends ConfigFormBase {

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a SmsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteBuilderInterface $route_builder, RequestContext $request_context) {
    parent::__construct($config_factory);
    $this->routeBuilder = $route_builder;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_twilio_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $simple_twilio_settings = $this->config('simple_twilio.settings');

    $form['simple_twilio'] = [
      '#type' => 'details',
      '#title' => $this->t('Simple Twilio'),
      '#open' => TRUE,
    ];

    $form['simple_twilio']['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('API keys can be found at <a href="https://www.twilio.com/console">https://www.twilio.com/console</a>.'),
    ];

    $form['simple_twilio']['account_sid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account SID'),
      '#default_value' => $simple_twilio_settings->get('account_sid'),
      '#placeholder' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
      '#required' => TRUE,
    ];

    $form['simple_twilio']['auth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auth token'),
      '#default_value' => $simple_twilio_settings->get('auth_token'),
      '#placeholder' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
      '#required' => TRUE,
    ];

    $form['simple_twilio']['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From number'),
      '#default_value' => $simple_twilio_settings->get('from'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('simple_twilio.settings')
      ->set('account_sid', $form_state->getValue('account_sid'))
      ->set('auth_token', $form_state->getValue('auth_token'))
      ->set('from', $form_state->getValue('from'))
      ->save();

    drupal_set_message($this->t('Simple Twilio settings saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_twilio.settings'];
  }

}
