<?php

namespace Drupal\chatbot_api_apiai\EventSubscriber;

use Drupal\api_ai_webhook\ApiAiEvent;
use Drupal\chatbot_api\Plugin\IntentPluginManager;
use Drupal\chatbot_api_apiai\IntentRequestApiAiProxy;
use Drupal\chatbot_api_apiai\IntentResponseApiAiProxy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber for ApiAi request events.
 */
class RequestSubscriber implements EventSubscriberInterface {

  /**
   * Chatbot API intent plugin manager.
   *
   * @var \Drupal\chatbot_api\Plugin\IntentPluginManager
   */
  protected $intentPluginManager;

  /**
   * Construct a new RequestSubscriber.
   *
   * @param \Drupal\chatbot_api\Plugin\IntentPluginManager $intentPluginManager
   *   Intent plugin manager service.
   */
  public function __construct(IntentPluginManager $intentPluginManager) {
    $this->intentPluginManager = $intentPluginManager;
  }

  /**
   * Gets the event.
   */
  public static function getSubscribedEvents() {
    $events[ApiAiEvent::NAME][] = ['onRequest', 0];
    return $events;
  }

  /**
   * Called upon a request event.
   *
   * @param \Drupal\api_ai_webhook\ApiAiEvent $event
   *   The event object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function onRequest(ApiAiEvent $event) {
    /** @var \Drupal\chatbot_api_apiai\IntentRequestApiAiProxy $request */
    $request = new IntentRequestApiAiProxy($event->getRequest());
    $response = new IntentResponseApiAiProxy($event->getResponse());

    $manager = $this->intentPluginManager;
    if ($manager->hasDefinition($request->getIntentName())) {

      $configuration = [
        'request' => $request,
        'response' => $response,
      ];
      $plugin = $manager->createInstance($request->getIntentName(), $configuration);
      $plugin->process();
    }
  }

}
