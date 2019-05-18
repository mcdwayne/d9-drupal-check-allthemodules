<?php

namespace Drupal\monster_menus\MMCreatePath;

use Drupal\monster_menus\Constants;

class MMCreatePathCat implements MMCreatePathInterface {

  public
    $alias = '',
    $comment = 0,
    $default_mode = Constants::MM_PERMS_READ,
    $flags = '',
    $hidden = FALSE,
    $hover = '',
    $max_depth = -1,
    $max_parents = -1,
    $menu_start = -1,
    $mmtid,
    $name = '',
    $no_update,
    $node_info = 3,
    $perms,
    $previews = 0,
    $reset,
    $rss = 0,
    $theme = '',
    $type = 'cat',
    $uid,
    $weight = 0;
  public $cascaded = [
    'allow_reorder' => -1,
    'allowed_themes' => NULL,
    'allowed_node_types' => NULL,
    'hide_menu_tabs' => -1,
  ];

  public function __construct($arr) {
    foreach ($arr as $key => $val) {
      if ($key == 'cascaded') {
        foreach ($val as $ckey => $cval) {
          $this->cascaded[$ckey] = $cval;
        }
      }
      else {
        $this->$key = $val;
      }
    }
  }

}