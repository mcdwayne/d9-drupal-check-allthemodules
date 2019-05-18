<?php

/**
 * @file
 * Contains \Drupal\hawk_auth_qr\EventSubscriber\HawkAuthQREventSubscriber.
 */

namespace Drupal\hawk_auth_qr\EventSubscriber;

use Drupal\Core\Url;
use Drupal\hawk_auth\HawkAuthCredentialsViewEvent;
use Drupal\hawk_auth\HawkAuthEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to event while viewing credentials to display QR code.
 */
class HawkAuthQrEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[HawkAuthEvents::VIEW_CREDENTIALS] = ['onViewCredentials', 0];
    return $events;
  }

  /**
   * Responds to HawkAuthEvents::VIEW_CREDENTIALS.
   * Adds QR Code link to each credential.
   *
   * @param HawkAuthCredentialsViewEvent $event
   *   The event being fired.
   */
  public function onViewCredentials(HawkAuthCredentialsViewEvent $event) {
    $list = $event->getBuild();
    foreach ($list['credentials']['#rows'] as $id => &$row) {
      $row['operations']['data']['#links'] = array_merge([
        'qr' => [
          'title' => t('View QR'),
          'url' => Url::fromRoute('hawk_auth_qr.view', ['hawk_credential' => $id]),
        ]
      ], $row['operations']['data']['#links']);
    }
    $event->setBuild($list);
  }

}
