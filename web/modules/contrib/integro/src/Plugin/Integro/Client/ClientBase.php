<?php

namespace Drupal\integro\Plugin\Integro\Client;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\integro\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for plugins.
 */
abstract class ClientBase extends PluginBase implements ContainerFactoryPluginInterface, ClientInterface {

  use DependencySerializationTrait;

  /**
   * The client configuration.
   *
   * @var array
   */
  protected $clientConfiguration = [];

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function authPrepare(array $configuration = []) {
    // To be implemented in descendants.
  }

  /**
   * {@inheritdoc}
   */
  public function auth(array $configuration = []) {
    // To be implemented in descendants.
    $this->authPrepare($configuration);
    $auth_result = [];
    $result = $this->authHandle($auth_result);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function authHandle(array $result = []) {
    // To be implemented in descendants.
    $result_handled = [];
    return $result_handled;
  }

  /**
   * {@inheritdoc}
   */
  public function requestPrepare() {
    // To be implemented in descendants.
    // @see $this->clientConfiguration.
  }

  /**
   * {@inheritdoc}
   */
  public function request() {
    // To be implemented in descendants.
    $this->requestPrepare();
    $request_result = [];
    $result = $this->requestHandle($request_result);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function requestHandle($result) {
    // To be implemented in descendants.
    $result_handled = $result;
    return $result_handled;
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
  public function defaultConfiguration() {
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if ($this->pluginId == '') {
      return [];
    }

    $form['#type'] = 'fieldset';
    $form['#title'] = $this->getLabel();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

}
