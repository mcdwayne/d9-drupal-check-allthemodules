<?php

namespace Drupal\we_megamenu;

use Drupal\Core\Menu\MenuTreeParameters;

class WeMegaMenuBuilder {
  /**
   * Get menu tree we_megamenu.
   *
   * @param string $menu_name
   *   Public static function getMenuTree menu_name.
   * @param array $items
   *   Public static function getMenuTree items.
   * @param int $level
   *   Public static function getMenuTree level.
   *
   * @return array
   *   Public static function getMenuTree array.
  */
  public static function getMenuTree($menu_name, $items = [], $level = 0) {
    $result = [];
    if ($level == 0) {
      $menu_active_trail = \Drupal::service('menu.active_trail')->getActiveTrailIds($menu_name);
      $menu_tree_parameters = (new MenuTreeParameters)->setActiveTrail($menu_active_trail)->onlyEnabledLinks();
      $tree = \Drupal::menuTree()->load($menu_name, $menu_tree_parameters);
      foreach ($tree as $item) {
        $route_name = $item->link->getPluginDefinition()['route_name'];
        $result[] = [
          'derivativeId' => $item->link->getDerivativeId(),
          'title' => $item->link->getTitle(),
          'level' => $level,
          'description' => $item->link->getDescription(),
          'weight' => $item->link->getWeight(),
          'url' => $item->link->getUrlObject()->toString(),
          'subtree' => self::getMenuTree($menu_name, $item, $level + 1),
          'route_name' => $route_name,
          'in_active_trail' => $item->inActiveTrail,
          'plugin_id' => $item->link->getPluginId(),
        ];
      }
    }
    else {
      if ($items->hasChildren) {
        foreach ($items->subtree as $key_item => $item) {
          $route_name = $item->link->getPluginDefinition()['route_name'];
          $result[] = [
            'derivativeId' => $item->link->getDerivativeId(),
            'title' => $item->link->getTitle(),
            'level' => $level,
            'description' => $item->link->getDescription(),
            'weight' => $item->link->getWeight(),
            'url' => $item->link->getUrlObject()->toString(),
            'subtree' => self::getMenuTree($menu_name, $item, $level + 1),
            'route_name' => $route_name,
            'in_active_trail' => $item->inActiveTrail,
            'plugin_id' => $item->link->getPluginId(),
          ];
        }
      }

    }

    return $result;

  }

  /**
   * Get menu tree sorted by weight ascending.
   *
   * @param string $menu_name
   *   Public static function getMenuTreeOrder menu_name.
   * @param array $items
   *   Public static function getMenuTreeOrder items.
   * @param int $level
   *   Public static function getMenuTreeOrder level.
   *
   * @return array
   *   Public static function getMenuTreeOrder array.
  */
  public static function getMenuTreeOrder($menu_name, $items = [], $level = 0) {
    $menu = self::getMenuTree($menu_name, $items = [], $level = 0);
    return self::sortMenuDeep($menu);
  }

  /**
   * Sort list child menu.
   *
   * @param string $menu
   *   Public static function sortMenuDeep menu.
   *
   * @return array
   *   Public static function sortMenuDeep array.
  */
  public static function sortMenuDeep($menu) {
    if (is_array($menu)) {
      $menu = self::sortMenu($menu);
      foreach ($menu as $key_item => $item) {
        if (isset($item['subtree'])) {
          $menu[$key_item]['subtree'] = self::sortMenuDeep($item['subtree']);
        }
      }
      return $menu;
    }
    return [];
  }

  /**
   * Sort menu by weight.
   *
   * @param string $menu
   *   Public static function sortMenu string menu.
   *
   * @return array
   *   Public static function sortMenu array.
   */
  public static function sortMenu($menu) {
    for ($i = 0; $i < count($menu); $i++) {
      for ($j = $i + 1; $j < count($menu); $j++) {
        if ($menu[$i]['weight'] > $menu[$j]['weight']) {
          $menu_tmp = $menu[$i];
          $menu[$i] = $menu[$j];
          $menu[$j] = $menu_tmp;
        }
      }
    }
    return $menu;
  }

