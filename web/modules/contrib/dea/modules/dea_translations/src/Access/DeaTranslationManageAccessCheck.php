<?php

namespace Drupal\dea_translations\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\content_translation\Access\ContentTranslationManageAccessCheck;
use Drupal\dea\EntityAccessManager;


/**
 * Access check for entity translation CRUD operation.
 */
class DeaTranslationManageAccessCheck extends ContentTranslationManageAccessCheck implements AccessInterface {

  /**
   * @var \Drupal\dea\EntityAccessManager
   */
  protected $accessManager;

  /**
   * Constructs a ContentTranslationManageAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $manager, LanguageManagerInterface $language_manager, EntityAccessManager $access_manager) {
    $this->accessManager = $access_manager;
    parent::__construct($manager, $language_manager);
  }

  /**
   * Checks translation access for the entity and operation on the given route.
   *
   * As Core falls a bit short here, we will apply the following convention:
   * the translate operation will be allowed for the user if he/she can
   * update the node.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $source
   *   (optional) For a create operation, the language code of the source.
   * @param string $target
   *   (optional) For a create operation, the language code of the translation.
   * @param string $language
   *   (optional) For an update or delete operation, the language code of the
   *   translation being updated or deleted.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, $source = NULL, $target = NULL, $language = NULL, $entity_type_id = NULL) {
    $entity = $route_match->getParameter($entity_type_id);
    if ($entity && $entity->isTranslatable()) {
      $dea_access = $this->accessManager->access($entity, 'update', $account);
      if ($dea_access != AccessResult::neutral()) {
        return $dea_access;
      }
    }
    return parent::access($route, $route_match, $account, $source, $target, $language, $entity_type_id);
  }

}
