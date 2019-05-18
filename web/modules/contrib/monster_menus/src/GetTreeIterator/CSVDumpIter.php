<?php
namespace Drupal\monster_menus\GetTreeIterator;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\GetTreeIterator;

class CSVDumpIter extends GetTreeIterator {

  /**
   * Database Service Object.
   *
   * @var Connection
   */
  protected $database;

  protected $fp;

  /**
   * Constructs a CSVDumpIter object.
   */
  public function __construct() {
    $this->fp = fopen('php://output', 'w');
    fputcsv($this->fp, array(
      'visname', 'mmtid', 'level', 'name', 'alias', 'default_mode', 'owner', 'theme',
      'alw_theme', 'alw_type', 'hidden', 'groups_w', 'users_w', 'groups_a',
      'users_a', 'groups_u', 'users_u', 'groups_r', 'users_r', 'flags', 'block'));
    $this->database = Database::getConnection();
  }

  /**
   * {@inheritdoc}
   */
  public function iterate($item) {
    $visname = str_repeat('>', $item->level) . ' ' . $item->name;

    $allowed_themes = array();
    $allowed_node_types = array();
    $result = $this->database->select('mm_cascaded_settings', 's')
      ->fields('s')
      ->condition('mmtid', $item->mmtid)
      ->condition('data_type', array('allowed_themes', 'allowed_node_types'), 'IN')
      ->execute();
    foreach ($result as $r) {
      if ($r->data_type == 'allowed_themes') {
        $allowed_themes[] = $r->data;
      }
      elseif ($r->data_type == 'allowed_node_types') {
        $allowed_node_types[] = $r->data;
      }
    }

    $groups = $users = array(
      Constants::MM_PERMS_READ  => array(),
      Constants::MM_PERMS_WRITE => array(),
      Constants::MM_PERMS_SUB   => array(),
      Constants::MM_PERMS_APPLY => array(),
    );
    $select = $this->database->select('mm_tree', 't');
    $select->join('mm_tree_access', 'a', 'a.mmtid=t.mmtid');
    $select->leftJoin('mm_tree', 't2', 'a.gid=t2.mmtid');
    $result = $select->fields('t2', array('mmtid', 'name'))
      ->fields('a', array('mode'))
      ->condition('t2.mmtid', '0', '>=')
      ->condition('a.mmtid', $item->mmtid)
      ->orderBy('t2.name')
      ->execute();
    foreach ($result as $r) {
      $groups[$r->mode][$r->mmtid] = $r->name;
    }

    $select = $this->database->select('mm_tree', 't');
    $select->join('mm_tree_access', 'a', 'a.mmtid=t.mmtid');
    $result = $select->fields('a', array('mode', 'gid'))
      ->condition('a.gid', '0', '<')
      ->condition('a.mmtid', $item->mmtid)
      ->execute();
    foreach ($result as $r) {
      $u = mm_content_get_users_in_group($r->gid, NULL, TRUE, 5);
      if (!is_null($u)) $users[$r->mode] = $u;
    }

    fputcsv($this->fp, array($visname, $item->mmtid, $item->level, $item->name,
                             $item->alias, $item->default_mode, $item->uid, $item->theme,
                             join(', ', $allowed_themes), join(', ', $allowed_node_types), $item->hidden,
                             $this->dump($groups[Constants::MM_PERMS_WRITE]), $this->dump($users[Constants::MM_PERMS_WRITE]),
                             $this->dump($groups[Constants::MM_PERMS_SUB]), $this->dump($users[Constants::MM_PERMS_SUB]),
                             $this->dump($groups[Constants::MM_PERMS_APPLY]), $this->dump($users[Constants::MM_PERMS_APPLY]),
                             $this->dump($groups[Constants::MM_PERMS_READ]), $this->dump($users[Constants::MM_PERMS_READ]),
                             join(',', array_keys($item->flags)), $item->bid));

    return 1;
  }

  protected function dump($arr) {
    $out = array();
    foreach ($arr as $id => $name) {
      $out[] = $id ? "$name [$id]" : $name;
    }
    return join(', ', $out);
  }

}
