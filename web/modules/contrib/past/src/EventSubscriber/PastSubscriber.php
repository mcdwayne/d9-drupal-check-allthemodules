<?php

namespace Drupal\past\EventSubscriber;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An EventSubscriber that listens to exceptions and shutdowns in order to log
 * them.
 */
class PastSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onKernelException'];
    $events[KernelEvents::REQUEST][] = ['registerShutdownFunction'];
    return $events;
  }

  /**
   * Registers _past_shutdown_function as shutdown function.
   *
   * @param GetResponseEvent $event
   *   Is given by the event dispatcher.
   */
  public function registerShutdownFunction(GetResponseEvent $event) {
    drupal_register_shutdown_function('_past_shutdown_function');
  }

  /**
   * Logs an exception with the Past backend.
   * 
   * @param GetResponseForExceptionEvent $event
   *   Is given by the event dispatcher.
   */
  public function onKernelException(GetResponseForExceptionEvent $event) {
    if (!\Drupal::config('past.settings')->get('exception_handling')) {
      return;
    }
    // Do not log 404 and 403 exceptions.
    if ($event->getException() instanceof HttpException) {
      return;
    }
    try{
      $past_event = past_event_create('past', 'unhandled_exception', $event->getException()->getMessage());
      $past_event->addArgument('exception', $event->getException());
      $past_event->setSeverity(RfcLogLevel::ERROR);
      $past_event->save();
    }
    catch (\Exception $exception2) {
      // Another uncaught exception was thrown while handling the first one.
      // If we are displaying errors, then do so with no possibility of a
      // further uncaught exception being thrown.
      if (error_displayable()) {
        print '<h1>Additional uncaught exception thrown while handling exception.</h1>';
        print '<h2>Original</h2><p>' . Error::renderExceptionSafe($event->getException()) . '</p>';
        print '<h2>Additional</h2><p>' . Error::renderExceptionSafe($exception2) . '</p><hr />';
      }
    }
  }

}
