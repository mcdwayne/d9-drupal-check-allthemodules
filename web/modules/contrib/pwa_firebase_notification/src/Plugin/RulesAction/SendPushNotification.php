<?php

namespace Drupal\pwa_firebase_notification\Plugin\RulesAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\pwa_firebase_notification\Controller\NotificationController;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a Send Push Notification action.
 *
 * @RulesAction(
 *   id = "rules_send_push",
 *   label = @Translation("Send Push Notification"),
 *   category = @Translation("Entity"),
 *   context = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity.")
 *     ),
 *     "title" = @ContextDefinition("string",
 *       label = @Translation("Title")
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message")
 *     ),
 *   }
 * )
 */
class SendPushNotification extends RulesActionBase {

  /**
   * Sends Push Notification.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Target node.
   * @param string $title
   *   Message title.
   * @param string $message
   *   Message body.
   */
  protected function doExecute(EntityInterface $entity, $title, $message) {
    $url = $entity->toUrl()->setAbsolute()->toString();
    NotificationController::sendMessageToAllUsers($title, $message, $url);
  }

}