  /**
   * Get menu items list.
   *
   * @return array
   *   Public static function getMenuItems array.
   */
  public static function getMenuItems($menu_name, $items = [], $level = 0, &$result = []) {
    if ($level == 0) {
      $menu_active_trail = \Drupal::service('menu.active_trail')->getActiveTrailIds($menu_name);
      $menu_tree_parameters = (new MenuTreeParameters)->setActiveTrail($menu_active_trail)->onlyEnabledLinks();
      $tree = \Drupal::menuTree()->load($menu_name, $menu_tree_parameters);
      foreach ($tree as $item) {
        $route_name = $item->link->getPluginDefinition()['id'];
        $uuid = ($route_name == 'standard.front_page') ? $item->link->getPluginDefinition()['id'] : $item->link->getDerivativeId();

        $result[$uuid] = [];
        if ($item->hasChildren) {
          foreach ($item->subtree as $key_menu => $menu) {
            $result[$uuid][] = $menu->link->getDerivativeId();
          }
        }
        self::getMenuItems($menu_name, $item, $level + 1, $result);
      }
    }
    else {
      if ($items->hasChildren) {
        foreach ($items->subtree as $key_item => $item) {
          $route_name = $item->link->getPluginDefinition()['id'];
          $uuid = ($route_name == 'standard.front_page') ? $item->link->getPluginDefinition()['id'] : $item->link->getDerivativeId();
          $result[$uuid] = [];
          if ($item->hasChildren) {
            foreach ($item->subtree as $key_menu => $menu) {
              $result[$uuid][] = $menu->link->getDerivativeId();
            }
          }
          self::getMenuItems($menu_name, $item, $level + 1, $result);
        }
      }
    }
    return $result;
  }


  /**
   * Get all block of drupal.
   *
   * @staticvar array $_list_blocks_array
   *
   * @return array
   *   Public static function getAllBlocks array list_blocks_array.
   */
  public static function getAllBlocks() {
    static $_list_blocks_array = [];
    if (empty($_list_blocks_array)) {
      $theme_default = \Drupal::config('system.theme')->get('default');
      $block_storage = \Drupal::entityManager()->getStorage('block');
      $entity_ids = $block_storage->getQuery()->condition('theme', $theme_default)->execute();
      $entities = $block_storage->loadMultiple($entity_ids);
      $_list_blocks_array = [];
      foreach ($entities as $block_id => $block) {
        if ($block->get('settings')['provider'] != 'we_megamenu') {
          $_list_blocks_array[$block_id] = $block->label();
        }
      }
      asort($_list_blocks_array);
    }
    return $_list_blocks_array;
  }

  /**
   * Check router exists.
   *
   * @param string $name as router name
   *   Public static function routeExists string name.
   * @return int
   *   Public static function routeExists int.
   */
  public static function routeExists($name) {
    $route_provider = \Drupal::service('router.route_provider');
    $route_provider = $route_provider->getRoutesByNames([$name]);
    return count($route_provider);
  }

  /**
   * Render drupal block.
   *
   * @param string $bid
   *   Public static function renderBlock bid.
   * @param bool $title_enable
   *   Public static function renderBlock title_enable.
   * @param string $section
   *   Public static function renderBlock section.
   *
   * @return string [markuphtml]
   *   Public static function renderBlock string.
   */
  public static function renderBlock($bid, $title_enable = TRUE, $section = '') {
    $html = '';
    if ($bid && !empty($bid)) {
      $block = \Drupal\block\Entity\Block::load($bid);
      if (isset($block) && !empty($block)) {
        $title = $block->label();
        $block_content = \Drupal::entityManager()
          ->getViewBuilder('block')
          ->view($block);

        if ($section == 'admin') {
          $html .= '<span class="close icon-remove" title="Remove this block">&nbsp;</span>';
        }

        $html .= '<div class="type-of-block">';
        $html .= '<div class="block-inner">';
        $html .= $title_enable ? '<h2>' . $title . '</h2>' : '';
        $html .= render($block_content);
        $html .= '</div>';
        $html .= '</div>';
      }
      else {
        $html = '<p><b>Warning:</b> <i>Broken/Missing block</i></p>';
      }
    }
    return $html;
  }

