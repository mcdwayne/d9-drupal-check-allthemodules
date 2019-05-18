<?php

namespace Drupal\micro_site\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\Routing\RequestContext as SymfonyRequestContext;
use Drupal\Core\Routing\RequestContext;


/**
 * Class SiteRedirectSubscriber.
 *
 * @package Drupal\micro_site
 */
class SiteRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \\Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   */
  public function __construct(AccountInterface $current_user, UrlGeneratorInterface $url_generator, EntityTypeManagerInterface $entity_type_manager, SiteNegotiatorInterface $site_negotiator) {
    $this->currentUser = $current_user;
    $this->urlGenerator = $url_generator;
    $this->entityTypeManager = $entity_type_manager;
    $this->negotiator = $site_negotiator;
    $this->context = new RequestContext();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = array('onKernelRequestSite', 50);
    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is dispatched.
   *
   * @param GetResponseEvent $event
   *   The event object.
   */
  public function onKernelRequestSite(GetResponseEvent $event) {
    $request = $event->getRequest();
    $exception = $request->get('exception');
    // If we've got an exception, nothing to do here.
    if ($request->get('exception') != NULL) {
      return;
    }

    if ($active_site = $this->negotiator->getActiveSite()) {
      return;
    }

    $path = $request->getPathInfo();
    if (preg_match('/^\/site\/([0-9]+)$/i', $path, $matches)) {
      $site_id = $matches['1'];
      $site = $this->negotiator->loadById($site_id);
      $active_site = $this->negotiator->getActiveSite();

      if ($site  && $site instanceof SiteInterface && empty($active_site)) {
        if ($site->isPublished() || $site->isRegistered()) {
          $site_url = $site->getSitePath();
          $target = Url::fromUri($site_url)->toString();
          $new_response = new TrustedRedirectResponse($target, '301');
          $event->setResponse($new_response);
        }
      }
    }



  }

}
