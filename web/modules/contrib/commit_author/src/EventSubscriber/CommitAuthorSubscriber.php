<?php
/**
 * @file
 * Contains Drupal\commit_author\EventSubscriber\CommitAuthorSubscriber
 */
namespace Drupal\commit_author\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commit_author\CommitAuthorMain;


class CommitAuthorSubscriber extends CommitAuthorMain implements EventSubscriberInterface {
  public function setErrorHandler(GetResponseEvent $event) {
    set_error_handler(array($this, 'error_handler'));
  }

  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('setErrorHandler');

    return $events;
  }
}
