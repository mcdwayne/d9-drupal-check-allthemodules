<?php

namespace Drupal\acquia_contenthub\EventSubscriber\HandleWebhook;

use Acquia\Hmac\ResponseSigner;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\HandleWebhookEvent;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responsible for handling site registration webhook responses.
 */
class RegisterWebhook implements EventSubscriberInterface {

  /**
   * The acquia_contenthub logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $channel;

  /**
   * RegisterWebhook constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->channel = $logger_factory->get('acquia_contenthub');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::HANDLE_WEBHOOK][] = 'onHandleWebhook';
    return $events;
  }

  /**
   * The method called for the AcquiaContentHubEvents::HANDLE_WEBHOOK event.
   *
   * @param \Drupal\acquia_contenthub\Event\HandleWebhookEvent $event
   *   The dispatched event.
   */
  public function onHandleWebhook(HandleWebhookEvent $event) {
    $payload = $event->getPayload();
    if ($payload['status'] == 'pending') {
      $client = $event->getClient();
      $uuid = isset($payload['uuid']) ? $payload['uuid'] : FALSE;

      if ($uuid && $payload['publickey'] == $client->getSettings()->getApiKey()) {
        $response = new Response();

        $psr7Factory = new DiactorosFactory();
        $psr7_request = $psr7Factory->createRequest($event->getRequest());

        $signer = new ResponseSigner($event->getKey(), $psr7_request);
        $signedResponse = $signer->signResponse($response);

        $event->setResponse($signedResponse);
        return;
      }
      else {
        $ip_address = $event->getRequest()->getClientIp();
        $message = new FormattableMarkup('Webhook [from IP = @IP] rejected (initiator and/or publickey do not match local settings): @whook', [
          '@IP' => $ip_address,
          '@whook' => print_r($payload, TRUE),
        ]);
        $this->channel->debug($message);
        $event->setResponse(new Response());
        return;
      }
    }
  }

}
