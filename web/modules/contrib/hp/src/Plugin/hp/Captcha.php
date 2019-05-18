<?php

namespace Drupal\hp\Plugin\hp;

use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the CAPTCHA Human Presence form protection plugin.
 *
 * @HpFormStrategy(
 *   id = "captcha",
 *   label = "CAPTCHA",
 * )
 */
class Captcha extends FormStrategyBase implements FormStrategyInterface {

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $http_client, RequestStack $request_stack, ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $http_client, $request_stack);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('request_stack'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'minimal_confidence' => '100',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if (isset($form['captcha']) && empty($storage['hp_captcha'])) {
      unset($form['captcha']);
    }

    parent::formAlter($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['minimal_confidence'] = [
      '#type' => 'number',
      '#title' => $this->t('The minimal confidence needed to pass form validation.'),
      '#description' => $this->t('Should be a number between 0 and 100. 0 means always pass validation.'),
      '#default_value' => $this->configuration['minimal_confidence'],
      '#min' => 0,
      '#max' => 100,
    ];
    $form['captcha'] = [
      '#markup' => $this->t('CAPTCHA should be configured through its own configuration forms.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['minimal_confidence'] = $values['minimal_confidence'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hpFormValidation(array &$element, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if (!empty($storage['hp_captcha'])) {
      return;
    }

    // Fetch the Human Presence check response.
    $response = $this->checkSession();

    // If the response is 100% sure the user is a human, do not prevent submission.
    if (!empty($response) && $response->signal == 'HUMAN' && $response->confidence >= $element['#hp_config']['minimal_confidence']) {
     // return;
    }

    // Otherwise fail the detection check and prevent form submission.
    // Can't use form_set_error() since that prevents form rebuilding.
    drupal_set_message(t('Sorry, we could not process your submission at this time. Please solve the CAPTCHA below.'), 'error');
    $message = $this->t('Suspicious form submission blocked: <pre>@response</pre>', ['@response' => print_r($response, TRUE)]);
    \Drupal::logger('hp')->notice($message);
    $storage['hp_captcha'] = TRUE;
    $form_state->setStorage($storage);
    $form_state->setRebuild();
    $form_state->setCached();
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return $this->moduleHandler->moduleExists('captcha');
  }

}
