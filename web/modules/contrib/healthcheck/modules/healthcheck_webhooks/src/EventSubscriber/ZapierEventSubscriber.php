<?php

namespace Drupal\healthcheck_webhooks\EventSubscriber;

use Drupal\healthcheck\Event\HealthcheckCriticalEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\healthcheck\Event\HealthcheckEvents;
use Drupal\healthcheck\Finding\FindingStatus;

/**
 * Provides an integration between Healthcheck Events and a custom-built Zapier webhook.
 */
class ZapierEventSubscriber implements EventSubscriberInterface {

  /**
   * Post notification of critical Healthcheck events to Zapier and log them.
   */
  public function postToZapier(HealthcheckCriticalEvent $event) {
    /** @var LoggerInterface $logger */
    $logger = \Drupal::logger('healthcheck_webhooks');

    // Get the critical findings only and iterate over them.
    $critical_findings = $event->getReport()->getFindingsByStatus()[FindingStatus::CRITICAL];
    foreach ($critical_findings as $finding) {

      // Prep the data for sending the request.
      $check = $finding->getCheck();
      $post_data = (object) [
        'key' => $finding->getKey(),
        'label' => $check->label(),
        'message' => $finding->getMessage()->__toString(),
      ];

      // Get the Zapier URL from config.
      $zapier_url = \Drupal::config('healthcheck_webhooks.settings')->get('zapier');

      // Send POST to Zapier using httpClient and log to DB.
      if ($zapier_url != '') {
        $client = \Drupal::httpClient();
        $request = $client->post($zapier_url, [
          'json' => json_encode($post_data),
        ]);
        $response = json_decode($request->getBody());
        $logger->info('Healthcheck found a critical event and posted to Zapier: ' . $finding->getKey());
        $logger->info('Zapier response after posting critical event: ' . '<pre>' . print_r($response, TRUE) . '</pre>');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    return [
      HealthcheckEvents::CHECK_CRITICAL => [
        'postToZapier',
      ],
    ];
  }
}
