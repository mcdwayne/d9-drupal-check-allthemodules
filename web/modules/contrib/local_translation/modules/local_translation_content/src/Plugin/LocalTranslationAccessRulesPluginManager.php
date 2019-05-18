<?php

namespace Drupal\local_translation_content\Plugin;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class LocalTranslationAccessRulesPluginManager.
 *
 * @package Drupal\local_translation_content\Access
 */
class LocalTranslationAccessRulesPluginManager extends DefaultPluginManager {

  /**
   * Supported operations array.
   *
   * @var array
   */
  protected $supportedOperations;
  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;
  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * Flag about enabled permissions checking.
   *
   * @var bool
   */
  protected $isEnabled;
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Local Translation user skills service.
   *
   * @var \Drupal\local_translation\Services\LocalTranslationUserSkills
   */
  protected $userSkills;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/LocalTranslationAccessRules',
      $namespaces,
      $module_handler,
      'Drupal\local_translation_content\Plugin\LocalTranslationAccessRulesInterface',
      'Drupal\local_translation_content\Annotation\LocalTranslationAccessRule'
    );
    $this->supportedOperations = ['update', 'delete', 'create'];
    $this->alterInfo('local_translation_content_access_rules_info');
    $this->setCacheBackend($cache_backend, 'local_translation_content_access_rules_info_plugins');

    list($this->userSkills,
      $this->languageManager,
      $this->entityTypeManager,
      $this->configFactory,
      $this->routeMatch,
      $this->currentUser) = array_reverse(func_get_args());
    $this->isEnabled = (bool) $this->configFactory
      ->get('local_translation.settings')
      ->get('enable_local_translation_content_permissions');
  }

  /**
   * Check user access for the specified operation and language.
   *
   * @param string $operation
   *   Operation name.
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $entity
   *   Entity to be operated on.
   * @param string|null $langcode
   *   Language ID.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   *   Access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function checkAccess($operation, $entity = NULL, $langcode = NULL) {
    // Workaround for admins.
    if ($this->isAdmin()) {
      return AccessResult::allowed()
        ->cachePerPermissions();
    }

    // Skip any non-supported operations.
    if (!in_array($operation, $this->supportedOperations)) {
      return AccessResult::neutral()
        ->cachePerPermissions();
    }

    // If the permissions checking is disabled - re-use the core's flow.
    if (!$this->isEnabled) {
      // If the entity exists - inherit the core's way to check
      // the translation permissions.
      if ($entity instanceof ContentEntityInterface) {
        // Get entity base info.
        $entity_type_id = $entity->getEntityTypeId();
        $bundle = $entity->bundle();

        // Get entity's access callback.
        $definition      = $this->entityTypeManager->getDefinition($entity_type_id);
        $translation     = $definition->get('translation');
        $access_callback = $translation['content_translation']['access_callback'];
        $access          = call_user_func($access_callback, $entity);
        if ($access->isAllowed()) {
          return $access;
        }

        // Check "translate any entity" permission.
        if ($this->currentUser->hasPermission('translate any entity')) {
          return AccessResult::allowed()->cachePerPermissions();
        }

        // Check per entity permission.
        $permission = "translate {$entity_type_id}";
        if ($definition->getPermissionGranularity() == 'bundle') {
          $permission = "translate {$bundle} {$entity_type_id}";
        }
        $access = AccessResult::allowedIfHasPermission($this->currentUser, $permission);
        if ($access->isAllowed()) {
          return $access;
        }

        // Check for entity operation permission.
        return AccessResult::allowedIfHasPermission($this->currentUser, "$operation content translations");
      }
    }

    $definitions = $this->getDefinitions();
    // If there are no rules or no entity - skip checking.
    if (empty($definitions) || !$entity instanceof ContentEntityInterface) {
      // Workaround for "create" operation.
      if ($operation === 'create') {
        return $this->processCreateOperation();
      }
      return AccessResult::neutral()
        ->cachePerPermissions();
    }

    // Workaround for user edit form access.
    if ($entity->getEntityTypeId() === 'user' && !$this->currentUser->isAnonymous()) {
      if ($entity->id() === $this->currentUser->id() && $operation === 'update') {
        // Just return the same code as in User module.
        // @see \Drupal\user\UserAccessControlHandler::checkAccess().
        return AccessResult::allowed()->cachePerUser();
      }
    }

    // Additional workaround for the "create translation" operation.
    if ($operation === 'create' && $entity instanceof ContentEntityInterface) {
      return $this->processCreateTranslationOperation();
    }

    // Try to find at least one rule that allowing user to access.
    foreach ($definitions as $id => $definition) {
      /** @var \Drupal\local_translation_content\Plugin\LocalTranslationAccessRulesInterface $instance */
      $instance = $this->createInstance($id, $definition);
      if ($instance->isAllowed($operation, $entity, $langcode)) {
        return AccessResult::allowed()
          ->cachePerPermissions();
      }
    }

    return AccessResult::forbidden()
      ->cachePerPermissions();
  }

  /**
   * Process entity create translation operation access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result object.
   */
  protected function processCreateTranslationOperation() {
    // Fetch source language object from the route params.
    $source = $this->routeMatch->getParameter('source');
    // Prepare array with all "from" translation skills of the current user.
    $from = $this->userSkills->getSourceSkills();
    // Fallback for the non-specified source language.
    // In this case we gonna use the entity's default translation language.
    if (!$source instanceof LanguageInterface) {
      $entity = $this->routeMatch->getParameter('node');
      if ($entity instanceof ContentEntityInterface) {
        foreach ($from as $langcode) {
          if (!$entity->hasTranslation($langcode)) {
            continue;
          }
          $source = $this->languageManager->getLanguage($langcode);
          break;
        }
        if (!$source instanceof LanguageInterface) {
          $source = $entity->getUntranslated()->language();
        }
      }
    }

    // Allow access if the source translation language is exists
    // in the user's "from" translation skills array.
    return AccessResult::allowedIf(
      $source instanceof LanguageInterface
      && !empty($from)
      && in_array($source->getId(), $from)
    )->cachePerPermissions();
  }

  /**
   * Process entity create operation access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result object.
   */
  protected function processCreateOperation() {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type->getPermissionGranularity() === 'bundle') {
        $bundle_key = $entity_type->getBundleEntityType();
        if ($bundle = $this->routeMatch->getParameter($bundle_key)) {
          return AccessResult::allowedIfHasPermission(
            $this->currentUser,
            "local_translation_content create {$bundle->id()} content"
          )->andIf(
            AccessResult::allowedIf($this->userSkills->userHasSkill($langcode))
          )->cachePerPermissions();
        }
      }
    }
    return AccessResult::neutral()
      ->cachePerPermissions();
  }

  /**
   * Check whether the current user is admin or not.
   *
   * @return bool
   *   TRUE - if current user is admin, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function isAdmin() {
    // Prevent any checking for anonymous users.
    if ($this->currentUser->isAnonymous()) {
      return FALSE;
    }
    // Load all admin roles.
    $admin_roles = $this->entityTypeManager
      ->getStorage('user_role')
      ->getQuery()
      ->condition('is_admin', TRUE, '=')
      ->execute();

    // Get roles of current users.
    $roles = $this->currentUser->getRoles(TRUE);

    return !empty(array_intersect($roles, $admin_roles));
  }

}
