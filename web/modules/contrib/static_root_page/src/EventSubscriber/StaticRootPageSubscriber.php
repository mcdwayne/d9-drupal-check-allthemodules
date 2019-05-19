<?php

namespace Drupal\static_root_page\EventSubscriber;

use Drupal\Core\Render\Renderer;
use Drupal\language\LanguageNegotiatorInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a StaticRootPageSubscriber.
 */
class StaticRootPageSubscriber implements EventSubscriberInterface {

  /**
   * The event.
   *
   * @var \Symfony\Component\HttpKernel\Event\FilterResponseEvent
   */
  protected $event;

  /**
   * The language negotiator.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * The language path processor.
   *
   * @var \Drupal\language\HttpKernel\PathProcessorLanguage
   */
  protected $pathProcessorLanguage;

  /**
   * The variable containing the request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The variable containing the renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\language\LanguageNegotiatorInterface $language_negotiator
   *   The language negotiator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The language selection page condition plugin manager.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(LanguageNegotiatorInterface $language_negotiator, RequestStack $requestStack, Renderer $renderer) {
    $this->languageNegotiator = $language_negotiator;
    $this->requestStack = $requestStack;
    $this->renderer = $renderer;
  }

  /**
   * Event callback.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event object.
   *
   * @return bool
   *   Returns FALSE.
   */
  public function redirectToStaticRootPage(FilterResponseEvent $event) {
    $this->event = $event;
    if ($this->languageNegotiator->isNegotiationMethodEnabled('static-root-page') &&
    $this->requestStack->getCurrentRequest()->getRequestUri() === $this->requestStack->getCurrentRequest()->getBaseUrl() . '/') {
      $build = [
        'page' => [
          '#theme' => 'static_root_page',
          '#content' => [],
        ],
      ];
      $html = $this->renderer->renderRoot($build);
      $response = new Response();
      $response->setContent($html);
      $event->setResponse($response);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // You can set the order of execution of this event callback in the array.
    // Find the order of execution by doing this in the Drupal Root:
    // grep "$events[KernelEvents::RESPONSE][]" . -R | grep -v 'Test'
    // The value is currently set to -50, feel free to adjust if needed.
    $events[KernelEvents::RESPONSE][] = ['redirectToStaticRootPage', -50];
    return $events;
  }

}
