<?php

namespace Drupal\brightcove\Plugin\QueueWorker;

use Brightcove\API\Exception\APIException;
use Drupal\brightcove\BrightcoveUtil;
use Drupal\brightcove\Entity\BrightcoveSubscription;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes Entity Sync Tasks for Subscription.
 *
 * @QueueWorker(
 *   id = "brightcove_subscription_delete_queue_worker",
 *   title = @Translation("Brightcove subscription queue worker."),
 *   cron = { "time" = 30 }
 * )
 */
class BrightcoveSubscriptionDeleteQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var array $data */
    if (!empty($data['local_only'])) {
      $brightcove_subscription = BrightcoveSubscription::loadByBcSid($data['subscription_id']);
      if (!empty($brightcove_subscription)) {
        $brightcove_subscription->delete(TRUE);
      }
    }
    else {
      // Check the Subscription if it is available on Brightcove or not.
      try {
        $cms = BrightcoveUtil::getCmsApi($data['api_client_id']);
        $cms->getSubscription($data['subscription_id']);
      }
      catch (APIException $e) {
        // If we got a not found response, delete the local version of the
        // subscription.
        if ($e->getCode() == 404) {
          /** @var \Drupal\brightcove\Entity\BrightcoveSubscription $subscription */
          $brightcove_subscription = BrightcoveSubscription::loadByBcSid($data['subscription_id']);

          if (!empty($brightcove_subscription)) {
            // In case of a default subscription, unset the entity's
            // association with the Brightcove entity, but keep a local entity
            // in Drupal without the Brightcove ID and set its status to
            // disabled.
            if ($brightcove_subscription->isDefault()) {
              $brightcove_subscription->setBcSid(NULL);
              $brightcove_subscription->setStatus(FALSE);
              $brightcove_subscription->save();
            }
            else {
              $brightcove_subscription->delete(TRUE);
            }
          }
        }
        elseif ($e->getCode() == 401) {
          watchdog_exception('brightcove', $e, 'Access denied for Notification.', [], RfcLogLevel::WARNING);
        }
        // Otherwise throw the same exception again.
        else {
          throw $e;
        }
      }
    }
  }

}
