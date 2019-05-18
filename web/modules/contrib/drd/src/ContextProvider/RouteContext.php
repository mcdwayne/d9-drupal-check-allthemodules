<?php

namespace Drupal\drd\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Abstract class to set current host/core/domain as a context on routes.
 */
abstract class RouteContext implements ContextProviderInterface, RouteContextInterface {
  use StringTranslationTrait;

  /**
   * Callback to determine if we are on a host, core or domain page.
   *
   * @return bool|RouteContext
   *   The matching route context or FALSE.
   */
  public static function findDrdContext() {
    foreach (['drd_domain.domain_route_context',
      'drd_core.core_route_context',
      'drd_host.host_route_context',
    ] as $item) {
      /** @var RouteContext $context */
      $context = \Drupal::service($item);
      if ($context->getEntity()) {
        return $context;
      }
    }
    return FALSE;
  }

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The DRD entity if we are on a DRD entity context.
   *
   * @var \Drupal\drd\Entity\BaseInterface
   */
  protected $entity;

  /**
   * Flag for view mode (vs. edit mode)
   *
   * @var bool
   */
  protected $viewMode = TRUE;

  /**
   * Constructs a new DrdRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = new ContextDefinition('entity:' . $this->getType(), NULL, FALSE);
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts[$this->getType()])) {
      if ($domain = $this->routeMatch->getParameter($this->getType())) {
        $value = $domain;
        $compiled = $route_object->compile();
        $tokens = $compiled->getTokens();
        $this->viewMode = (count($tokens) == 2);
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result[$this->getType()] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:' . $this->getType(), $this->t('Domain from URL')));
    return [$this->getType() => $context];
  }

  /**
   * Determine the entity of the current context.
   *
   * @return \Drupal\drd\Entity\BaseInterface|bool
   *   The entity if in DRD entity context or FALSE otherwise.
   */
  public function getEntity() {
    if (!isset($this->entity)) {
      $runtimeContexts = $this->getRuntimeContexts([]);
      if (!empty($runtimeContexts[$this->getType()])) {
        $this->entity = $runtimeContexts[$this->getType()]->getContextValue();
      }
      else {
        $this->entity = FALSE;
      }
    }
    return $this->entity;
  }

  /**
   * Get view mode.
   *
   * @return bool
   *   TRUE if we are in view mode.
   */
  public function getViewMode() {
    return $this->viewMode;
  }

}
