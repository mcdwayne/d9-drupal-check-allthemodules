<?php

namespace Drupal\role_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'RoleMenuBlock' block.
 *
 * @Block(
 *  id = "role_menu_block",
 *  admin_label = @Translation("Role menu block"),
 * )
 */
class RoleMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, MenuLinkTreeInterface $menu_link_tree) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkTree = $menu_link_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $roles = $this->currentUser->getRoles();
    $role_storage = $this->entityTypeManager->getStorage('user_role');
    foreach ($roles as $role) {
      /** @var \Drupal\user\RoleInterface $entity */
      $entity = $role_storage->load($role);
      $menu_id = $entity->getThirdPartySetting('role_menu', 'menu', '');
      if (!empty($menu_id)) {
        $build[$menu_id][] = [
          '#markup' => '<ul class="nav"><li class="nav-header">' . $this->t('@role navigation', ['@role' => $entity->label()]) . '</li></ul>',
        ];
        $build[$menu_id][] = $this->buildMenu($menu_id);
      }
    }

    return $build;
  }

  protected function buildMenu($menu_id) {
    $parameters = $this->menuLinkTree->getCurrentRouteMenuTreeParameters($menu_id);

    // Build the whole menu tree
    $parameters->expandedParents = [];

    $tree = $this->menuLinkTree->load($menu_id, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    $build = $this->menuLinkTree->build($tree);
    $build['#theme'] = 'menu__role_menu';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();

    $roles = $this->currentUser->getRoles();
    $role_storage = $this->entityTypeManager->getStorage('user_role');
    foreach ($roles as $role) {
      /** @var \Drupal\user\RoleInterface $entity */
      if ($entity = $role_storage->load($role)) {
        $menu_id = $entity->getThirdPartySetting('role_menu', 'menu', '');
        if (!empty($menu_id)) {
          $cache_tags[] = 'config:system.menu.' . $menu_id;
        }
      }
    }

    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = [];
    $roles = $this->currentUser->getRoles();
    $role_storage = $this->entityTypeManager->getStorage('user_role');
    foreach ($roles as $role) {
      /** @var \Drupal\user\RoleInterface $entity */
      if ($entity = $role_storage->load($role)) {
        $menu_id = $entity->getThirdPartySetting('role_menu', 'menu', '');
        if (!empty($menu_id)) {
          $contexts[] = 'route.menu_active_trails:' . $menu_id;
        }
      }
    }

    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);
  }

}
