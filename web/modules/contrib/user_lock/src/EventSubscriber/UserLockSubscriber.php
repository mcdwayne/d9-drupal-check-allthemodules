<?php

namespace Drupal\user_lock\EventSubscriber;

use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserLockSubscriber implements EventSubscriberInterface {

  public function checkForRedirection(GetResponseEvent $event) {
    if ($event->getRequest()->query->get('id')) {
      $time = date('Y-m-d h:i:s a', time());
      $str_time = strtotime($time);
      $id = $event->getRequest()->query->get('id');
      $entities_load = \Drupal::entityTypeManager()->getStorage('user_lock_config_entity')->loadByProperties(['id'=>$id]);
      if($entities_load) {
        $message = $entities_load[$id]->get_lock_message();
        if($entities_load[$id]->get_lock_period_from()){
          $default_lock_from = DrupalDateTime::createFromTimestamp($entities_load[$id]->get_lock_period_from());
          $lock_from = $default_lock_from->format('Y-m-d h:i:s a');
          $str_lock_from = @strtotime($default_lock_from->format('Y-m-d h:i:s a'));
        }
        if($entities_load[$id]->get_lock_period_to()){
          $default_lock_to = DrupalDateTime::createFromTimestamp($entities_load[$id]->get_lock_period_to());
          $lock_to = $default_lock_to->format('Y-m-d h:i:s a');
          $str_lock_to = @strtotime($default_lock_to->format('Y-m-d h:i:s a'));
        }
        if(($str_time >= $str_lock_from) && ($str_time <= $str_lock_to)){
          drupal_set_message($message, 'warning');
          drupal_set_message(t('You have been locked from '. $lock_from .' to '.$lock_to), 'warning');
        }
      }
    }
  }

 /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }

}
