<?php

namespace Drupal\groupmenu;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentType;

/**
 * Group menu configuration overrides.
 */
class GroupMenuConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * The configuration storage.
   *
   * Do not access this directly. Should be accessed through self::getConfig()
   * so that the cache of configurations is used.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $baseStorage;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Statically cache configurations keyed by configuration name.
   *
   * @var array
   */
  protected $configurations;

  /**
   * Statically cache group types keyed by node type.
   *
   * @var array
   */
  protected $groupTypes;

  /**
   * Statically cache the current users group menu IDs keyed by group type.
   *
   * @var array
   */
  protected $userGroupMenuIds;

  /**
   * Statically cache overrides per node type.
   *
   * @var array[]
   */
  protected $overrides;

  /**
   * Constructs the GroupMenuConfigOverrides object.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(StorageInterface $storage, AccountInterface $current_user, CacheBackendInterface $cache) {
    $this->baseStorage = $storage;
    $this->currentUser = $current_user;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $node_type_names = array_filter($names, function ($name) {
      return strpos($name, 'node.type') === 0;
    });

    if (!empty($node_type_names)) {
      foreach ($node_type_names as $node_type_name) {
        if (isset($this->overrides[$node_type_name])) {
          $overrides[$node_type_name] = $this->overrides[$node_type_name];
        }
        else {
          $current_config = $this->getConfig($node_type_name);

          // We first get a list of all group types where the node type plugin
          // has enabled the setting to show group menus. With those group
          // types we can get all the group menu content types to look for
          // actual group menu content. Once we have the group menu content, we
          // can check their groups to see if the user has permissions to edit
          // the menus.
          $group_types = $this->getEnabledGroupMenuTypesByNodeType($current_config['type']);
          if ($group_types && $menus = $this->getUserGroupMenuIdsByGroupTypes($group_types)) {
            $overrides[$node_type_name] = [
              'third_party_settings' => [
                'menu_ui' => [
                  'available_menus' => array_merge($current_config['third_party_settings']['menu_ui']['available_menus'], $menus),
                ],
              ],
            ];

            // Add result to static cache.
            $this->overrides[$node_type_name] = $overrides[$node_type_name];
          }
        }
      }
    }

    return $overrides;
  }

  /**
   * Get all group types where the group menus are enabled for a node type.
   *
   * @param string $node_type
   *   A node type.
   *
   * @return array
   *   An array of group types with the ID as key and value.
   */
  protected function getEnabledGroupMenuTypesByNodeType($node_type) {
    if (isset($this->groupTypes[$node_type])) {
      return $this->groupTypes[$node_type];
    }

    $cid = 'groupmenu:group_menu_types:' . $node_type;
    $persistent_cache = $this->cache->get($cid);
    if ($persistent_cache && $persistent_cache->valid) {
      $this->groupTypes[$node_type] = $persistent_cache->data;
      return $this->groupTypes[$node_type];
    }

    $plugin_id = 'group_node:' . $node_type;
    $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);

    // Get the list of group types to find menus for.
    $this->groupTypes[$node_type] = [];
    /** @var \Drupal\group\entity\GroupContentTypeInterface $group_content_type */
    foreach ($group_content_types as $group_content_type) {
      if (!empty($group_content_type->getContentPlugin()->getConfiguration()['node_form_group_menu'])) {
        $this->groupTypes[$node_type][$group_content_type->getGroupType()->id()] = $group_content_type->getGroupType()->id();
      }
    }

    $this->cache->set($cid, $this->groupTypes[$node_type]);
    return $this->groupTypes[$node_type];
  }

  /**
   * Get a users group menu IDs for a list of group types.
   *
   * @param array $group_types
   *   An array of group types with the ID as key.
   *
   * @return array
   *   An array of menu IDs.
   */
  protected function getUserGroupMenuIdsByGroupTypes(array $group_types) {
    $group_types_cid = md5(implode('-', $group_types));
    if (isset($this->userGroupMenuIds[$this->currentUser->id()][$group_types_cid])) {
      return $this->userGroupMenuIds[$this->currentUser->id()][$group_types_cid];
    }

    $cid = 'groupmenu:user_group_menu_ids:' . $this->currentUser->id() . ':' . $group_types_cid;
    $persistent_cache = $this->cache->get($cid);
    if ($persistent_cache && $persistent_cache->valid) {
      $this->userGroupMenuIds[$this->currentUser->id()][$group_types_cid] = $persistent_cache->data;
      return $this->userGroupMenuIds[$this->currentUser->id()][$group_types_cid];
    }

    // We can't use dependency injection for entity type manager, since this
    // will cause circular dependencies.
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $plugin_id = 'group_menu:menu';
    $group_content_types = $entity_type_manager->getStorage('group_content_type')
      ->loadByProperties([
        'content_plugin' => $plugin_id,
        'group_type' => array_keys($group_types),
      ]);

    if (empty($group_content_types)) {
      return [];
    }

    $group_contents = $entity_type_manager->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
      ]);

    // Check access and add menus to config.
    $this->userGroupMenuIds[$this->currentUser->id()][$group_types_cid] = [];
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      if ($group_content->getGroup()->hasPermission("update $plugin_id entity", $this->currentUser)) {
        $this->userGroupMenuIds[$this->currentUser->id()][$group_types_cid][] = $group_content->getEntity()->id();
      }
    }

    $this->cache->set($cid, $this->userGroupMenuIds[$this->currentUser->id()][$group_types_cid]);
    return $this->userGroupMenuIds[$this->currentUser->id()][$group_types_cid];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfig($config_name) {
    if (!isset($this->configurations[$config_name])) {
      $this->configurations[$config_name] = $this->baseStorage->read($config_name);
    }
    return $this->configurations[$config_name];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'GroupMenuConfigOverrides';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
