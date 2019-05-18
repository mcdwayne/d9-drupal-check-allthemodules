<?php

namespace Drupal\access_conditions;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\access_conditions\Entity\AccessModelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides an access checker service.
 */
class AccessChecker implements CacheableDependencyInterface {

  use ConditionAccessResolverTrait;
  use RefinableCacheableDependencyTrait;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a AccessChecker object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository, AccountProxyInterface $current_user) {
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access for a permission tree.
   *
   * @param \Drupal\access_conditions\Entity\AccessModelInterface $access_model
   *   The access model that contain the conditions to be evaluated.
   *
   * @return bool
   *   TRUE if access is granted or FALSE if access is denied.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkAccess(AccessModelInterface $access_model) {
    $this->resetCache();

    // Skip if the user can bypass access conditions access control.
    if ($this->currentUser->hasPermission('bypass access conditions access')) {
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($this->currentUser->id());
      $this->addCacheableDependency($user);

      return TRUE;
    }

    $conditions = [];
    $missing_context = FALSE;
    foreach ($access_model->getAccessConditions() as $condition_id => $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        try {
          $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
          $this->contextHandler->applyContextMapping($condition, $contexts);
        }
        catch (ContextException $e) {
          $missing_context = TRUE;
        }
      }
      $conditions[$condition_id] = $condition;
      $this->addCacheableDependency($condition);
    }

    if (!$missing_context && $this->resolveConditions($conditions, $access_model->getAccessLogic()) !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Reset cache values.
   */
  private function resetCache() {
    $this->cacheContexts = [];
    $this->cacheTags = [];
    $this->cacheMaxAge = Cache::PERMANENT;
  }

}
