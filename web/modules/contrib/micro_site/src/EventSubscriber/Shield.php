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
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class SiteRedirectSubscriber.
 *
 * @package Drupal\micro_site
 */
class Shield implements EventSubscriberInterface {

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructor.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   */
  public function __construct(SiteNegotiatorInterface $site_negotiator) {
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('ShieldLoad', 300);
    return $events;
  }

  /**
   * // only if KernelEvents::REQUEST !!!
   * @see \Symfony\Component\HttpKernel\KernelEvents for details
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function ShieldLoad(GetResponseEvent $event) {
    // allow Drush to bypass Shield
    if (PHP_SAPI === 'cli') {
      return;
    }

    $active_site = $this->negotiator->getActiveSite();
    if (!$active_site instanceof SiteInterface) {
      return;
    }

    $shield_enabled = $active_site->getSiteShield();
    if (empty($shield_enabled)) {
      return;
    }

    $user = $active_site->getSiteShieldUser();
    $pass = $active_site->getSiteShieldPassword();

    if (!empty($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])
      && $_SERVER['PHP_AUTH_USER'] == $user
      && $_SERVER['PHP_AUTH_PW']   == $pass) {
      return;
    }

    $response = new Response();
    $response->headers->add([
      'WWW-Authenticate' => 'Basic realm="' . strtr('Please authenticate', [
          '[user]' => $user,
          '[pass]' => $pass,
        ]) . '"',
    ]);
    $response->setStatusCode(401);
    $event->setResponse($response);


//    header(sprintf('WWW-Authenticate: Basic realm="%s"', strtr('Site protected', array('[user]' => $user, '[pass]' => $pass))));
//    header('HTTP/1.0 401 Unauthorized');
//    exit;
  }

}
