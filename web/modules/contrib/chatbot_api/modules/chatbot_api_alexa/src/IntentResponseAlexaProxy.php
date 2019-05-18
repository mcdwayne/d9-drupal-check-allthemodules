<?php

namespace Drupal\chatbot_api_alexa;

use Alexa\Response\Response;
use Drupal\chatbot_api\IntentResponseInterface;

/**
 * Proxy wrapping Alexa Response in a ChatbotRequestInterface.
 *
 * @package Drupal\chatbot_api_alexa
 */
class IntentResponseAlexaProxy implements IntentResponseInterface {

  /**
   * Original object.
   *
   * @var \Alexa\Response\Response
   */
  protected $original;

  /**
   * IntentResponseAlexaProxy constructor.
   *
   * @param \Alexa\Response\Response $original
   *   Original response instance.
   */
  public function __construct(Response $original) {
    $this->original = $original;
  }

  /**
   * Proxy-er calling original response methods.
   *
   * @param string $method
   *   The name of the method being called.
   * @param array $args
   *   Array of arguments passed to the method.
   *
   * @return mixed
   *   Value returned from the method.
   */
  public function __call($method, array $args) {
    return call_user_func_array([$this->original, $method], $args);
  }

  /**
   * {@inheritdoc}
   */
  public function addIntentAttribute($name, $value) {
    $this->original->addSessionAttribute($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setIntentResponse($text) {
    return $this->original->respond($text);
  }

  /**
   * {@inheritdoc}
   */
  public function setIntentDisplayCard($content, $title = "") {
    return $this->original->withCard($title, $content);
  }

}
