<?php

namespace Drupal\death_link\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\death_link\Service\RedirectService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class PageNotFoundEventSubscriber.
 *
 * @package Drupal\death_link\EventSubscriber
 */
class PageNotFoundEventSubscriber implements EventSubscriberInterface {

  /**
   * The redirect service.
   *
   * @var \Drupal\death_link\Service\RedirectService
   */
  protected $redirectService;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * PageNotFoundEventSubscriber constructor.
   *
   * @param \Drupal\death_link\Service\RedirectService $redirectService
   *   The group content service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(RedirectService $redirectService, EntityTypeManagerInterface $entityManager) {
    $this->redirectService = $redirectService;
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return ([
      KernelEvents::EXCEPTION => [
        ['redirect'],
      ],
    ]);
  }

  /**
   * Perform redirect on 404.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The exception event.
   */
  public function redirect(GetResponseForExceptionEvent $event) {

    // Make sure we get a "not found" exception.
    $exception = $event->getException();
    if (!$exception || !$exception instanceof NotFoundHttpException) {
      return;
    }

    // Make sure the status code is "404".
    $statusCode = $exception->getStatusCode();
    if ($statusCode !== 404) {
      return;
    }

    // Get the current request uri and call
    // the service to find a redirect match.
    $requestUri = $event->getRequest()->getRequestUri();
    $matchingDeathLinks = $this->redirectService->getMatchingRedirect($requestUri);

    // Make sure we only continue when we found matches.
    if (!$matchingDeathLinks) {
      return;
    }

    // If there are multiple matches, pick the first suitable one.
    $redirectUrl = NULL;
    foreach ($matchingDeathLinks as $matchingDeathLink) {

      // Try to load the DeathLink based on the found ID.
      /** @var \Drupal\death_link\Entity\DeathLinkInterface $deathLink */
      $deathLink = $this->entityManager->getStorage('death_link')->load($matchingDeathLink);
      if (!$deathLink) {
        continue;
      }

      // Try to get a valid url.
      if (!$deathLink->getToEntity()) {
        continue;
      }

      $redirectUrl = $deathLink->getToEntity()->toUrl();
    }

    if (!$redirectUrl) {
      return;
    }

    // If there are no matching urls, this won't be executed
    // and we show the 404 anyway.
    /** @var \Drupal\Core\Url $redirectUrl */
    $event->setResponse(new TrustedRedirectResponse($redirectUrl->toString(), 301));
  }

}
