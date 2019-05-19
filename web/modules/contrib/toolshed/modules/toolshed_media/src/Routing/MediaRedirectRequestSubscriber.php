<?php

namespace Drupal\toolshed_media\Routing;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\toolshed\Utility\FileHelper;

/**
 * Event subscriber handler which redirects Media requests to file.
 *
 * By default the media entity canonical display page, points to fully rendered
 * display page (entity display mode), but for most non-administrators, we
 * tend want to redirect to the file directly.
 */
class MediaRedirectRequestSubscriber implements EventSubscriberInterface {

  /**
   * Route used to determine how the request needs to be handled.
   *
   * @var Drupal\Core\Routing\RouteMatchInterface
   */
  protected $route;

  /**
   * The account to use when determining if the request should redirect.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Drupal service for getting information and handlers for entity types.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Generate a new MediaRedirectRequestSubscriber event listener.
   *
   * @param Drupal\Core\Routing\RouteMatchInterface $route
   *   The routing match interface to determine what route the request is
   *   acting on, and if it matches a media entity request.
   * @param Drupal\Core\Session\AccountInterface $account
   *   An account / session proxy representing the current user to evaluate
   *   the permissions and handling of the account retrieval.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal service for getting handlers for the various entity types.
   */
  public function __construct(RouteMatchInterface $route, AccountInterface $account, EntityTypeManagerInterface $entityTypeManager) {
    $this->route = $route;
    $this->account = $account;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'onHandleRequest',
    ];
  }

  /**
   * Respond to the Request, and redirect media pages if appropriate.
   *
   * Determine if the request is for the canonical media display page and
   * decide if the page needs to get redirected to the media source file
   * if the user access and media type settings call for it.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event information. This will contain the current kernel object and
   *   information about the request, since the event is responding to a
   *   KernelEvents::REQUEST event.
   */
  public function onHandleRequest(GetResponseEvent $event) {
    // Ensure that the current route is for a media entity display, and
    // get the media entity that is being requested.
    $media = $this->route->getRouteName() === 'entity.media.canonical' ? $this->route->getParameter('media') : NULL;

    if ($media && !$media->access('edit', $this->account)) {
      $bundleEntityType = $media->getEntityType()->getBundleEntityType();
      $mediaType = $this->entityTypeManager->getStorage($bundleEntityType)->load($media->bundle());

      if (!$mediaType) {
        return;
      }

      $displayBehaviors = $mediaType->getThirdPartySetting('toolshed_media', 'media_type_behaviors');

      if (!empty($displayBehaviors['redirect_to_file'])) {
        $fileHelper = FileHelper::fromEntity($media);
        $event->setResponse(new RedirectResponse($fileHelper->buildRawUrl(FALSE)));
      }
    }
  }

}
