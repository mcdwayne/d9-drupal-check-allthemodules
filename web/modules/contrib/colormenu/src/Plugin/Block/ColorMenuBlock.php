<?php

namespace Drupal\colormenu\Plugin\Block;

use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Provides a generic Menu block.
 *
 * @Block(
 *   id = "colormenu_sidebar_menu",
 *   admin_label = @Translation("Color Menu"),
 *   category = @Translation("Color"),
 * )
 */
class ColorMenuBlock extends BlockBase {

  /**
   *
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   *
   */
  private function setActiveTrailMenu($tree, $active_trail, $tr = []) {
    if (empty($tree) || empty($active_trail)) {
      return;
    }
    $i = 0;
    $al = $active_trail;
    $tr = [];
    foreach ($tree as $key => $var) {
      if (in_array($key, $al)) {
        $obj = $this->setActive($var);
        $tr[$key] = $obj;
      }
      else {
        $tr[$key] = $var;
      }
      if ($var->hasChildren) {
        $i++;
        $this->setActiveTrailMenu($var->subtree, $active_trail, $tr[$key]->subtree);
      }
    }
    return $tr;
  }

  /**
   *
   */
  private function setActive($obj) {
    if ($obj instanceof MenuLinkTreeElement) {
      $obj->inActiveTrail = TRUE;
    }
    return $obj;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menutree = \Drupal::menuTree();
    $menu_name = 'admin';
    $active_trail = \Drupal::service('menu.active_trail')->getActiveTrailIds($menu_name);
    $parameters = new MenuTreeParameters();
    $tree_ma = $menutree->load($menu_name, $parameters);
    $tree = $this->setActiveTrailMenu($tree_ma, $active_trail);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menutree->transform($tree, $manipulators);

    return $this->buildMenu($tree);
  }

  /**
   *
   */
  protected function buildMenu(array $tree) {
    $tree_access_cacheability = new CacheableMetadata();
    $tree_link_cacheability = new CacheableMetadata();
    $items = $this->buildItems($tree, $tree_access_cacheability, $tree_link_cacheability);

    $build = [];

    // Apply the tree-wide gathered access cacheability metadata and link
    // cacheability metadata to the render array. This ensures that the
    // rendered menu is varied by the cache contexts that the access results
    // and (dynamic) links depended upon, and invalidated by the cache tags
    // that may change the values of the access results and links.
    // $tree_cacheability = $tree_access_cacheability->merge($tree_link_cacheability);
    // $tree_cacheability->applyTo($build);
    if ($items) {
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Get the menu name from the last link.
      $item = end($items);
      $link = $item['original_link'];
      $menu_name = $link->getMenuName();
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme'] = 'menu__' . strtr($menu_name, '-', '_');
      $build['#items'] = $items;
      // Set cache tag.
      // $build['#cache']['tags'][] = 'config:system.menu.' . $menu_name;.
    }

    return $build;
  }

  /**
   * Builds the #items property for a menu tree's renderable array.
   *
   * Helper function for ::build().
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   A data structure representing the tree, as returned from
   *   MenuLinkTreeInterface::load().
   * @param \Drupal\Core\Cache\CacheableMetadata &$tree_access_cacheability
   *   Internal use only. The aggregated cacheability metadata for the access
   *   results across the entire tree. Used when rendering the root level.
   * @param \Drupal\Core\Cache\CacheableMetadata &$tree_link_cacheability
   *   Internal use only. The aggregated cacheability metadata for the menu
   *   links across the entire tree. Used when rendering the root level.
   *
   * @return array
   *   The value to use for the #items property of a renderable menu.
   *
   * @throws \DomainException
   */
  protected function buildItems(array $tree, CacheableMetadata &$tree_access_cacheability, CacheableMetadata &$tree_link_cacheability) {
    $items = [];
    $i = 1;
    foreach ($tree as $data) {
      $i++;
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $data->link;
      // Generally we only deal with visible links, but just in case.
      if (!$link->isEnabled()) {
        continue;
      }

      if ($data->access !== NULL && !$data->access instanceof AccessResultInterface) {
        throw new \DomainException('MenuLinkTreeElement::access must be either NULL or an AccessResultInterface object.');
      }

      // Gather the access cacheability of every item in the menu link tree,
      // including inaccessible items. This allows us to render cache the menu
      // tree, yet still automatically vary the rendered menu by the same cache
      // contexts that the access results vary by.
      // However, if $data->access is not an AccessResultInterface object, this
      // will still render the menu link, because this method does not want to
      // require access checking to be able to render a menu tree.
      if ($data->access instanceof AccessResultInterface) {
        $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($data->access));
      }

      // Gather the cacheability of every item in the menu link tree. Some links
      // may be dynamic: they may have a dynamic text (e.g. a "Hi, <user>" link
      // text, which would vary by 'user' cache context), or a dynamic route
      // name or route parameters.
      $tree_link_cacheability = $tree_link_cacheability->merge(CacheableMetadata::createFromObject($data->link));

      // Only render accessible links.
      if ($data->access instanceof AccessResultInterface && !$data->access->isAllowed()) {
        continue;
      }

      $class = [''];
      // Set a class for the <li>-tag. Only set 'expanded' class if the link
      // also has visible children within the current tree.
      if ($data->hasChildren && !empty($data->subtree)) {
        $class[] = '';
      }
      elseif ($data->hasChildren) {
        $class[] = '';
      }
      // Set a class if the link is in the active trail.
      if ($data->inActiveTrail) {
        $class[] = 'active';
      }

      // Note: links are rendered in the menu.html.twig template; and they
      // automatically bubble their associated cacheability metadata.
      $element = [];
      $element['attributes'] = new Attribute();
      $element['attributes']['class'] = $class;
      $element['data_index'] = $i;
      $element['data_icon'] = 'fa-plane';
      $element['title'] = $link->getTitle();
      $element['description'] = $link->getDescription();
      $element['url'] = $link->getUrlObject();
      $element['url']->setOption('set_active_class', TRUE);
      $element['below'] = $data->subtree ? $this->buildItems($data->subtree, $tree_access_cacheability, $tree_link_cacheability) : [];
      if (isset($data->options)) {
        $element['url']->setOptions(NestedArray::mergeDeep($element['url']->getOptions(), $data->options));
      }
      $element['original_link'] = $link;
      // Index using the link's unique ID.
      $items[$link->getPluginId()] = $element;
    }
    // kint($items);
    return $items;
  }

}
