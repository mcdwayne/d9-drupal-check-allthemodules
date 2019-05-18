<?php

namespace Drupal\domain_lang\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirect subscriber for control language detection and selection pages.
 */
class DomainLangConfigRedirect implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The domain loader.
   *
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $domainLoader;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator service.
   * @param \Drupal\domain\DomainLoaderInterface $domain_loader
   *   The domain loader service.
   */
  public function __construct(DomainNegotiatorInterface $domain_negotiator, DomainLoaderInterface $domain_loader) {
    $this->domainNegotiator = $domain_negotiator;
    $this->domainLoader = $domain_loader;
  }

  /**
   * Check current request and redirect if needed.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Current request response event.
   */
  public function checkRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $domain = $this->domainNegotiator->getActiveDomain();


    switch ($request->get(RouteObjectInterface::ROUTE_NAME)) {

      case 'language.negotiation':
        drupal_set_message($this->t('You was redirected to current active domain language settings page.'));
        drupal_set_message($this->t('This page should be used for currently active domain language detection and selection setup.'));
        $this->setRedirectResponse($event, 'domain_lang.admin', $domain);
        break;

      case 'language.negotiation_session':
        $args = ['@type' => $this->t('Session language detection configuration')];
        drupal_set_message($this->t('You was redirected to current active domain @type page.', $args));
        drupal_set_message($this->t('This page should be used for currently active domain @type.', $args));
        $this->setRedirectResponse($event, 'domain_lang.negotiation_session', $domain);
        break;

      case 'language.negotiation_browser':
        $args = ['@type' => $this->t('Browser language detection configuration')];
        drupal_set_message($this->t('You was redirected to current active domain @type page.', $args));
        drupal_set_message($this->t('This page should be used for currently active domain @type.', $args));
        $this->setRedirectResponse($event, 'domain_lang.negotiation_browser', $domain);
        break;

      case 'language.negotiation_url':
        $args = ['@type' => $this->t('URL language detection configuration')];
        drupal_set_message($this->t('You was redirected to current active domain @type page.', $args));
        drupal_set_message($this->t('This page should be used for currently active domain @type.', $args));
        $this->setRedirectResponse($event, 'domain_lang.negotiation_url', $domain);
        break;

      case 'language.negotiation_selected':
        $args = ['@type' => $this->t('Selected language configuration')];
        drupal_set_message($this->t('You was redirected to current active domain @type page.', $args));
        drupal_set_message($this->t('This page should be used for currently active domain @type.', $args));
        $this->setRedirectResponse($event, 'domain_lang.negotiation_selected', $domain);
        break;
    }
  }

  /**
   * Sets TrustedRedirectResponse to redirect to related domain page.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request response event.
   * @param string $route
   *   The name of the route.
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain object.
   */
  protected function setRedirectResponse(GetResponseEvent $event, $route, DomainInterface $domain) {
    $event->setResponse(new TrustedRedirectResponse(Url::fromRoute(
      $route,
      ['domain' => $domain->id()],
      ['absolute' => TRUE])->toString())
    );
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkRequest');
    return $events;
  }

}
