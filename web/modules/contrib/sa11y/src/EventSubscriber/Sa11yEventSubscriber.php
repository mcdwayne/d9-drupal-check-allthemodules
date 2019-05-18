<?php

namespace Drupal\sa11y\EventSubscriber;

use Drupal\sa11y\Event\Sa11yCompletedEvent;
use Drupal\sa11y\Event\Sa11yEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Sa11yEventSubscriber.
 *
 * @package Drupal\sa11y
 */
class Sa11yEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[Sa11yEvents::COMPLETED][] = ['onCompletedReport', 50];
    return $events;
  }

  /**
   * Notifies Users of completed reports.
   *
   * @param \Drupal\sa11y\Event\Sa11yCompletedEvent $event
   *   The event.
   */
  public function onCompletedReport(Sa11yCompletedEvent $event) {
    $sa11y_config = \Drupal::config('sa11y.settings');

    $notify_list = $sa11y_config->get('emails');
    if (!empty($notify_list)) {
      $default_langcode = \Drupal::languageManager()
        ->getDefaultLanguage()
        ->getId();

      foreach ($notify_list as $target) {
        if ($target_user = user_load_by_mail($target)) {
          $target_langcode = $target_user->getPreferredLangcode();
        }
        else {
          $target_langcode = $default_langcode;
        }

        // Send the message.
        \Drupal::service('plugin.manager.mail')
          ->mail('sa11y', 'notify', $target, $target_langcode, ['report_id' => $event->getId()]);
      }
    }
  }

}
