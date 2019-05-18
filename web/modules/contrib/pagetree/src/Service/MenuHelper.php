<?php
namespace Drupal\pagetree\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Static menu helper.
 *
 * Provides static methods for working with menus.
 */
class MenuHelper
{

    /**
     * Create a menu link for the given node id.
     *
     * @param int $nid
     * @param string $title
     * @param int $parentId
     * @return void
     */
    public static function createMenuLink($entity, $title, $parentEntry = '', $menu = 'main')
    {
        $linkDef = array(
            'title' => $title,
            'link' => array('uri' => 'entity:node/' . $entity->id()),
            'menu_name' => $menu,
            'weight' => 1000,
            'bundle' => 'menu_link_content',
            'expanded' => true,
        );
        if (!empty($parentId)) {
            $parentLinkItem = self::loadMenuLink($parentEntry);
            if ($parentLinkItem) {
                $linkDef['parent'] = $parentLinkItem->getPluginId();
                $linkDef['enabled'] = $parentLinkItem->isEnabled();
                if ($menu == '') {
                    $linkDef['menu_name'] = $parentLinkItem->getMenuName();
                }
            } else {
                if ($menu == '') {
                    $linkDef['menu_name'] = 'main';
                }
            }
        } else {
            if ($menu == '') {
                $linkDef['menu_name'] = 'main';
            }
        }
        $menuLink = MenuLinkContent::create($linkDef);
        $menuLink->save();
    }

    public static function loadMenuLink($uuid)
    {
        $menuLink = null;
        $uuid = str_replace('menu_link_content:', '', $uuid);
        if (!empty($uuid)) {
            $query = \Drupal::entityQuery('menu_link_content')
                ->condition('uuid', $uuid, 'LIKE');
            // if ($menu != '') {
            //     $query->condition('menu_name', $menu);
            // }
            $result = $query->execute();
            $menuLinkId = (!empty($result)) ? reset($result) : false;

            if ($menuLinkId) {
                $menuLink = MenuLinkContent::load($menuLinkId);
            }
        }

        return $menuLink;
    }

    /**
     * Get a menu link content entity by content type node id.
     *
     * @param int $nid
     * @param string $menu
     * @return MenuLinkContent
     */
    public static function getMenuLink($nid, $menu = 'main')
    {
        $menuLink = null;
        if (is_numeric($nid)) {
            $query = \Drupal::entityQuery('menu_link_content')
                ->condition('link.uri', '%node/' . $nid, 'LIKE');
            if ($menu != '') {
                $query->condition('menu_name', $menu);
            }
            $result = $query->execute();
            $menuLinkId = (!empty($result)) ? reset($result) : false;

            if ($menuLinkId) {
                $menuLink = MenuLinkContent::load($menuLinkId);
            }
        }
        return $menuLink;
    }

    /**
     * Set the parent of the given menu link using a node id.
     *
     * @param MenuLinkContent $menuLink
     * @param int $parentId
     * @param string $menu
     * @return void
     */
    public static function setParent($menuLink, $parentId, $menu = 'main')
    {
        if (is_numeric($parentId) && $parentId > -1) {
            $parentMenuLink = self::getMenuLink($parentId, $menu);
            if ($parentLinkItem) {
                $menuLink->parent->value = $parentMenuLink->getPluginId();
            } else {
                $menuLink->parent->value = '';
            }
        } else {
            $menuLink->parent->value = '';
        }
        $menuLink->save();
    }

    /**
     * Save a menu link and reorder the level.
     *
     * Takes the the current menulink content and it's position on the level.
     *
     * @param MenuLinkContent $current
     * @param int $position
     * @param string $menu
     * @return void
     */
    public static function saveAndReorder($current, $position, $menu = 'main')
    {
        $current->weight->value = $position * 10;
        $current->save();
        $parentId = $current->parent->value;
        self::reorder($parentId, $current, $menu);
    }

    /**
     * Reorder a menu level.
     *
     * Reorders the menu level with $parentId as parent.
     * Skips the entry given with $skip.
     *
     * @param string $parentId
     * @param MenuLinkContent $skip
     * @param string $menu
     * @return void
     */
    public static function reorder($parentId, $skip = null, $menu = 'main')
    {
        $query = \Drupal::entityQuery('menu_link_content')
            ->condition('menu_name', $menu);

        if (!empty($parentId)) {
            $query->condition('parent', $parentId);
        } else {
            $query->condition('parent', '', 'IS NULL');
        }
        if ($skip != null) {
            $query->condition('id', $skip->id(), "<>");
        }
        $query->sort('weight');
        $menuLinkIds = $query->execute();

        $menuLinks = MenuLinkContent::loadMultiple($menuLinkIds);
        $currentWeight = 10;
        foreach ($menuLinks as $menuLink) {
            if ($skip != null && $currentWeight == $skip->weight->value) {
                $currentWeight += 10;
            }
            $menuLink->weight->value = $currentWeight;
            $menuLink->save();
            $currentWeight += 10;
        }
    }

    public static function getRoot($id, $menu = 'main')
    {
        $menuLink = $current = self::getMenuLink($id, $menu);
        $i = 0;
        while ($menuLink->parent->value != '' && $i < 10) {
            $menuLink = self::loadMenuLink($menuLink->parent->value);
            $i++;
        }
        return $menuLink;
    }

    /**
     * Returns the menu tree.
     *
     * Returns the ordered, full menu tree, starting at the given root (or at the top, if omitted).
     *
     * @param string $menu The menu name.
     * @param integer $root The root plugin id.
     * @return MenuTree
     */
    public static function getMenuTree($menu = 'main', $root = '')
    {
        $menuLinkTree = \Drupal::menuTree();
        $parameters = new MenuTreeParameters();
        $parameters->setMinDepth(0)->setMaxDepth(99);
        if (!empty($root)) {
            $parameters->setRoot($root);
        }
        $manipulators = array(
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        );
        $tree = $menuLinkTree->load($menu, $parameters);
        $tree = $menuLinkTree->transform($tree, $manipulators);
        return $tree;
    }

    /**
     * Clears the menu cache.
     *
     * @param string $menu
     * @return void
     */
    public static function clearCache($menu = 'main')
    {
        Cache::invalidateTags(['config:system.menu.' . $menu]);
        \Drupal::service('plugin.manager.menu.link')->rebuild();
    }
}
