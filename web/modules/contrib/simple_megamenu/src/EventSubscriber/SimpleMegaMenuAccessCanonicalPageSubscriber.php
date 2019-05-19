<?php

namespace Drupal\simple_megamenu\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class SimpleMegaMenuAccessCanonicalPageSubscriber.
 *
 * @package Drupal\simple_megamenu
 */
class SimpleMegaMenuAccessCanonicalPageSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(AccountInterface $current_user, UrlGeneratorInterface $url_generator) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['onRequestCheckAccess'];
    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is dispatched.
   *
   * @param GetResponseEvent $event
   *   The event object.
   */
  public function onRequestCheckAccess(GetResponseEvent $event) {
    $request = $event->getRequest();

    // If we've got an exception, nothing to do here.
    if ($request->get('exception') != NULL) {
      return;
    }

    /** @var \Drupal\simple_megamenu\Entity\SimpleMegaMenu $simple_mega_menu */
    $simple_mega_menu = $request->get('simple_mega_menu');
    if ($simple_mega_menu  && $simple_mega_menu instanceof SimpleMegaMenuInterface) {
      if (!$this->currentUser->hasPermission('access simple mega menu entities canonical page')) {
        throw new NotFoundHttpException();
      }
    }
  }

}
