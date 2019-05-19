<?php

namespace Drupal\views_tag_access;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views\Entity\View;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A helper class for dealing with tags on a view.
 */
class ViewsTagAccessHelper {

  /**
   * The view we are working with.
   *
   * @var \Drupal\views\Entity\View
   */
  protected $view;

  /**
   * The tags we define permissions for.
   */
  protected $permissionTags;

  /**
   * The tags, broken into an array and trimmed.
   */
  protected $tags;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The private tempstore.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempstoreFactory;

  /**
   * Build a views tag helper class.
   *
   * @param \Drupal\views\Entity\View $view
   *   The view we are working with.
   */
  public function __construct(View $view, ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, PrivateTempStoreFactory $tempstore_factory) {
    $this->view = $view;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->tempstoreFactory = $tempstore_factory;
  }

  /**
   * Retrieve a view tag access helper.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Drupal\views\Entity\View $view
   *   The view we are working with.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, View $view) {
    return new static(
      $view,
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * Get the tags for a view.
   *
   * @param bool $permission_only
   *   Whether to only include tags we have permissions for.
   * @param bool $rebuild
   *   Whether to rebuild the list of tags from the view.
   *
   * @return array
   *   An array of tags.
   */
  public function getTags($permission_only = FALSE, $rebuild = FALSE) {
    if ($rebuild || !isset($this->tags)) {
      $this->permissionTags = NULL;
      $this->tags = array_filter(array_map('trim', explode(',', $this->view->get('tag'))));
    }

    if ($permission_only) {
      if (!isset($this->permissionTags)) {
        $this->permissionTags = array_intersect($this->tags, $this->configFactory->get('views_tag_access.settings')->get('tags'));
      }
    }

    return $permission_only ? $this->permissionTags : $this->tags;
  }

  /**
   * Check whether the view has the given tag.
   *
   * @param string $tag
   *   The tag to check for.
   * @param bool $permission_only
   *   Whether to only include tags we have permissions for.
   *
   * @return bool
   *   TRUE if the view has the given tag.
   */
  public function hasTag($tag, $permission_only = FALSE) {
    return in_array($tag, $this->getTags($permission_only));
  }

  /**
   * Check whether the view has the any of the given tags.
   *
   * @param string[] $tags
   *   The tags to check for.
   * @param bool $permission_only
   *   Whether to only include tags we have permissions for.
   *
   * @return bool
   *   TRUE if the view has any of the given tags.
   */
  public function hasAnyTag(array $tags, $permission_only = FALSE) {
    return array_intersect($tags, $this->getTags($permission_only));
  }

  /**
   * Check whether the view has the all of the given tags.
   *
   * @param string[] $tags
   *   The tags to check for.
   * @param bool $permission_only
   *   Whether to only include tags we have permissions for.
   *
   * @return bool
   *   TRUE if the view has all of the given tags.
   */
  public function hasAllTag(array $tags, $permission_only = FALSE) {
    return array_intersect($tags, $this->getTags($permission_only)) == $tags;
  }

  /**
   * Check access for an operation on this view based on it's tags.
   *
   * @param string|NULL $operation
   *   The operation we want to check access for or NULL to see if we have
   *   access to any of the operations we work with.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account we are checking access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($operation, AccountInterface $account) {
    // Capture if we are after access to any operation.
    $any_operation = !isset($operation);

    // Ensure this is one of the operations we work with.
    if (!$any_operation && !in_array($operation, ['update', 'duplicate', 'enable', 'disable', 'delete'])) {
      return AccessResult::neutral();
    }

    // If this view has been created by this user in this session, we need to
    // bypass this check for the duration of the session. We double check that
    // the account we're testing is the current user so we don't return false
    // positives.
    if ($this->currentUser->id() == $account->id()) {
      $store = $this->tempstoreFactory->get('views_tag_access');
      $views = $store->get('created_views');
      if (is_array($views) && in_array($this->view->id(), $views)) {
        return AccessResult::allowed();
      }
    }

    // Now loop over the tagged based permissions.
    foreach ($this->getTags(TRUE) as $tag) {
      // If we have administer views tagged TAG allow access.
      if ($account->hasPermission("administer views tagged {$tag}")) {
        return AccessResult::allowed();
      }

      // If $operations is NULL, we need to check all the operations we work
      // with.
      $operations = !$any_operation ? (array) $operation : ['update', 'duplicate', 'enable', 'disable', 'delete'];
      foreach ($operations as $op) {
        if ($account->hasPermission("{$op} views tagged {$tag}")) {
          return AccessResult::allowed();
        }
      }
    }

    // If we are checking access for any operations, we need
    // Otherwise, we need to be neutral.
    return AccessResult::neutral();
  }

}
