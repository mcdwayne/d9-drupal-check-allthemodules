<?php

namespace Drupal\transaction\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\transaction\Entity\TransactionType;
use Drupal\transaction\TransactionTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Checks access of applicable entity to transaction type.
 */
class ApplicableTransactionAccess implements AccessInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * ApplicableTransactionAccess constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RouteMatchInterface $route_match, RequestStack $request_stack) {
    $this->currentRouteMatch = $route_match;
    $this->requestStack = $request_stack;
  }

  /**
   * Check if the transaction type is applicable to the content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The involved content entity.
   * @param \Drupal\transaction\TransactionTypeInterface $transaction_type
   *   The transaction type.
   * @param \Symfony\Component\Routing\Route
   *   The route to check access for.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Allowed if the transaction type is applicable to the content entity.
   */
  public function access(ContentEntityInterface $entity = NULL, TransactionTypeInterface $transaction_type = NULL, Route $route = NULL, Request $request = NULL) {
    // Check access for the current route if none given.
    if (!$route) {
      $route = $this->currentRouteMatch->getRouteObject();
    }
    if (!$request) {
      $request = $this->requestStack->getCurrentRequest();
    }

    // Look after the target entity.
    if (!$entity) {
      $entity = $request->get('target_entity');
    }

    if (!$entity
      || !is_object($entity)
      || !($entity instanceof ContentEntityInterface)) {
      $entity = NULL;

      if (!($target_entity_type_id = $route->getOption('_transaction_target_entity_type_id'))
        && ($target_entity_type = $request->get('target_entity_type'))
        && is_object($target_entity_type)
        && $target_entity_type instanceof EntityTypeInterface) {
        $target_entity_type_id = $target_entity_type->id();
      }

      if ($target_entity_type_id) {
        $entity = $request->get($target_entity_type_id);
      }
    }

    // Look after the transaction type.
    if (!$transaction_type) {
      $transaction_type = $request->get('transaction_type');
    }

    if (!$transaction_type
      || !is_object($transaction_type)
      || !($transaction_type instanceof TransactionTypeInterface)) {
      $transaction_type = NULL;
      if ($transaction_type_id = $route->getOption('_transaction_transaction_type_id')) {
        $transaction_type = TransactionType::load($transaction_type_id);
      }
    }

    if (!$entity || !$transaction_type) {
      return AccessResult::forbidden();
    }

    $result = $transaction_type->isApplicable($entity)
      ? AccessResult::allowed()
      : AccessResult::forbidden();

    return $result
      ->addCacheableDependency($entity)
      ->addCacheableDependency($transaction_type);
  }

}
