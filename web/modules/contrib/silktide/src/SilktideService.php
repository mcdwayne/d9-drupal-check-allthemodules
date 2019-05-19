<?php

namespace Drupal\silktide;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\silktide\event\SilktideEvent;
use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SilktideService.
 *
 * @package Drupal\silktide
 */
class SilktideService implements EventSubscriberInterface {

  /**
   * Silktide status notification end point.
   */
  const SILKTIDE_URL = 'https://api.silktide.com/cms/update';

  /**
   * Current version of this module.
   */
  const VERSION = '1.3';

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   A http client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   A logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration settings factory.
   */
  public function __construct(
    ClientInterface $client,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory
  ) {

    $this->client = $client;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * Get the list of subscribed events.
   *
   * @return array
   *   Events to subscribe to.
   */
  public static function getSubscribedEvents() {
    $events[SilktideEvent::EVENT_NAME][] = ['notify'];
    return $events;
  }

  /**
   * Notify Silktide of an event.
   *
   * @param \Drupal\silktide\event\SilktideEvent $event
   *   The event that was triggered.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function notify(SilktideEvent $event) {
    $settings = $this->configFactory->get('silktide.settings');
    $apiKey = $settings->get('apikey');

    if (32 !== strlen($apiKey)) {
      return;
    }

    $dataToSend = [
      'method' => 'post',
      'headers' => [
        'content-type' => 'application/x-www-form-urlencoded',
        'user-agent' => 'SilktideDrupal/' . self::VERSION . ' (compatible; Drupal/' . \Drupal::VERSION . ')',
      ],
      'blocking' => TRUE,
      'compress' => TRUE,
      'body' => http_build_query(
        [
          'apiKey' => $apiKey,
          'urls' => [
            $event->getUrl(),
          ],
        ]
      ),
    ];

    try {
      $response = $this->client->request(
        'post',
        self::SILKTIDE_URL,
        $dataToSend
      );
      $this->loggerFactory->get('silktide')->info(
        'Notified Silktide about @url - response @response',
        [
          '@url' => $event->getUrl(),
          '@response' => $response->getBody()->getContents(),
        ]
      );

    }
    catch (\Throwable $e) {
      $this->loggerFactory->get('silktide')->error(
        'Could not send notification to Silktide @message about @url - data @data',
        [
          '@message' => $e->getMessage(),
          '@url' => $event->getUrl(),
          '@data' => json_encode($dataToSend),
        ]
      );
    }
  }

}
