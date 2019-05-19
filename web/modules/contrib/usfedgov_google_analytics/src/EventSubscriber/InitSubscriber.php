<?php /**
 * @file
 * Contains \Drupal\usfedgov_google_analytics\EventSubscriber\InitSubscriber.
 */

namespace Drupal\usfedgov_google_analytics\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  public function onEvent() {

  }

}
