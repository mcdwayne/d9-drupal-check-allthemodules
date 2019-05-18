<?php

/**
 * @file
 * Contains \Drupal\freegeoip_views\FreegeoipSubscriber.
 */

namespace Drupal\freegeoip_views\EventSubscriber;

use Drupal\freegeoip_views\FreegeoipGetValue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FreegeoipSubscriber.
 *
 * @package Drupal\freegeoip_views
 */
class FreegeoipSubscriber implements EventSubscriberInterface {


  protected $freegeoipvalue;
  /**
   * Constructor.
   */
  public function __construct(FreegeoipGetValue $freegeoipvalueObj) {
    $this->freegeoipvalue = $freegeoipvalueObj;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.request'] = ['seFreegeoip'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function seFreegeoip(Event $event) {
    if((\Drupal::currentUser()->isAuthenticated()) && !isset($_SESSION['freegeoip'])) {
      $_SESSION['freegeoip'] = $this->freegeoipvalue->getFreegeoipValue();
    }
  }

}
