<?php

namespace Drupal\chatbot_api_apiai;

use DialogFlow\Model\Context;
use DialogFlow\Model\Webhook\Request;
use Drupal\chatbot_api\IntentRequestInterface;

/**
 * Proxy wrapping Api.ai Request in a IntentRequestInterface.
 *
 * @package Drupal\chatbot_api_apiai
 */
class IntentRequestApiAiProxy implements IntentRequestInterface {

  use ApiAiContextTrait;

  /**
   * Original object.
   *
   * @var \DialogFlow\Model\Webhook\Request
   */
  protected $original;

  /**
   * IntentRequestAlexaProxy constructor.
   *
   * @param \DialogFlow\Model\Webhook\Request $original
   *   Original request instance.
   */
  public function __construct(Request $original) {
    $this->original = $original;
  }

  /**
   * Proxy-er calling original request methods.
   *
   * @param string $method
   *   The name of the method being called.
   * @param array $args
   *   Array of arguments passed to the method.
   *
   * @return mixed
   *   The value returned from the called method.
   */
  public function __call($method, array $args) {
    return call_user_func_array([$this->original, $method], $args);
  }

  /**
   * Proxy-er calling original request properties.
   *
   * @param string $name
   *   The name of the property to get.
   *
   * @return mixed
   *   The value of the property, NULL otherwise.
   */
  public function __get($name) {
    if (isset($this->original->{$name})) {
      return $this->original->{$name};
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIntentName() {
    return $this->original->getResult()->getMetadata()->getIntentName();
  }

  /**
   * {@inheritdoc}
   */
  public function getIntentAttribute($name, $default = NULL) {
    $context_name = $this->getContextName($name);
    $contexts = $this->original->getResult()->getContexts();

    // Loop contexts to find a match.
    foreach ($contexts as $context) {
      // Extract context.
      if ($context instanceof Context && $this->contextNameIs($context, $context_name)) {

        // API.ai supports context parameters. Intents can get/set parameters
        // by separating the context name and the parameter name with a period
        // i.e. context_name.parameter_name .
        $params = $context->getParameters();

        // Early return if parameter exists.
        if (isset($params[$this->getParameterName($name)])) {
          return $params[$this->getParameterName($name)];
        }
      }
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getIntentSlot($name, $default = NULL) {
    $params = $this->original->getResult()->getParameters();

    return isset($params[$name]) ? $params[$name] : $default;
  }

}
