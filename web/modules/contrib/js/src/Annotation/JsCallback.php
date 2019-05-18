<?php

namespace Drupal\js\Annotation;

use Drupal\Component\Annotation\Plugin;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines a JsCallback annotation object.
 *
 * @Annotation
 */
class JsCallback extends Plugin {

  /**
   * The HTTP request methods allowed.
   *
   * This must be an array of string values. If the request does not match any
   * of the allowed methods defined by the callback, it will be rejected.
   *
   * @var array
   */
  public $allowed_methods = [];

  /**
   * Captures any printed output from the callback.
   *
   * Normally a callback should return its content, not print it. By default
   * this property is enabled and will discard any printed output.
   *
   * @var bool
   *
   * @see hook_js_captured_content_alter
   */
  public $capture_output = TRUE;

  /**
   * Generates a token to prevent CSRF attacks for authenticated users.
   *
   * If the callback is only accessible to authenticated users, it is strongly
   * recommended that this is not disabled, otherwise your site could
   * potentially be susceptible to CSRF attacks.
   *
   * If the callback needs to support both anonymous and authenticated users,
   * then this should be disable and the responsibility of checking request
   * validity falls to the callback itself.
   *
   * @var bool
   */
  public $csrf_token = TRUE;

  /**
   * Dynamically loads arguments for a callback.
   *
   * An associative array of key/value pairs where parameter name is the key
   * and a callback is the value. The callback will be passed a single
   * argument, the value of the passed parameter.
   *
   * The JS module automatically detects any "PARAMETER_load" functions that
   * exists based on if "process request" is enabled and the function
   * explicitly specifies that parameter in it's callback function signature.
   *
   * For example: if one of the parameters passed is "node" and the callback
   * function signature defines a $node argument, then a "node_load" function
   * will be invoked (if the function exists). Optionally, to disable the
   * automatic load callback for a parameter, you can set the callback to
   * FALSE or to disable all automatic "load arguments" processing you may
   * set FALSE for the entire "load arguments" property.
   *
   * @todo Fix this documentation to reference route option parameters.
   *
   * @var array
   */
  public $parameters = [];

  /**
   * The class or service that sends the result of the callback to the browser.
   *
   * This response object, whether a stand alone class or service, must be an
   * instance of \Drupal\js\JsResponse.
   *
   * Defaults to \Drupal\js\JsResponse.
   *
   * @var string
   *
   * @see \Drupal\js\JsResponse
   */
  public $response = '\\Drupal\\js\\JsResponse';

  /**
   * The human readable title of the callback.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /** @var  \Drupal\Core\ParamConverter\ParamConverterManager */
  protected $paramConvertManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($values) {
    parent::__construct($values);
    $this->paramConvertManager = \Drupal::service('paramconverter_manager');
    $this->setAllowedMethods();
    $this->setParameterConverters();
  }

  /**
   * Sets default allowed methods.
   */
  public function setAllowedMethods() {
    if (!$this->definition['allowed_methods']) {
      $this->definition['allowed_methods'] = ['POST', 'GET'];
    }
  }

  /**
   * Sets parameter converters.
   */
  public function setParameterConverters() {
    if (!$this->definition['parameters']) {
      return;
    }

    // Create an empty "route" so we can add the necessary param converts to it.
    $route = new Route('');
    $route->setOption('parameters', $this->definition['parameters']);

    // Create a route collection for the parameter convert manager.
    $collection = new RouteCollection();
    $collection->add('', $route);
    $this->paramConvertManager->setRouteParameterConverters($collection);

    // Retrieve the filled out parameters.
    $this->definition['parameters'] = $route->getOption('parameters');
  }

}

