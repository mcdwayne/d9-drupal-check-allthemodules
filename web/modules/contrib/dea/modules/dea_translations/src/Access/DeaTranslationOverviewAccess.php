<?php

namespace Drupal\dea_translations\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_translation\Access\ContentTranslationOverviewAccess;
use Drupal\dea\EntityAccessManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Access check for entity translation overview.
 * This class is responsible for allowing access to the Translate tab of an entity
 * ex: /node/nid/translations
 */
class DeaTranslationOverviewAccess extends ContentTranslationOverviewAccess implements AccessInterface {

  /**
   * @var \Drupal\dea\EntityAccessManager
   */
  protected $accessManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $typeManager;

  /**
   * Constructs a DeaTranslationOverviewAccess object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager, EntityAccessManager $access_manager, EntityTypeManagerInterface $type_manager) {
    $this->accessManager = $access_manager;
    $this->typeManager = $type_manager;
    parent::__construct($manager);
  }

  /**
   * Checks access to the translation overview for the entity and bundle.
   *
   * As Core falls a bit short here, we will apply the following convention:
   * the translate operation will be allowed for the user if he/she can
   * update the node.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, $entity_type_id) {
    $entity = $route_match->getParameter($entity_type_id);
    if ($entity && $entity->isTranslatable()) {
      $dea_access = $this->accessManager->access($entity, 'update', $account);
      if ($dea_access != AccessResult::neutral()) {
        return $dea_access;
      }
    }
    return parent::access($route_match, $account, $entity_type_id);
  }

}
