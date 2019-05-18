<?php

namespace Drupal\log_entity;

use Drupal\log_entity\Entity\LogEntity;
use Drupal\log_entity\Event\LogEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The event logger service handles persisting logged events.
 */
class EventLogger implements EventLoggerInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * EventLogger constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function logEvent(LogEvent $event) {
    $entry = LogEntity::create($event->toArray() + $this->getGlobalInfo());
    $entry->save();
  }

  /**
   * Gets the global information for each log.
   *
   * @return array
   *   An array of info to store with all events.
   */
  protected function getGlobalInfo() {
    return [
      'timestamp' => time(),
      'ip_address' => $this->getIpAddress(),
    ];
  }

  /**
   * Gets the IP.
   *
   * @return string
   *   The client IP address.
   */
  protected function getIpAddress() {
    // @TODO, check if this works behind cloudfront?
    return $this->request->getClientIp();
  }

}
