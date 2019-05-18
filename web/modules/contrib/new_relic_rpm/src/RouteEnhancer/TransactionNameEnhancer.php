<?php

namespace Drupal\new_relic_rpm\RouteEnhancer;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Enhances routes with a dynamic transaction name.
 */
class TransactionNameEnhancer implements EnhancerInterface {

  private $resolver;

  /**
   * Constructor.
   */
  public function __construct(ControllerResolverInterface $resolver) {
    $this->resolver = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {

    /** @var \Symfony\Component\Routing\Route $route */
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if (!$route->hasDefault('_transaction_name_callback')) {
      return $defaults;
    }

    $cb = $route->getDefault('_transaction_name_callback');
    $callable = $this->resolver->getControllerFromDefinition($cb);

    // Clone the request so we can set the attributes now.  Otherwise,
    // attributes aren't populated until after the route is enhanced.
    $cloned = clone $request;
    $cloned->attributes->replace($defaults);
    $arguments = $this->resolver->getArguments($cloned, $callable);
    $defaults['_transaction_name'] = call_user_func_array($callable, $arguments);

    return $defaults;
  }

}
