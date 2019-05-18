<?php

namespace Drupal\prev_next_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Provides a 'PrevNextBlock' block.
 *
 * @Block(
 *  id = "prev_next_block",
 *  admin_label = @Translation("Previous Next Block"),
 * )
 */
class PrevNextBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MenuActiveTrailInterface.
   *
   * @var Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * EntityTypeInterface.
   *
   * @var Drupal\Core\Entity\EntityTypeInterface
   * Storage object for menu_link_content.
   */
  protected $menuStorage;

  /**
   * MenuName.
   *
   * @var string
   * Machine name of menu we're working with.
   */
  protected $menuName;

  /**
   * Constructs a new BlockBase.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The Menu Active Trail service.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    LinkGeneratorInterface $link_generator,
    EntityTypeManagerInterface $entity_type_manager,
    MenuActiveTrailInterface $menu_active_trail) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkGenerator = $link_generator;
    $this->entityTypeManager = $entity_type_manager;
    $this->menuActiveTrail = $menu_active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('link_generator'),
      $container->get('entity_type.manager'),
      $container->get('menu.active_trail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $default_value = FALSE;
    if (isset($this->configuration['menu_name'])) {
      $default_value = $this->configuration['menu_name'];
    }
    
    // Get all defined menus.
    $this->menuStorage = $this->entityTypeManager->getStorage('menu');
    $all_menus = $this->menuStorage->getQuery()->execute();
    
    $form['menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu to use'),
      '#default_value' => $default_value,
      '#options' => $all_menus,
      '#description' => $this->t('Select the menu for which this block will generate previous and next links.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['menu_name'] = $form_state->getValue('menu');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_menu_uuid = $parent_menu_uuid = $grandparent_menu_uuid = '';
    $prev_index = $next_index = $parent_menu_id = NULL;
    $prev_entity_id = $next_entity_id = $parents = NULL;
    $next_parent = $prev_parent = NULL;
    $children = [];
    $has_children = $prev_sibling_has_children = FALSE;
    $prev_url = NULL;
    $prev_title = $this->t('Previous');
    $next_url = NULL;
    $next_title = $this->t('Next Page');

    $items = [
      'prev' => [],
      'next' => [],
    ];

    $this->menuStorage = $this->entityTypeManager
      ->getStorage('menu_link_content');

    // Get the menu to work with.
    $menu_name = $this->configuration['menu_name'];

    if (!isset($menu_name)) {
      // Menu has not been set yet.
      return [];
    }

    $this->menuName = $menu_name;

    $active_trail_ids = $this->menuActiveTrail->getActiveTrailIds($menu_name);

    // Setup empty array that still correctly sets cache.
    $build['#cache']['keys'][] = 'prev_next_block';
    $build['#cache'] = ['max-age' => -1];
    // Block should be different for each different url.
    $build['#cache']['contexts'] = ['url.path'];
    // Invalidate this cache when menu is resaved, updated.
    $build['#cache']['tags'][] = 'config:system.menu.' . $menu_name;


    if (count($active_trail_ids) == 1) {
      // This block is on a request with no matching menu items.
      return $build;
    }

    // Start with current menu link.
    $current_menu_uuid = array_shift($active_trail_ids);
    $current_menu_id = $this->getMenuId($current_menu_uuid);
    $current_menu_entity = $this->menuStorage->load($current_menu_id);

    // Get any children of this current menu item.
    $children = $this->children($current_menu_uuid);
    if (!empty($children)) {
      $children = array_values($children);
      $has_children = TRUE;
    }

    // Parent of '' is top level of menu, so query with parent is NULL.
    $parent_menu_uuid = array_shift($active_trail_ids);
    if ($parent_menu_uuid != '') {
      $parent_menu_id = $this->getMenuId($parent_menu_uuid);
    }

    $grandparent_menu_uuid = array_shift($active_trail_ids);

    // Get siblings.
    $siblings_ordered = $this->getSiblings($parent_menu_uuid);

    // Get parents, aunts, uncles.
    $parents_ordered = $this->getSiblings($grandparent_menu_uuid);

    // Find current index, prev, next.
    $sibling_index = array_search($current_menu_id, $siblings_ordered);
    if ($sibling_index !== FALSE) {
      $prev_index = $sibling_index - 1;
      $next_index = $sibling_index + 1;

      // Check if previous sibling has children.
      if (isset($siblings_ordered[$prev_index])) {
        $prev_sibling_entity = $this->menuStorage
          ->load($siblings_ordered[$prev_index]);
        if (is_object($prev_sibling_entity)) {
          $prev_uuid = $prev_sibling_entity->getPluginId();
          $prev_last_child = $this->lastChild($prev_uuid);
          if ($prev_last_child) {
            $prev_sibling_has_children = TRUE;
          }
        }
      }
    }

    // Find current parent index, prev, next.
    if ($parent_menu_id) {
      $parent_index = array_search($parent_menu_id, $parents_ordered);
      if ($parent_index !== FALSE) {
        $prev_parent = $parent_index - 1;
        $next_parent = $parent_index + 1;
      }
    }

    // Get prev item.
    if ($prev_sibling_has_children && $prev_index > -1) {
      // Prev is last child of previous sibling.
      $prev_entity = $this->menuStorage
        ->load($prev_last_child);
      if (is_object($prev_entity)) {
        $url = $prev_entity->getUrlObject();
        $prev_url = $url;
      }
    }
    elseif (isset($siblings_ordered[$prev_index])) {
      // Simple case of prev sibling.
      $prev_entity_id = $siblings_ordered[$prev_index];
      $prev_menu_entity = $this->menuStorage
        ->load($siblings_ordered[$prev_index]);
      $url = $prev_menu_entity->getUrlObject();
      $prev_url = $url;
    }
    elseif ($prev_index === -1 && isset($parent_index)) {
      // Prev item is parent of these siblings.
      $prev_menu_entity = $this->menuStorage
        ->load($parents_ordered[$parent_index]);
      $url = $prev_menu_entity->getUrlObject();
      $prev_url = $url;
    }

    // Get next item.
    if ($has_children) {
      // Next is first child of current menu item.
      $next_menu_entity = $this->menuStorage->load($children[0]);
      $url = $next_menu_entity->getUrlObject();
      $next_url = $url;
    }
    elseif (isset($siblings_ordered[$next_index])) {
      // Simplest case of next sibling.
      $next_entity_id = $siblings_ordered[$next_index];
      $next_menu_entity = $this->menuStorage
        ->load($siblings_ordered[$next_index]);
      $url = $next_menu_entity->getUrlObject();
      $next_url = $url;
    }
    else {
      // Next could be next parent or older generation.
      $next_menu_entity = $this->findUncle($current_menu_entity);
      if ($next_menu_entity) {
        $next_url = $next_menu_entity->getUrlObject();
      }
    }

    // Generate prev content.
    if ($prev_url) {
      $prev_url->setOption('attributes', [
        'class' => [
          'pager__link',
          'pager__link--prev',
        ],
      ]);
      $items['prev'] = Link::fromTextAndUrl($prev_title, $prev_url)->toRenderable();
    }
    else {
      $items['prev']['#markup'] = $prev_title;
    }

    // Generate next content.
    if ($next_url) {
      $next_url->setOption('attributes', [
        'class' => [
          'pager__link',
          'pager__link--next',
        ],
      ]);
      $items['next'] = Link::fromTextAndUrl($next_title, $next_url)->toRenderable();
    }
    else {
      $items['next']['#markup'] = $next_title;
    }

    $build['#cache']['keys'][] = 'prev_next_block';
    $build['#cache'] = ['max-age' => -1];
    // Block should be different for each different url.
    $build['#cache']['contexts'] = ['url.path'];
    // Invalidate this cache when menu is resaved, updated.
    $build['#cache']['tags'][] = 'config:system.menu.' . $menu_name;
    $build['nav_links'] = $items;
    return $build;
  }

  /**
   * Get Menu Item Entity ID from Menu UUID.
   *
   * @param mixed $menu_uuid
   *   UUID of a menu item.
   *
   * @return int
   *   Entity ID of menu item.
   */
  protected function getMenuId($menu_uuid) {

    $parts = explode(':', $menu_uuid);
    if (isset($parts[1])) {
      $entity_id = $this->menuStorage->getQuery()
        ->condition('uuid', $parts[1])
        ->execute();
      return array_shift($entity_id);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get array of this menu link's children.
   *
   * @param string $menu_uuid
   *   UUID of a menu item.
   *
   * @return array
   *   Children menu link entity ids or empty array.
   */
  protected function children($menu_uuid) {
    $children = $this->menuStorage->getQuery()
      ->condition('menu_name', $this->menuName)
      ->condition('parent', $menu_uuid)
      ->sort('weight', 'ASC')
      ->sort('title', 'ASC')
      ->execute();
    return $children;
  }

  /**
   * Get array of this menu link's siblings.
   *
   * @param string $parent_menu_uuid
   *   UUID of a menu item.
   *
   * @return array
   *   Sibling menu link entity ids or empty array.
   */
  protected function getSiblings($parent_menu_uuid = '') {
    $siblings = $this->menuStorage->getQuery()
      ->condition('menu_name', $this->menuName);
    if ($parent_menu_uuid == '') {
      $siblings->condition('parent', NULL, 'IS');
    }
    else {
      $siblings->condition('parent', $parent_menu_uuid);
    }
    $siblings = $siblings->sort('weight', 'ASC')->sort('title', 'ASC')
      ->execute();
    return array_values($siblings);
  }

  /**
   * Recusively find next parent sibling.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $menu_entity
   *   Is a menu_link_content entity.
   *
   * @return mixed
   *   Entity of next parent sibling or FALSE.
   */
  protected function findUncle(MenuLinkContent $menu_entity) {
    $parent_uuid = $menu_entity->getParentId();
    $parent_id = $this->getMenuId($parent_uuid);
    if (!$parent_id) {
      return FALSE;
    }
    $parent_entity = $this->menuStorage->load($parent_id);
    $grandparent_uuid = $parent_entity->getParentId();
    $siblings = $this->getSiblings($grandparent_uuid);
    $parent_index = array_search($parent_id, $siblings);
    if ($parent_index !== FALSE && isset($siblings[$parent_index + 1])) {
      return $this->menuStorage->load($siblings[$parent_index + 1]);
    }
    else {
      // Try one generation older.
      $menu_entity = $this->menuStorage->load($parent_id);
      if (is_object($menu_entity)) {
        return $this->findUncle($menu_entity);
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Recursively get last child of a menu link - who has no children themselves.
   *
   * @param string $menu_uuid
   *   UUID of a menu item.
   *
   * @return int
   *   last children menu link entity ids or FALSE.
   */
  protected function lastChild($menu_uuid) {
    $children = $this->children($menu_uuid);
    if (!empty($children)) {
      $last_child = array_pop($children);
      if ($last_child_entity = $this->menuStorage->load($last_child)) {
        $last_uuid = $last_child_entity->getPluginId();
        $next_generation = $this->children($last_uuid);
        if (empty($next_generation)) {
          return $last_child;
        }
        else {
          $last_child = array_pop($next_generation);
          $last_child_entity = $this->menuStorage->load($last_child);
          $last_uuid = $last_child_entity->getPluginId();
          return $this->lastChild($last_uuid);
        }
      }
    }
    else {
      return $this->getMenuId($menu_uuid);
    }
  }

}