  /**
   * Render Drupal 8 Mega Menu blocks.
   *
   * @param string $menu_name
   *   Public static function renderWeMegaMenuBlock menu_name.
   * @param string $theme
   *   Public static function renderWeMegaMenuBlock theme
   *
   * @return array
   *   Public static function renderWeMegaMenuBlock array.
   */
  public static function renderWeMegaMenuBlock($menu_name, $theme) {
    return [
      '#theme' => 'we_megamenu_frontend',
      '#block_theme' => $theme,
      '#menu_name' => $menu_name,
      '#section' => 'admin',
      '#blocks' => WeMegaMenuBuilder::getAllBlocks(),
    ];
  }

  /**
   * Load config Drupal 8 Mega Menu.
   *
   * @param string $menu_name
   *   Public static function loadConfig menu_name.
   * @param string $theme
   *   Public static function loadConfig theme.
   *
   * @return string || bool
   *   Public static function loadConfig string.
   */
  public static function loadConfig($menu_name = '', $theme = '') {
    if (!empty($menu_name) && !empty($theme)) {
      $query = \Drupal::database()->select('we_megamenu', 'km');
      $query->addField('km', 'data_config');
      $query->condition('km.menu_name', $menu_name);
      $query->condition('km.theme', $theme);
      $query->range(0, 1);
      $result = $query->execute()->fetchField();
      return json_decode($result);
    }
    return FALSE;
  }

  /**
   * Save config Drupal 8 Mega Menu.
   *
   * @param string $menu_name
   *   Public static function saveConfig menu_name.
   * @param string $theme
   *   Public static function saveConfig theme.
   * @param object $data_config
   *   Public static function saveConfig data_config.
   *
   * @return object
   *   Public static function saveConfig string.
   */
  public static function saveConfig($menu_name, $theme, $data_config) {
    $result = \Drupal::service('database')
      ->merge('we_megamenu')
      ->key([
        'menu_name' => $menu_name,
        'theme' => $theme
      ])
      ->fields([
        'data_config' => $data_config,
      ])->execute();
    $menu_config = WeMegaMenuBuilder::loadConfig($menu_name, $theme);
  }

  /**
   * Insert new menu item.
   *
   * @param string $key_menu
   *   Public static function menuItemInsert key_menu.
   * @param object $menu_config
   *   Public static function menuItemInsert menu_config.
   * @param object $menu_item
   *   Public static function menuItemInsert menu_item.
   * @param object $menu_child_item
   *   Public static function menuItemInsert menu_child_item.
   */
  public static function menuItemInsert($key_menu, $menu_config, $menu_item, $menu_child_item) {
    if (isset($menu_child_item['col_content']) && isset($menu_child_item['col_cfg'])) {
      $tmp_col_content = $menu_child_item['col_content'];
      $tmp_col_cfg = $menu_child_item['col_cfg'];
      if (isset($menu_item->rows_content)) {
        $row_count = 0;
        $col_count = 0;
        if (count($menu_item->rows_content)) {
          $li_flag = FALSE;
          $rows_content = $menu_item->rows_content;
          foreach ($rows_content as $key_rows => $rows) {
            if (is_array($rows)) {
              foreach ($rows as $key_row_col => $row) {
                if (isset($row->col_content)) {
                  $cols = $row->col_content;
                  if (is_array($cols)) {
                    foreach ($cols as $key_col => $col) {
                      if (isset($col->mlid)) {
                        $row_count = $key_rows;
                        $col_count = $key_row_col;
                        $li_flag = TRUE;
                      }
                    }
                  }
                }
              }
            }
          }

          if (!$li_flag) {
            $bk_items = $menu_config->menu_config->{$key_menu}->rows_content;
            $menu_config->menu_config->{$key_menu}->rows_content = [];
            $menu_config->menu_config->{$key_menu}->rows_content[0] = [];
            foreach ($bk_items as $key => $value) {
              $menu_config->menu_config->{$key_menu}->rows_content[] = $value;
            }
          }
        }

        $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_content[] = $tmp_col_content;
        $items_validate_serialize = array_map("serialize", $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_content);
        $items_validate_unique = array_unique($items_validate_serialize);
        $items_validate = array_map("unserialize", $items_validate_unique);
        $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_content = $items_validate;
        if (!isset($menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_config)) {
          $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_config = $tmp_col_cfg;
        }
      }
    }
  }

