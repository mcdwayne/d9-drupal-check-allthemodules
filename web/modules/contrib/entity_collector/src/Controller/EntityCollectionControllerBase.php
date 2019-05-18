<?php

namespace Drupal\entity_collector\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_collector\Service\EntityCollectionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EntityCollectionControllerBase
 *
 * @package Drupal\entity_collector\Controller
 */
class EntityCollectionControllerBase implements ContainerInjectionInterface {

  /**
   * The entity collection manager.
   *
   * @var \Drupal\entity_collector\Service\EntityCollectionManagerInterface
   */
  protected $entityCollectionManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * EntityCollectorApiController constructor.
   *
   * @param \Drupal\entity_collector\Service\EntityCollectionManagerInterface $entityCollectionManager
   *   The entity collection manager.
   * @param \Drupal\Core\Session\AccountInterface|\Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(EntityCollectionManagerInterface $entityCollectionManager, AccountProxyInterface $currentUser, RequestStack $requestStack) {
    $this->entityCollectionManager = $entityCollectionManager;
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_collection.manager'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * Check for view access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param $entityCollectionId
   *
   * @return mixed
   */
  public function checkViewAccess(AccountInterface $account, $entityCollectionId) {
    $entityCollection = $this->entityCollectionManager->getEntityCollection($entityCollectionId);
    return $entityCollection->access('view', $account, TRUE);
  }

  /**
   * Check for update access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param $entityCollectionId
   *
   * @return mixed
   */
  public function checkUpdateAccess(AccountInterface $account, $entityCollectionId) {
    $entityCollection = $this->entityCollectionManager->getEntityCollection($entityCollectionId);
    return $entityCollection->access('update', $account, TRUE);
  }

  /**
   * Check for delete access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param $entityCollectionId
   *
   * @return mixed
   */
  public function checkDeleteAccess(AccountInterface $account, $entityCollectionId) {
    $entityCollection = $this->entityCollectionManager->getEntityCollection($entityCollectionId);
    return $entityCollection->access('delete', $account, TRUE);
  }
}
