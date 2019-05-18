<?php

/**
 * @file
 * Contains \Drupal\domain_redirect\EventSubscriber\RedirectRequestSubscriber.
 */

namespace Drupal\domain_redirect\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Domain redirect subscriber for controller requests.
 */
class DomainRedirectRequestSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a \Drupal\domain_redirect\EventSubscriber\DomainRedirectRequestSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factor
   *   The entity query service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, QueryFactory $query_factory, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->entityQueryFactory = $query_factory;
    $this->config = $config_factory->get('domain_redirect.settings');
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['redirect'];
    return $events;
  }

  /**
   * Perform the domain redirect, if needed.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function redirect(GetResponseEvent $event) {
    // Check if a destination domain was configured.
    if ($destinationDomain = $this->config->get('destination_domain')) {
      // Get the current domain of this request.
      if ($domain = $this->getDomain()) {
        // Check if a redirect exists for this domain.
        if ($redirect = $this->getDomainRedirect($domain)) {
          // Determine the destination by swapping the domain for the
          // configured destination domain.
          $destination = str_replace($domain, $destinationDomain, Url::fromUri($redirect->getDestination()['uri'], ['absolute' => TRUE])->toString());

          // Set the redirect with a cache tag for the domain redirect entity.
          $response = new TrustedRedirectResponse($destination, 301);
          $response->addCacheableDependency($redirect);
          $event->setResponse($response);
        }
      }
    }
  }

  /**
   * Get the domain for the current request, if one.
   *
   * @return mixed
   *   The domain for the current request, or NULL if there is not one.
   */
  public function getDomain() {
    // Determine the current absolute URL of this request.
    $url = Url::fromRoute('<current>', [], ['absolute' => TRUE])->toString();

    // Parse the URL.
    $parsed = parse_url($url);

    // Extract the host.
    return !empty($parsed['host']) ? $parsed['host'] : NULL;
  }

  /**
   * Get the domain redirect based on a given domain.
   *
   * @param string $domain
   *   The domain.
   * @return null|\Drupal\domain_redirect\Entity\DomainRedirect
   *   The domain redirect for the given domain if one was found,
   *   otherwise NULL.
   */
  public function getDomainRedirect($domain) {
    // Check if a redirect exists for this domain.
    $ids = $this->entityQueryFactory
      ->get('domain_redirect')
      ->condition('redirect_domain', $domain)
      ->execute();

    if ($ids) {
      // Load the redirect.
      return $this->entityManager
        ->getStorage('domain_redirect')
        ->load(@reset($ids));
    }

    return NULL;
  }
}
