<?php

namespace Drupal\search_api_saved_searches\Plugin\views\argument_validator;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api_saved_searches\Entity\SavedSearchAccessControlHandler;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates whether the argument matches the current authenticated user.
 *
 * This code is based on the Commerce module.
 *
 * @ViewsArgumentValidator(
 *   id = "search_api_saved_searches_current_authenticated_user",
 *   title = @Translation("Current authenticated user"),
 *   entity_type = "user",
 * )
 */
class CurrentAuthenticatedUser extends ArgumentValidatorPluginBase implements CacheableDependencyInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|null
   */
  protected $currentUser;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setCurrentUser($container->get('current_user'));
    $plugin->setEntityTypeManager($container->get('entity_type.manager'));

    return $plugin;
  }

  /**
   * Retrieves the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  public function getCurrentUser() {
    return $this->currentUser ?: \Drupal::service('current_user');
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The new current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::service('entity_type.manager');
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The new entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    // A non-numeric argument can't be a valid UID.
    if (!is_numeric($argument)) {
      return FALSE;
    }

    $admin_permission = SavedSearchAccessControlHandler::ADMIN_PERMISSION;
    $is_admin = $this->getCurrentUser()->hasPermission($admin_permission);

    // Only admins are allowed to view the list of anonymous users' searches.
    if ($argument == 0) {
      return $is_admin;
    }

    try {
      $user_storage = $this->getEntityTypeManager()->getStorage('user');
      $user = $user_storage->load($argument);
    }
    // @todo Use a multi-catch once we can depend on PHP 7.1+.
    catch (InvalidPluginDefinitionException $e) {
    }
    catch (PluginNotFoundException $e) {
    }

    if (empty($user)) {
      return FALSE;
    }
    return $is_admin || $user->id() == $this->getCurrentUser()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
