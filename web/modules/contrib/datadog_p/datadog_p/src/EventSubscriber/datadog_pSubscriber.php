<?php
namespace Drupal\datadog_p\EventSubscriber;
require DRUPAL_ROOT . '/libraries/datadog/src/DogStatsd.php';
require DRUPAL_ROOT . '/libraries/datadog/src/BatchedDogStatsd.php';   
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Component\Utility\Unicode;

use DataDog\DogStatsd;
use DataDog\BatchedDogStatsd;


 
class datadog_pSubscriber implements EventSubscriberInterface {  
  /**
  * {@inheritdoc}
  */
  function cica(){
    drupal_set_message("cica");
  }
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['pageLoad', 100];
    return $events;
  }
  public function pageLoad($event){
    $statsd = new DogStatsd();
    $config = \Drupal::config('datadog_p.adminsettings'); 
    $url = $config->get('datadog_settings');
    $statsd->increment($url);
  }
}