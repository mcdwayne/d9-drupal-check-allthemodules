<?php

namespace Drupal\condition_plugins_commerce\ContextProvider;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current order as a context on order routes.
 */
class OrderRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new OrderRouteContext.
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
    $context_definition = new ContextDefinition('entity:commerce_order', NULL, FALSE);
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['commerce_order'])) {
      if ($commerce_order = $this->routeMatch->getParameter('commerce_order')) {
        $value = $commerce_order;
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'entity.commerce_order_type.add') {
      $commerce_order_type = $this->routeMatch->getParameter('commerce_order_type');
      $value = Order::create(['type' => $commerce_order_type->id()]);
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['commerce_order'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:commerce_order', $this->t('Order from URL')));

    return ['commerce_order' => $context];
  }

}
