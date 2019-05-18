<?php

namespace Drupal\devel_debug_40x\EventSubscriber;

use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Dumps 403 or 404 exceptions through Devel's dumper manager.
 */
class DevelDebug40xExceptionSubscriber implements EventSubscriberInterface {

  /**
   * The Devel dumper manager.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $develDumperManager;

  /**
   * Constructs a new DevelDebug40xExceptionSubscriber instance.
   *
   * @param \Drupal\devel\DevelDumperManagerInterface $devel_dumper_manager
   *   The Devel dumper manager.
   */
  public function __construct(DevelDumperManagerInterface $devel_dumper_manager) {
    $this->develDumperManager = $devel_dumper_manager;
  }

  /**
   * Dumps 403 or 404 exceptions through Devel's dumper manager.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();

    if ($exception instanceof HttpExceptionInterface) {
      if ($exception->getStatusCode() == 403) {
        $this->develDumperManager->message($exception);
      }
      if ($exception->getStatusCode() == 404) {
        $this->develDumperManager->message($exception);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', 50];
    return $events;
  }

}
