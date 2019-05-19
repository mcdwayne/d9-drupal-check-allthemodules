<?php

namespace Drupal\translators_content\Access;

use Drupal\content_translation\Access\ContentTranslationManageAccessCheck;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\translators_content\Plugin\TranslatorsAccessRulesPluginManager;
use Symfony\Component\Routing\Route;

/**
 * Class TranslatorsContentManageAccess.
 *
 * @package Drupal\translators_content\Access
 */
class TranslatorsContentManageAccess extends ContentTranslationManageAccessCheck {

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;
  /**
   * Access rules manager.
   *
   * @var \Drupal\translators_content\Plugin\TranslatorsAccessRulesPluginManager
   */
  protected $accessRulesManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityManagerInterface $manager,
    LanguageManagerInterface $language_manager,
    AccountInterface $account,
    TranslatorsAccessRulesPluginManager $access_rules_manager
  ) {
    parent::__construct($manager, $language_manager);
    $this->account            = $account;
    $this->accessRulesManager = $access_rules_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(
    Route $route,
    RouteMatchInterface $route_match,
    AccountInterface $account,
    $source = NULL,
    $target = NULL,
    $language = NULL,
    $entity_type_id = NULL
  ) {
    $route_name = $route_match->getRouteName();
    $route      = $route_match->getRouteObject();
    $entity     = $route_match->getParameter($entity_type_id);

    // Workaround for translation overview page.
    if ($route_name === "entity.$entity_type_id.content_translation_overview") {
      return $this->checkOverviewAccess($route_match, $account, $entity_type_id);
    }

    $operation = $route->getRequirement('_access_content_translation_manage');

    return $this->accessRulesManager
      ->checkAccess($operation, $entity, $language);
  }

  /**
   * Additional access checking for translation overview page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultNeutral|mixed
   *   Access checking result.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkOverviewAccess(RouteMatchInterface $route_match, AccountInterface $account, $entity_type_id) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    if ($entity && $entity->isTranslatable()) {
      // Get entity base info.
      $bundle = $entity->bundle();

      // Get entity access callback.
      $definition      = $this->entityManager->getDefinition($entity_type_id);
      $translation     = $definition->get('translation');
      $access_callback = $translation['content_translation']['access_callback'];
      $access          = call_user_func($access_callback, $entity);
      if ($access->isAllowed()) {
        return $access;
      }

      // Check "translate any entity" permission.
      if ($account->hasPermission('translate any entity')) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      // Check per entity permission.
      $permission = "translate {$entity_type_id}";
      if ($definition->getPermissionGranularity() == 'bundle') {
        $permission = "translate {$bundle} {$entity_type_id}";
      }
      return AccessResult::allowedIfHasPermission($account, $permission);
    }

    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(ContentEntityInterface $entity, LanguageInterface $language = NULL, $operation = 'delete') {
    if (!$language instanceof LanguageInterface) {
      $language = \Drupal::languageManager()->getCurrentLanguage();
    }
    return $this->accessRulesManager->checkAccess($operation, $entity, $language->getId());
  }

}
