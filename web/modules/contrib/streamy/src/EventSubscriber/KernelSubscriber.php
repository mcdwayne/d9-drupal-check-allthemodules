<?php

namespace Drupal\streamy\EventSubscriber;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\streamy\StreamyFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Subscribe to KernelEvents::REQUEST events and redirect if site is currently
 * in maintenance mode.
 */
class KernelSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\streamy\StreamyFactory
   */
  protected $streamyFactory;

  /**
   * @var \Drupal\Core\StreamWrapper\StreamWrapperInterface
   */
  protected $streamWrapperManager;

  /**
   * KernelSubscriber constructor.
   *
   * @param \Drupal\streamy\StreamyFactory                  $streamyFactory
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $streamWrapper
   */
  public function __construct(StreamyFactory $streamyFactory, StreamWrapperManager $streamWrapper) {
    $this->streamyFactory = $streamyFactory;
    $this->streamWrapperManager = $streamWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['unregisterInvalidStreamyWappers'];
    return $events;
  }

  /**
   * Looping through disabled streamy streams and fake
   * their referenced class in the StreamWrapperManager
   * to avoid any error if the above-mentioned stream gets called.
   *
   * @param GetResponseEvent $event
   */
  public function unregisterInvalidStreamyWappers(GetResponseEvent $event) {
    $streamySchemes = $this->streamyFactory->getSchemes();
    foreach ($streamySchemes as $scheme) {
      $wrapper = $this->streamyFactory->getFilesystem($scheme);
      if ($wrapper && $wrapper->isDisabled()) {
        $currentWrappers = stream_get_wrappers();
        if (in_array($scheme, $currentWrappers)) {

          // The dummy StreamWrapper
          $class = 'Drupal\streamy\StreamWrapper\DummyStreamWrapper';

          // Faking the info of the current $scheme
          $this->streamWrapperManager->addStreamWrapper("streamy.{$scheme}.stream_wrapper", $class, $scheme);

          // Here we fake the StreamWrapperManager by changing the class of the already registered
          // but invalid $scheme with a class that returns HIDDEN as Stream Type
          $this->streamWrapperManager->registerWrapper($scheme, $class, StreamWrapperInterface::WRITE_VISIBLE);
        }
      }
    }
  }
}
