<?php

namespace Drupal\chatbot_api_helloworld_intent_test\Plugin\Chatbot\Intent;

use Drupal\chatbot_api\Plugin\IntentPluginBase;

/**
 * Plugin implementation of chatbot intent.
 *
 * @Intent(
 *   id = "HelloWorldTest",
 *   label = @Translation("Hello World!")
 * )
 */
class HelloWorldIntent extends IntentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function process() {
    $this->response->setIntentResponse('Hello World Test');
  }

}
