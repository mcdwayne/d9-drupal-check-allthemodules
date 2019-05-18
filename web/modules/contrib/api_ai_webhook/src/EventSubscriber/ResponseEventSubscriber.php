<?php

namespace Drupal\api_ai_webhook\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Subscribe to the response in order to better handle Api.AI errors.
 *
 * @package Drupal\api_ai_webhook
 */
class ResponseEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.response'][] = ['alterResponse'];

    return $events;
  }

  /**
   * Alter the response if is an Api.AI webhook error.
   *
   * This method is called whenever the kernel.response event is dispatched,
   * then we filter responses/requests coming form Api.AI webhook.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The repose event.
   */
  public function alterResponse(FilterResponseEvent $event) {
    if (rtrim($event->getRequest()->getPathInfo(), '/') === '/api.ai/webhook') {

      // Handle errors.
      if ($event->getResponse()->isClientError() || $event->getResponse()->isServerError()) {
        $data = [
          'status' => [
            'code' => $event->getResponse()->getStatusCode(),
            'errorType' => JsonResponse::$statusTexts[$event->getResponse()->getStatusCode()],
          ],
          'speech' => 'An error occurred.',
        ];

        $event->setResponse(new JsonResponse($data, $event->getResponse()->getStatusCode()));
      }
    }
  }

}
