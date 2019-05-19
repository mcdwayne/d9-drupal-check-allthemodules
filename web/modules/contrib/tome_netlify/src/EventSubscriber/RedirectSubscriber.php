<?php

namespace Drupal\tome_netlify\EventSubscriber;

use Drupal\tome_static\StaticGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Writes to the _redirects file when redirects are generated.
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * The static generator.
   *
   * @var \Drupal\tome_static\StaticGeneratorInterface
   */
  protected $staticGenerator;

  /**
   * Constructs a RedirectSubscriber object.
   *
   * @param \Drupal\tome_static\StaticGeneratorInterface $static_generator
   *   The static generator.
   */
  public function __construct(StaticGeneratorInterface $static_generator) {
    $this->staticGenerator = $static_generator;
  }

  /**
   * Reacts to a response event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $request = $event->getRequest();
    if ($request->attributes->has(StaticGeneratorInterface::REQUEST_KEY) && $response instanceof RedirectResponse) {
      $base_dir = $this->staticGenerator->getStaticDirectory();
      file_prepare_directory($base_dir, FILE_CREATE_DIRECTORY);
      file_put_contents("$base_dir/_redirects", $request->getPathInfo() . ' ' . $response->getTargetUrl() . "\n", FILE_APPEND);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

}
