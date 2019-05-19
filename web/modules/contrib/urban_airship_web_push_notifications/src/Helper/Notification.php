<?php

namespace Drupal\urban_airship_web_push_notifications\Helper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\urban_airship_web_push_notifications\PushApi;
use Drupal\urban_airship_web_push_notifications\SchedulesApi;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Send notifications.
 */
class Notification {

  /**
   * Replace tokens and send message.
   */
  public function send(EntityInterface $entity) {
    if (\Drupal::currentUser()->hasPermission('send urban airship web push notifications')) {
      $config = \Drupal::config('urban_airship_web_push_notifications.configuration');
      if (empty($config->get('app_key')) || empty($config->get('app_master_secret'))) {
        \Drupal::logger('urban_airship_web_push_notifications')->warning('Invalid credentials');
        return;
      }
      if (!empty($entity->urban_airship_web_push_notifications)) {
        $body = $entity->urban_airship_web_push_notifications_text;
        $scheduled = $entity->urban_airship_web_push_notifications_scheduled;
        if (\Drupal::moduleHandler()->moduleExists('token')) {
          $token_service = \Drupal::token();
          $body = $token_service->replace($body, ['node' => $entity]);
        }
        $options = ['absolute' => TRUE];
        $action_url = Url::fromRoute('entity.node.canonical', ['node' => $entity->id()], $options);
        $notification = [
          'title'       => html_entity_decode($entity->urban_airship_web_push_notifications_title, ENT_QUOTES),
          'body'        => !empty($body) ? html_entity_decode($body, ENT_QUOTES) : $entity->label(),
          'icon'        => !empty($entity->urban_airship_web_push_notifications_icon) ? $entity->urban_airship_web_push_notifications_icon : '',
          'url'         => !empty($entity->urban_airship_web_push_notifications_action_url) ? $entity->urban_airship_web_push_notifications_action_url : $action_url->toString(),
          'interaction' => !empty($entity->urban_airship_web_push_notifications_interaction),
          'scheduled'   => !empty($scheduled) ? \Drupal::service('date.formatter')->format($scheduled->getTimestamp(), 'custom', 'Y-m-d\TH:i:s') : '',
        ];
        // Other modules can override notification messages if needed.
        \Drupal::moduleHandler()->alter('urban_airship_web_push_notifications', $notification, $entity);
        $this->logNotification($notification, $entity);
        $push = (new PushApi())
          ->setAudience('all')
          ->setDeviceTypes('web')
          ->setNotification($notification);
        if ($entity->urban_airship_web_push_notifications == 1) {
          // Instant notifications.
          $push->send();
        }
        else {
          // Schedule notifications.
          (new SchedulesApi())
            ->setName($entity->label())
            ->setLocalScheduledTime($notification['scheduled'])
            ->setPush($push->getData())
            ->schedule();
        }
      }
    }
  }

  /**
   * Track notification.
   */
  protected function logNotification($notification, $entity) {
    $username = '';
    if ($user = User::load(\Drupal::currentUser()->id())) {
      $username = $user->getUsername();
    }
    db_insert('urban_airship_web_push_notifications')
      ->fields([
        'username'                => $username,
        'nid'                     => $entity->id(),
        'notification_title'      => $notification['title'],
        'notification_text'       => $notification['body'],
        'notification_icon'       => $notification['icon'],
        'notification_action_url' => $notification['url'],
        'sent_at'                 => time(),
      ])
      ->execute();
  }

}
