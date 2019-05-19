<?php

namespace Drupal\simple_mobile_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
/**
 * Provides a Simple Mobile Menu block.
 *
 * @Block(
 *   id = "SimpleMenu_block",
 *   admin_label = @Translation("Simple Mobile Menu Block"),
 * )
 */
class SimpleMenuBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $simple_mobile_menu_config = \Drupal::config('simple_mobile_menu.settings')->get('menu_name');
    $block_config = [];
    $block_config['menu_name'][] = $simple_mobile_menu_config;
    $block_config                = ['menu_name', $simple_mobile_menu_config];
    // Get menu tree.
    $tree = simple_mobile_menu_build_tree($block_config);

    // build menu class.
   $tree = $this->_build_menu_style($tree, $block_config);

    $library = [];
    $library[] = 'simple_mobile_menu/simple_mobile_menu';

    return [
      '#theme'       => 'simple_mobile_menu',
      '#attached'    => [
                    'library' => $library,
                  ],
      '#menu_output' => drupal_render($tree)
      
    ];
 
  }

  // Leave an empty line here.



/**
   * // add 'menuparent' class.
   *
   * @param $items
   *
   * @return mixed
   */
  public function _build_sub_menu_menuparent($items) {
    foreach ($items as $k => &$item) {
       
      if ($item['below']) {
        $item['attributes']->addClass('has-child');
        $item['below'] = $this->_build_sub_menu_menuparent($item['below']);
      }
    }
    return $items;
  }

  /**
   * add class to smm menus.
   *
   * @param $tree
   * @param $block_config
   *
   * @return mixed
   */
  public function _build_menu_style($tree, $block_config) {
    // add default class.
    $tree['#attributes']['class'][] = 'mobile_menu';
   // $tree['#attributes']['class'][] = 'smm-menu-' . $block_config['menu_name'];

    // add 'menuparent' class.
    $tree['#items'] = $this->_build_sub_menu_menuparent($tree['#items']);
    return $tree;
  }

  }