  /**
   * Delete menu item.
   *
   * @param object $menu_config
   *   Public static function menuItemInsert menu_config.
   * @param string $menu_uuid
   *   Public static function menuItemInsert menu_uuid.
   */
  public static function menuItemDelete($menu_config, $menu_uuid) {
    if (isset($menu_config->menu_config)) {
      $menus = $menu_config->menu_config;
      if (isset($menus) && is_array($menus)) {
        foreach ($menus as $key_menu => $menu) {
          # Remove as root
          if ($key_menu == $menu_uuid) {
            if (isset($menu_config->menu_config[$menu_uuid])) {
              unset($menu_config->menu_config[$menu_uuid]);
            }
          }

          # Remove as leaf
          if (isset($menu->rows_content)) {
            $rows_content = $menu->rows_content;
            foreach ($rows_content as $key_rows => $rows) {
              if (is_array($rows)) {
                foreach ($rows as $key_row_col => $row) {
                  if (isset($row->col_content)) {
                    $cols = $row->col_content;
                    if (is_array($cols)) {
                      foreach ($cols as $key_col => $col) {
                        if (isset($col->mlid) && $col->mlid == $menu_uuid) {
                          unset($menu_config->menu_config->{$key_menu}->rows_content[$key_rows][$key_row_col]->col_content[$key_col]);
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      } 
    }
  }

  /**
   * Order display menu item.
   *
   * @param object $menu_config
   *   Public static function menuItemInsert menu_config.
   */
  public static function orderMenuItems($menu_config) {
    if (isset($menu_config->menu_config)) {
      $menus = $menu_config->menu_config;
      if (isset($menus) && is_object($menus)) {
        foreach ($menus as $key_menu => $menu) {
          if (isset($menu->rows_content)) {
            $rows_content = $menu->rows_content;
            foreach ($rows_content as $key_rows => $rows) {
              $positions = [];
              $list_menu_items = [];
              $list_mega_menu_items = [];
              $row_count = 0;
              $col_count = 0;
              if (is_array($rows)) {
                foreach ($rows as $key_row_col => $row) {
                  if (isset($row->col_content)) {
                    $cols = $row->col_content;
                    if (is_array($cols) && isset(reset($cols)->mlid)) {
                      $row_count = $key_rows;
                      $col_count = $key_row_col;
                      $positions[] = $row_count . '-' . $col_count . '-' . count($cols);
                      foreach ($cols as $key_col => $col) {
                        $menu_item = \Drupal::entityManager()
                          ->getStorage('menu_link_content')
                          ->loadByProperties(['uuid' => $col->mlid]);
                        if (is_array($menu_item)) {
                          $menu_item = reset($menu_item);
                          if (method_exists($menu_item, 'get')) {
                            $list_menu_items[] =  [
                              'derivativeId' => $menu_item->get('uuid')->getString(),
                              'title' => $menu_item->get('title')->getString(),
                              'weight' => $menu_item->get('weight')->getString(),
                            ];
                            $list_mega_menu_items[] = $col;
                          }
                        }
                      }
                    }
                  }
                }
              }

              if (!sizeof($positions)) {
                continue;
              }

              $list_menu_items = WeMegaMenuBuilder::sortMenu($list_menu_items);
              foreach ($positions as $key_position => $position) {
                $pos_params = explode('-', $position);
                $row = $pos_params[0];
                $col = $pos_params[1];
                $size = $pos_params[2];

                if ($size >= 0) {
                  if (is_array($list_menu_items)) {
                    $list_item = array_slice($list_menu_items, 0, $size);
                    if (is_array($list_item)) {
                      $list_menu_items = array_map('unserialize', array_diff_assoc(array_map('serialize', $list_menu_items), array_map('serialize', $list_item)));

                      $menu_config->menu_config->{$key_menu}->rows_content[$row][$col]->col_content = [];
                      foreach ($list_item as $key_menu_item => $menu_itemnew) {
                        foreach ($list_mega_menu_items as $key_mega_menu => $mega_menu_item) {
                          if (isset($mega_menu_item->mlid)) {
                            if ($menu_itemnew['derivativeId'] == $mega_menu_item->mlid) {
                              $menu_config->menu_config->{$key_menu}->rows_content[$row][$col]->col_content[] = $mega_menu_item;
                              $items_validate_serialize = array_map("serialize", $menu_config->menu_config->{$key_menu}->rows_content[$row][$col]->col_content);
                              $items_validate_unique = array_unique($items_validate_serialize);
                              $items_validate = array_map("unserialize", $items_validate_unique);
                              $menu_config->menu_config->{$key_menu}->rows_content[$row][$col]->col_content = $items_validate;
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Drag-Drop menu item insert.
   *
   * @param string $menu_name
   *   Public static function menuItemInsert menu_name.
   * @param string $theme_name
   *   Public static function menuItemInsert theme_name.
   * @param object $menu_config
   *   Public static function menuItemInsert menu_config.
   * @param object $child_item
   *   Public static function menuItemInsert child_item.
   */
  public static function dragDropMenuItems($menu_name, $theme_name = '', $menu_config, $child_item) {
    $list_menu_items = WeMegaMenuBuilder::getMenuItems($menu_name);
    if (isset($child_item['col_content']) && isset($child_item['col_cfg']) && isset($menu_config->menu_config)) {
      $tmp_col_content = $child_item['col_content'];
      $tmp_col_cfg = $child_item['col_cfg'];
      $menu_items = $menu_config->menu_config;
      foreach ($list_menu_items as $uuid => $childs) {
        $uuid = ($uuid == 'standard.front_page') ? base_path() : $uuid;
        foreach ($menu_items as $key_menu => $menu_item) {
          if (isset($menu_item->rows_content)) {
            $rows_content = $menu_item->rows_content;
            if (count($rows_content)) {
              foreach ($rows_content as $key_rows => $rows) {
                if ($key_menu == $uuid) {
                  if (is_array($rows)) {
                    $list_mega_items = [];
                    $row_count = 0;
                    $col_count = 0;
                    foreach ($rows as $key_row_col => $row) {
                      if (isset($row->col_content)) {
                        $cols = $row->col_content;
                        if (is_array($cols)) {
                          foreach ($cols as $key_col => $col) {
                            if (isset($col->mlid)) {
                              $row_count = $key_rows;
                              $col_count = $key_row_col;

                              if (!in_array($col->mlid, $childs)) {
                                unset($menu_config->menu_config->{$key_menu}->rows_content[$key_rows][$key_row_col]->col_content[$key_col]);
                                if (!count($menu_config->menu_config->{$key_menu}->rows_content[$key_rows][$key_row_col]->col_content)) {
                                  unset($menu_config->menu_config->{$key_menu}->rows_content[$key_rows][$key_row_col]);
                                  if (!count($menu_config->menu_config->{$key_menu}->rows_content[$key_rows])) {
                                    unset($menu_config->menu_config->{$key_menu}->rows_content[$key_rows]);
                                  }
                                }
                              } else {
                                $list_mega_items[] = $col->mlid;
                              }
                            }
                          }
                        }
                      }
                    }

                    foreach ($childs as $key_child => $child_uuid) {
                      $child_uuid = ($child_uuid == 'standard.front_page') ? base_path() : $child_uuid;
                      if (!in_array($child_uuid, $list_mega_items)) {
                        $tmp_col_content->mlid = $child_uuid;
                        $list_mega_items[] = $child_uuid;
                        if (is_object($menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_content)) {
                          $tmp = clone $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count];
                          unset($menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]);
                          $menu_config->menu_config->{$key_menu}->rows_content[$row_count + 1][$col_count] = $tmp;
                        }
                        $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_content[] = $tmp_col_content;
                        $items_validate_serialize = array_map('serialize', $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_content);
                        $items_validate_unique = array_unique($items_validate_serialize);
                        $items_validate = array_map('unserialize', $items_validate_unique);
                        $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_content = $items_validate;
                        if (!isset($menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_config)) {
                          $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_config = $tmp_col_cfg;
                        }
                      }
                    }
                  }
                }
              }
            } else {
              if ($key_menu == $uuid) {
                foreach ($childs as $key_child => $child_uuid) {
                  $tmp_col_content->mlid = $child_uuid;
                  $menu_config->menu_config->{$key_menu}->rows_content[0][0]->col_content[] = $tmp_col_content;
                  $items_validate_serialize = array_map("serialize", $menu_config->menu_config->{$key_menu}->rows_content[0][0]->col_content);
                  $items_validate_unique = array_unique($items_validate_serialize);
                  $items_validate = array_map("unserialize", $items_validate_unique);
                  $menu_config->menu_config->{$key_menu}->rows_content[0][0]->col_content = $items_validate;
                  if (!isset($menu_config->menu_config->{$key_menu}->rows_content[0][0]->col_config)) {
                    $menu_config->menu_config->{$key_menu}->rows_content[0][0]->col_config = $tmp_col_cfg;
                  }
                }
              }
            }
          }
        }
      }

      // Remove duplicate items
      foreach ($menu_items as $key_menu => $menu_item) {
        if (isset($menu_item->rows_content)) {
          $rows_content = $menu_item->rows_content;
          if (count($rows_content)) {
            foreach ($rows_content as $key_rows => $rows) {
              if (is_array($rows)) {
                $list_mega_items = [];
                $row_count = 0;
                $col_count = 0;
                $flag = [];
                foreach ($rows as $key_row_col => $row) {
                  if (isset($row->col_content)) {
                    $cols = $row->col_content;
                    if (is_array($cols)) {
                      foreach ($cols as $key_col => $col) {
                        if (isset($col->mlid)) {
                          $row_count = $key_rows;
                          $col_count = $key_row_col;
                          if (isset($flag[$col->mlid])) {
                            $flag[$col->mlid] ++;
                            if ($flag[$col->mlid] > 0) {
                              if (isset($menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count])) {
                                $col_items_content = $menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count];
                                if (isset($col_items_content) && count($col_items_content->col_content)) {
                                  foreach ($col_items_content->col_content as $key_col_content => $c_content) {
                                    if (isset($c_content->mlid) && $c_content->mlid == $col->mlid) {
                                      unset($menu_config->menu_config->{$key_menu}->rows_content[$row_count][$col_count]->col_content[$key_col_content]);
                                    }
                                  }
                                }
                              }
                            }
                          } else {
                            $flag[$col->mlid] = 0;
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Get trail array.
   *
   * @return array
   *   Public static function buildPageTrail array.
   */
  public static function buildPageTrail($menu_items) {
    $trail = [];
    foreach ($menu_items as $key_item => $item) {
      $plugin_id = $item['plugin_id'];
      $check_is_front_page = \Drupal::service('path.matcher')->isFrontPage();
      $route_name = $item['route_name'];

      if ($route_name == '<front>' && $check_is_front_page) {
        $trail[$plugin_id] = $item;
      }
      elseif (isset($item['in_active_trail']) && $item['in_active_trail'] == 1) {
        $trail[$plugin_id] = $item;
      }

      if (isset($item['subtree']) && count($item['subtree'])) {
        $trail += self::buildPageTrail($item['subtree']);
      }
    }
    return $trail;
  }

  /**
   * Render all drupal view.
   */
  public static function renderView() {
    $entity_manager = \Drupal::entityManager();
    $views = $entity_manager->getStorage('view')->loadMultiple();
    foreach ($views as $key => $view) {
      $view = \Drupal\views\Views::getView($key);
      $a = $view->render();
      if ($a) {
        echo drupal_render($view);
        exit;
      }
    }
  }
}