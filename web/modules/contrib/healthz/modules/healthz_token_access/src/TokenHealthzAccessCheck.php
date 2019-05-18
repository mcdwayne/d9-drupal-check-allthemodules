<?php

namespace Drupal\healthz_token_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Check if the healthz access token is present.
 */
class TokenHealthzAccessCheck implements AccessInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Create an instance of RouteAlterSubscriber.
   */
  public function __construct(ConfigFactoryInterface $configFactory, RequestStack $requestStack) {
    $this->configFactory = $configFactory;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match) {
    $settings = $this->configFactory->get('healthz_token_access.settings');
    // Allow both of these to be empty for environments where no token is set.
    return AccessResult::allowedIf($this->requestStack->getCurrentRequest()->query->get('token') === $settings->get('access_token'))
      ->addCacheContexts(['url.query_args'])
      ->addCacheableDependency($settings);
  }

}
