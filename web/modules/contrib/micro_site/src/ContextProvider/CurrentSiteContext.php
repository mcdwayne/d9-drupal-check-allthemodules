<?php

namespace Drupal\micro_site\ContextProvider;

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a context handler for a micro site.
 */
class CurrentSiteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a CurrentSiteContext object.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(SiteNegotiatorInterface $negotiator, RouteMatchInterface $route_match) {
    $this->negotiator = $negotiator;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    // Load the current site.
    $current_site = $this->negotiator->getActiveSite();
    // Set the context.
    $context_definition = EntityContextDefinition::create('site')->setRequired(FALSE)->setLabel('Active site');

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['url.site']);
    $context = new Context($context_definition, $current_site);
    $context->addCacheableDependency($cacheability);
    $result['site'] = $context;
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('site', $this->t('Active site'));
    return ['site' => $context];
  }

}
