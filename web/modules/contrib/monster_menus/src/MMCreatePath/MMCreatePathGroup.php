<?php

namespace Drupal\monster_menus\MMCreatePath;

use Drupal\monster_menus\Constants;

class MMCreatePathGroup implements MMCreatePathInterface {

  public
    $alias = '',
    $comment = 0,
    $default_mode,
    $flags = '',
    $hidden = FALSE,
    $hover = '',
    $mmtid,
    $name = '',
    $no_update,
    $perms,
    $reset,
    $rss = 0,
    $theme = '',
    $type = 'group',
    $uid,
    $weight = 0;
  public
    $qfield,
    $members,
    $qfrom,
    $vgroup;

  public function __construct($arr) {
    // Work around PHP's inability to assign a default value using concatenation
    // to a public class variable.
    $this->default_mode = Constants::MM_PERMS_APPLY . ',' . Constants::MM_PERMS_READ;
    foreach ($arr as $key => $val) {
      $this->$key = $val;
    }
  }

}