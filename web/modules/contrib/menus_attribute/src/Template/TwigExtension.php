<?php

namespace Drupal\menus_attribute\Template;

use Drupal\menus_attribute\StorageHelper;

/**
 * TwigExtension class returns menus attribute for each menu item and link.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * In this function we define a twig extension name.
   */
  public function getName() {
    return 'menus_attribute';
  }

  /**
   * In this function we can declare the extension function.
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('menus_attribute', [$this, 'menusAttribute']),
      new \Twig_SimpleFunction('test_menu', [$this, 'test']),
    ];
  }

  /**
   * Returns added attributes for list.
   */
  public function menusAttribute($plugin_id) {
    $instance = StorageHelper::instance();
    $attributes = NULL;
    if ($instance->exists($plugin_id)) {
      $data = $instance->getData($plugin_id);
      if ($data->link_id) {
        $attributes['link']['id'] = $data->link_id;
      }
      if ($data->link_name) {
        $attributes['link']['name'] = $data->link_name;
      }
      if ($data->link_title) {
        $attributes['link']['title'] = $data->link_name;
      }
      if ($data->link_rel) {
        $attributes['link']['rel'] = $data->link_rel;
      }
      if ($data->link_classes) {
        $attributes['link']['class'] = $data->link_classes;
      }
      if ($data->link_style) {
        $attributes['link']['style'] = $data->link_style;
      }
      if ($data->link_target) {
        $attributes['link']['target'] = $data->link_target;
      }
      if ($data->link_accesskey) {
        $attributes['link']['accesskey'] = $data->link_accesskey;
      }
      if ($data->item_id) {
        $attributes['item']['id'] = $data->item_id;
      }
      if ($data->item_classes) {
        $attributes['item']['class'] = $data->item_classes;
      }
      if ($data->item_style) {
        $attributes['item']['style'] = $data->item_style;
      }
    }
    return $attributes;
  }

}
