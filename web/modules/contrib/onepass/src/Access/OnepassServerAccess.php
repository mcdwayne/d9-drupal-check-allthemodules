<?php

namespace Drupal\onepass\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\onepass\OnepassServiceInterface;
use Symfony\Component\Routing\Route;

/**
 * Define access service for access only from 1Pass service server.
 */
class OnepassServerAccess implements AccessInterface {

  /**
   * Onepass service.
   *
   * @var \Drupal\onepass\OnepassServiceInterface
   */
  protected $onepass;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\onepass\OnepassServiceInterface $onepass
   *   Onepass service.
   */
  public function __construct(OnepassServiceInterface $onepass) {
    $this->onepass = $onepass;
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account) {
    return $this->onepass->isRequestValid() ? AccessResult::allowed() : AccessResult::forbidden();
  }

}