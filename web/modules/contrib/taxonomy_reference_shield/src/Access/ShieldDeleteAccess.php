<?php

namespace Drupal\taxonomy_reference_shield\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy_reference_shield\ReferenceHandlerInterface;
use Symfony\Component\Routing\Route;

/**
 * Verifies access to the shield delete form.
 */
class ShieldDeleteAccess implements AccessInterface {

  /**
   * The module's configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The reference handler.
   *
   * @var \Drupal\taxonomy_reference_shield\ReferenceHandlerInterface
   */
  protected $referenceHandler;

  /**
   * Builds a new AvoidIgnoredRoles object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\taxonomy_reference_shield\ReferenceHandlerInterface $reference_handler
   *   The reference handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ReferenceHandlerInterface $reference_handler) {
    $this->config = $config_factory->get('taxonomy_reference_shield.config')->get('enabled');
    $this->referenceHandler = $reference_handler;
  }

  /**
   * Checks access if role is ignored.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $parameters = $route_match->getParameters();

    if (!$parameters->has('taxonomy_term')) {
      return AccessResult::forbidden();
    }

    $term = $parameters->get('taxonomy_term');
    if (isset($this->config[$term->bundle()]) && $this->config[$term->bundle()] && $this->referenceHandler->getReferences($term, TRUE)) {
      return AccessResult::allowedIfHasPermission($account, 'delete terms in ' . $term->bundle());
    }

    return AccessResult::forbidden();
  }

}
