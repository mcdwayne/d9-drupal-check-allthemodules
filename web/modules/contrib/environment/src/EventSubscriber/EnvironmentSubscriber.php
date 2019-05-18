<?php
namespace Drupal\environment\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnvironmentSubscriber implements EventSubscriberInterface {

  public function checkForEnvironmentSwitch(GetResponseEvent $event) {
    $env_req_override = \Drupal::config('environment.settings')->get('environment_require_override');
    if ($env_req_override) {
      $env_override = \Drupal::config('environment.settings')->get('environment_override');
      if (!empty($env_override)) {
        $current_env = environment_current(FALSE);
        if ($current_env != $env_override) {
          environment_switch($env_override, TRUE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForEnvironmentSwitch');
    return $events;
  }

}
?>