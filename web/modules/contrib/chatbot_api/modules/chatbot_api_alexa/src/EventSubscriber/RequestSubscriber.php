<?php

namespace Drupal\chatbot_api_alexa\EventSubscriber;

use Alexa\Request\IntentRequest;
use Drupal\alexa\AlexaEvent;
use Drupal\chatbot_api\Plugin\IntentPluginManager;
use Drupal\chatbot_api_alexa\IntentRequestAlexaProxy;
use Drupal\chatbot_api_alexa\IntentResponseAlexaProxy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber for Alexa request events.
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
    $events['alexaevent.request'][] = ['onRequest', 0];
    return $events;
  }

  /**
   * Called upon a request event.
   *
   * @param \Drupal\alexa\AlexaEvent $event
   *   The event object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function onRequest(AlexaEvent $event) {
    // Early return if current request is not an IntentRequest.
    if (!($event->getRequest() instanceof IntentRequest)) {
      return;
    }

    /** @var \Drupal\chatbot_api_alexa\IntentRequestAlexaProxy|\Alexa\Request\IntentRequest $request */
    $request = new IntentRequestAlexaProxy($event->getRequest());
    $response = new IntentResponseAlexaProxy($event->getResponse());

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
