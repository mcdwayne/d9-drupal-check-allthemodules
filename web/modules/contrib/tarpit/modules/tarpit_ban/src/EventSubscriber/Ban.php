<?php

namespace Drupal\tarpit_ban\EventSubscriber;

use Drupal\ban\BanIpManager;
use Drupal\Core\Logger\LoggerChannel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Ban implements EventSubscriberInterface {

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack;
   */
  protected $request_stack;

  /**
   * @var \Drupal\ban\BanIpManager
   */
  protected $ipmanager;

  /**
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack, BanIpManager $ipmanager, LoggerChannel $logger) {
    $this->request_stack = $request_stack;
    $this->ipmanager = $ipmanager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['tarpit_ban.reaction'][] = array('ban', 0);
    return $events;
  }

  /**
   * Custom callback.
   *
   * @param $event \Drupal\tarpit_ban\Event\ReactionEvent
   *   The event.
   */
  public function ban($event) {
    $request = $this->request_stack->getCurrentRequest();
    $ip = $request->getClientIP();
    $this->ipmanager->banIp($ip);
    $this->logger->warning('IP ' . $ip . ' has been blocked.');
  }

}
