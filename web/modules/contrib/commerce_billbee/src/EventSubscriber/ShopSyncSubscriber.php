<?php

namespace Drupal\commerce_billbee\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Notifies Billbee that new data is available for synchronisation.
 */
class ShopSyncSubscriber implements EventSubscriberInterface {

  /**
   * Notifies Billbee data is ready for synchronisation.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function triggerBillbeeShopSync(WorkflowTransitionEvent $event) {
    $settings = \Drupal::config('commerce_billbee.settings');
    $shop_id = $settings->get('shop_id');
    if ($shop_id) {
      $url = 'https://app01.billbee.de/Sync/TriggerShopSync/' . $shop_id;
      $client = new Client();
      $response = $client->get($url);

      if ($settings->get('enable_logging')) {
        \Drupal::logger('commerce_billbee')
          ->notice("Shop sync requested at %url.", ['%url' => $url,]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.post_transition' => [
        'triggerBillbeeShopSync',
        -100,
      ],
    ];
    return $events;
  }

}
