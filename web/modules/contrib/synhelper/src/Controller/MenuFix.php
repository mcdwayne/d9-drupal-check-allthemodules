<?php

namespace Drupal\synhelper\Controller;

/**
 * @file
 * Contains \Drupal\synapse\Controller\Page.
 */
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\NodeType;

/**
 * Controller routines for page example routes.
 */
class MenuFix extends ControllerBase {

  /**
   * Page Callback.
   */
  public static function editor() {
    $otvet = "";
    $menu = \Drupal::entityTypeManager()->getStorage('menu')->load('main');

    $menu_tree_parameters = new MenuTreeParameters();
    $menu_tree = \Drupal::menuTree()->load('editor', $menu_tree_parameters);

    foreach ($menu_tree as $key => $link) {
      // Добавить -- сделать раскрытым.
      if ($key == 'synmini.add') {
        self::nodeAdd();
      }
      // R: synmini.menu -- скрыть .
      if ($key == 'synmini.menu') {
        self::menuMain();
        self::hideSynminiMenu();
      }
    }
    // Очистим кэш меню.
    self::truncate();
    return $otvet;
  }

  /**
   * F menuMain.
   */
  public static function menuMain() {
    $otvet = "";
    $link = self::getSynminiMenu();
    $exist = self::menuLinkCheck();
    if (!$exist) {
      // Добавим пункт меню.
      $menu_link = MenuLinkContent::create([
        'title' => 'Меню',
        'link' => ['uri' => 'internal:/admin/structure/menu/manage/main'],
        'menu_name' => 'editor',
        'weight' => $link['weight'],
        'expanded' => TRUE,
      ]);
      $menu_link->save();
    }
    return $otvet;
  }

  /**
   * F nodeAdd.
   */
  public static function nodeAdd() {
    $otvet = "";
    // Раскроем пункт меню.
    self::expandSynminiAdd();
    // Все типы материала.
    $types = self::getNodeTypes();
    foreach ($types as $type => $name) {
      $link = self::getMenuLink($type);
      if (!$link) {
        // Добавим пункт меню.
        $menu_link = MenuLinkContent::create([
          'title' => $name,
          'link' => ['uri' => 'internal:/node/add/' . $type],
          'menu_name' => 'editor',
          'parent' => 'synmini.add',
          'expanded' => TRUE,
        ]);
        $menu_link->save();
      }
    }

    return TRUE;
  }

  /**
   * F getMenuLink.
   */
  public static function menuLinkCheck() {
    $db = \Drupal::database();
    $query = $db->select('menu_link_content_data', 'm');
    $query->fields('m', ['id', 'title', 'link__uri']);
    $query->condition('link__uri', 'internal:/admin/structure/menu/manage/main');
    $data = $query->execute()->fetchAllAssoc('id', 'title', 'link__uri');
    return array_shift($data);
  }

  /**
   * F getMenuLink.
   */
  public static function getMenuLink($type) {
    $db = \Drupal::database();
    $query = $db->select('menu_link_content_data', 'm');
    $query->fields('m', ['id', 'title', 'link__uri']);
    $query->condition('link__uri', 'internal:/node/add/' . $type);
    $query->condition('parent', 'synmini.add');
    $data = $query->execute()->fetchAllAssoc('id', 'title', 'link__uri');
    return array_shift($data);
  }

  /**
   * F getNodeTypes.
   */
  public static function getNodeTypes() {
    $node_types = NodeType::loadMultiple();
    $types = [];
    foreach ($node_types as $node_type) {
      $ntype = $node_type->toArray();
      $types[$ntype['type']] = $ntype['name'];
    }
    return $types;
  }

  /**
   * F hideSynminiMenu.
   */
  public static function expandSynminiAdd() {
    // Скроем старый пункт.
    $query = \Drupal::database()->update('menu_tree');
    $query->fields(['expanded' => 1]);
    $query->condition('id', 'synmini.add');
    $query->condition('menu_name', 'editor');
    $query->execute();
  }

  /**
   * F getSynminiMenu.
   */
  public static function getSynminiMenu() {
    $db = \Drupal::database();
    $query = $db->select('menu_tree', 'm');
    $query->fields('m', ['menu_name', 'id', 'enabled', 'weight']);
    $query->condition('menu_name', 'editor');
    $query->condition('id', 'synmini.menu');
    $data = $query->execute()->fetchAllAssoc('id', 'enabled', 'weight');
    return array_shift($data);
  }

  /**
   * F hideSynminiMenu.
   */
  public static function hideSynminiMenu() {
    // Скроем старый пункт.
    $query = \Drupal::database()->update('menu_tree');
    $query->fields(['enabled' => 0]);
    $query->condition('id', 'synmini.menu');
    $query->condition('menu_name', 'editor');
    $query->execute();
  }

  /**
   * Truncate cache_menu.
   */
  public static function truncate() {
    $query = \Drupal::database()->truncate('cache_menu');
    $query->execute();

    $query = \Drupal::database()->select('config', 'c');
    $query->fields('c', ['name', 'data']);
    $query->condition('name', 'core.menu.static_menu_link_overrides');
    $data = $query->execute()->fetchAllAssoc('data');
    foreach ($data as $key => $value) {
      $str = $value->data;
    }

    $data = unserialize($str);
    $data['definitions']['synmini__add']['expanded'] = TRUE;
    $data['definitions']['synmini__menu']['enabled'] = FALSE;

    $query = \Drupal::database()->update('config');
    $query->fields(['data' => serialize($data)]);
    $query->condition('name', 'core.menu.static_menu_link_overrides');
    $query->execute();

  }

}
