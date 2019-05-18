<?php

namespace Drupal\js\Plugin\Js;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Plugin\PluginBase;
use Drupal\js\JsResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Base JsCallback class.
 */
abstract class JsCallbackBase extends PluginBase implements JsCallbackInterface {

  /**
   * @var \Drupal\Core\DependencyInjection\ClassResolver
   */
  protected $classResolver;

  /**
   * @var \Drupal\js\Js
   */
  protected $js;

  /**
   * @var \Drupal\js\JsResponse
   */
  protected $response;

  /**
   * @var array
   */
  protected $parameters;

  /**
   * @var \Drupal\Core\Routing\Enhancer\ParamConversionEnhancer
   */
  protected $paramConversion;

  /**
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  protected $title;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->classResolver = \Drupal::service('class_resolver');
    $this->js = \Drupal::service('js.callback');
    $this->paramConversion = \Drupal::service('route_enhancer.param_conversion');
    $this->title = isset($this->pluginDefinition['title']) ? $this->pluginDefinition['title'] : '';

    try {
      $this->response = $this->classResolver->getInstanceFromDefinition($this->pluginDefinition['response']);
    }
    catch (\Exception $e) {
      // Intentionally left empty since this is checked below.
    }

    if (!($this->response instanceof JsResponse)) {
      throw new \InvalidArgumentException('JS Callback requires that the "response" option be either a service identifier or a class name creating an instance that is or inherits from \Drupal\js\JsResponse.');
    }

    $this->parameters = $this->convertParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function accessDeniedMessage() {
    return $this->t('Unauthorized access. If you feel this is in error, please contact the site administrator.');
  }

  /**
   * {@inheritdoc}
   */
  public function anonymousUserMessage() {
    return $this->t('Cannot complete request: requires authentication. Try refreshing this page or logging out and back in again.');
  }

  /**
   * {@inheritdoc}
   */
  public function call($method) {
    return call_user_func_array([$this, $method], $this->mapMethodParameters($method));
  }

  /**
   * {@inheritdoc}
   */
  public function captureOutput() {
    return !!$this->pluginDefinition['capture_output'];
  }

  /**
   * Convert callback parameters into fully loaded objects.
   *
   * @return array
   *   An associative array of converted parameters.
   */
  protected function convertParameters() {
    if (!$this->js->isExecuting()) {
      return [];
    }

    // Create a standalone "Request" object.
    $request = $this->js->getRequest();
    $parameters = $request->query->all() + $request->request->all();

    if ($this->pluginDefinition['parameters']) {
      $options = ['parameters' => $this->pluginDefinition['parameters']];
      $route = new Route('/{' . implode('}/{', array_keys($parameters)) . '}', $parameters, [], $options);
      $parameters = $this->paramConversion->enhance(['_route_object' => $route] + $parameters, Request::create(''));
      unset($parameters['_route_object']);
      unset($parameters['_raw_variables']);

      // Handle JSON data.
      $boolean_values = ['true', 'false', '1', '0', 'yes', 'no'];
      foreach ($parameters as $key => $value) {
        // Convert possible JSON strings into arrays.
        if (is_string($value) && $value !== '' && ($value[0] === '[' || $value[0] === '{') && ($json = Json::decode($value))) {
          $parameters[$key] = $json;
        }
        // Convert certain JSON values into booleans.
        elseif (is_string($value) && in_array($value, $boolean_values)) {
          $parameters[$key] = (bool) $value;
        }
      }
    }

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function csrfToken() {
    return !!$this->pluginDefinition['csrf_token'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedMethods() {
    return $this->pluginDefinition['allowed_methods'];
  }

  /**
   * {@inheritdoc}
   */
  public function invalidTokenMessage() {
    return $this->t('Cannot complete request: invalid CSRF token. Try refreshing this page or logging out and back in again.');
  }

  /**
   * {@inheritdoc}
   */
  public function mapMethodParameters($method) {
    $args = [];
    $parameters = $this->getParameters();
    $reflection = new \ReflectionClass($this);
    $function = $reflection->getMethod($method);
    foreach ($function->getParameters() as $param) {
      $default_value = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : NULL;
      $value = isset($parameters[$param->name]) ? $parameters[$param->name] : $default_value;

      // Type case values if not an object.
      if (isset($value) && !is_object($value) && ($type = gettype($default_value))) {
        settype($value, $type);
      }

      $args[$param->name] = $value;
    }
    return $args;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title = '') {
    $this->title = $title;
  }

  /**
   * {@inheritdoc}
   */
  public function methodNotAllowedMessage() {
    $allowed = $this->getAllowedMethods();
    return $this->formatPlural(
      count($allowed),
      'Method not allowed: %method. Only the %allowed_methods method is allowed. Please contact the site administrator if this problem persists.',
      'Method not allowed: %method. Only the %allowed_methods methods are allowed. Please contact the site administrator if this problem persists.',
      [
        '%method' => $this->js->getRequest()->getMethod(),
        '%allowed_methods' => implode(', ', $allowed),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return TRUE;
  }

}
