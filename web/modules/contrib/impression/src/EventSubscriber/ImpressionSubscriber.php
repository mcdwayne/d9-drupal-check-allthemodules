<?php /**
 * @file
 * Contains \Drupal\impression\EventSubscriber\BootSubscriber.
 */

namespace Drupal\impression\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ImpressionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  public function onEvent(\Symfony\Component\HttpKernel\Event\GetResponseEvent $event) {
    $position = stripos($_SERVER['REQUEST_URI'], '/impression');
    $user = \Drupal::currentUser();
    if (isset($user->uid)) {
      $uid = $user->uid;
    }
    else {
      $uid = 0;
    }
    if ($position === FALSE || $position != 0) {
      $impression = entity_create('impression_base');
      $impression->domain = $_SERVER['HTTP_HOST'];
      $impression->uri = $_SERVER['REQUEST_URI'];
      $impression->ip = \Drupal::request()->getClientIp();
      $impression->hi = '';
      $impression->ref = $_SERVER['HTTP_REFERER'];
      $impression->action = 'pageload';
      $impression->save();
    }
  }

}
