<?php

namespace Drupal\urban_airship_web_push_notifications\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\urban_airship_web_push_notifications\PushApi;

/**
 * Provides a 'Send notification (Urban Airship Web Notify)' action.
 *
 * @RulesAction(
 *   id = "urban_airship_web_push_notifications",
 *   label = @Translation("Send notification (Web Notify)"),
 *   category = @Translation("Urban Airship"),
 *   context = {
 *     "notification" = @ContextDefinition("string",
 *       label = @Translation("Notification")
 *     )
 *   }
 * )
 *
 */
class UrbanAirshipWebNotify extends RulesActionBase {

  /**
   * Send Urban Airship notification
   *
   * @param string $notification
   *   Notification message to send
   */
  protected function doExecute($notification) {
    (new PushApi())
      ->setAudience('all')
      ->setDeviceTypes('web')
      ->setNotification($notification)
      ->send();
  }

}
