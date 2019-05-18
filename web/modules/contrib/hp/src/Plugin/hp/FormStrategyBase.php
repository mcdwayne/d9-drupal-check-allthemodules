<?php

namespace Drupal\hp\Plugin\hp;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Symfony\Component\HttpFoundation\RequestStack;
use GuzzleHttp\Client;

/**
 * Provides the base class for form strategies.
 */
abstract class FormStrategyBase extends PluginBase implements FormStrategyInterface, ContainerFactoryPluginInterface {

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The request_stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new FormStrategyBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client
   *   The http client.
   * @param \Symfony\Component\HttpFoundation\RequestStack
   *   The Request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $http_client, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->httpClient = $http_client;
    $this->requestStack = $request_stack;
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
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }


  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state) {
    $class = get_class($this);
    $form['hp'][$form['#form_id']] = [
      '#type' => 'container',
      '#element_validate' => [
        [$class, 'formValidate'],
      ],
      '#hp_config' => $this->configuration,
      '#plugin_id' => $this->pluginId,
    ];
  }

  /**
   * Returns the current user's Human Presence session ID.
   */
  public function sessionId() {
    return $this->requestStack->getCurrentRequest()->cookies->get('ellipsis_sessionid');
  }

  /**
   * Returns the base URL to use for checking Human Presence.
   */
  public function checkSessionUrl() {
    $api_key = \Drupal::config('hp.settings')->get('api_key');
    return [
      'uri' => 'https://api.humanpresence.io/v2/checkhumanpresence/' . $this->sessionId(),
      'query' => [
        'apikey' => $api_key,
      ],
    ];
  }

  /**
   * Performs a Human Presence check for the current user's session.
   *
   * @return array
   *   An empty array in the event of an invalid request or the API response from
   *   Human Presence including the keys:
   *   - signal: a string representing the type of session, one of HUMAN, BOT, or
   *     BAD_SESSION (in the event the session has not interacted with the site
   *     enough for Human Presence to determine the type of session)
   *   - confidence: a numeric value ranging from 0 to 100 denoting the percentage
   *     confidence Human Presence has in its signal designation
   */
  public function checkSession() {
    // Check Human Presence via an API request.
    $url = $this->checkSessionUrl();
    $response = $this->httpClient->get($url['uri'], ['query' => $url['query']]);

    $status_code = $response->getStatusCode();
    // If we got an OK response from the API...
    if (!empty($status_code) && $status_code == 200) {
      // Return the data array.
      return json_decode($response->getBody());
    }
    else {
      // Otherwise return an empty array.
      return [];
    }
  }

  /**
   * Static element validate callback to instantiate the plugin.
   *
   * This way we can use a plugin object during form validation instead of
   * static methods.
   */
  public static function formValidate($element, FormStateInterface $form_state) {
    $plugin = self::createPlugin($element);
    $plugin->hpFormValidation($element, $form_state);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param array $element
   *   The form element having a #hp_config and a #plugin_id key.
   *
   * @return \Drupal\hp\Plugin\hp\FormStrategyInterface
   *   The plugin form.
   */
  public static function createPlugin(array $element) {
    $manager = \Drupal::service('plugin.manager.hp_form_strategy');
    $plugin_collection = new DefaultSingleLazyPluginCollection($manager, $element['#plugin_id'], $element['#hp_config']);
    return $plugin_collection->get($element['#plugin_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function hpFormValidation(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function access() {
    return TRUE;
  }

}
