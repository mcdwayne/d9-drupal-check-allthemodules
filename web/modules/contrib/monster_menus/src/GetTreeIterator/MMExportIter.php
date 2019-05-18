<?php
namespace Drupal\monster_menus\GetTreeIterator;

use Drupal\Core\Database\Database;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\MMExportGroup;
use Drupal\monster_menus\MMExportCat;
use Drupal\monster_menus\MMImportExportException;
use Drupal\monster_menus\GetTreeIterator;
use Drupal\node\Entity\Node;

class MMExportIter extends GetTreeIterator {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public $nodes, $pages, $subrequest, $pool, $users, $parents, $aliases;
  private $include_nodes, $page_nodes;

  /**
   * Constructs an MMExportIter object.
   *
   * @param bool $include_nodes
   *   If TRUE, include node contents.
   */
  public function __construct($include_nodes) {
    $this->pages = $this->nodes = $this->parents = $this->aliases = $this->subrequest = $this->pool = $this->users = $this->page_nodes = array();
    $this->include_nodes = $include_nodes;
    $this->database = Database::getConnection();
  }

  /**
   * {@inheritdoc}
   */
  public function iterate($item) {
    // Skip recycle bins
    if ($item->perms[Constants::MM_PERMS_IS_RECYCLE_BIN]) {
      return -1;
    }
    if (!$item->perms[Constants::MM_PERMS_READ]) {
      throw new MMImportExportException('You do not have permission to read the page/group with mmtid=@mmtid.', array('@mmtid' => $item->mmtid));
    }
    // Create a new object and filter out unused fields.
    $compare = $item->perms[Constants::MM_PERMS_IS_GROUP] ? new MMExportGroup(array()) : new MMExportCat(array());
    $i = (object)array_intersect_key((array)$item, (array)$compare);
    unset($i->mmtid);

    if (!empty($item->bid)) {
      $i->menu_start = $item->bid;
    }

    $i->perms = array();
    // Groups
    $select = $this->database->select('mm_tree', 't');
    $select->join('mm_tree_access', 'a', 'a.mmtid = t.mmtid');
    $select->leftJoin('mm_tree', 't2', 'a.gid = t2.mmtid');
    $result = $select->fields('t2', array('mmtid', 'name'))
      ->fields('a', array('mode'))
      ->condition('t2.mmtid', '0', '>=')
      ->condition('a.mmtid', $item->mmtid)
      ->execute();
    foreach ($result as $r) {
      if ($r->mmtid == $item->mmtid) {
        $i->perms[$r->mode]['groups'][] = 'self';
      }
      else {
        $i->perms[$r->mode]['groups'][$r->mmtid] = $this->add_group($r->mmtid, $item->mmtid);
      }
    }

    // Individual users
    $select = $this->database->select('mm_tree', 't');
    $select->join('mm_tree_access', 'a', 'a.mmtid = t.mmtid');
    $result = $select->fields('a', array('mode', 'gid'))
      ->condition('a.gid', '0', '<')
      ->condition('a.mmtid', $item->mmtid)
      ->execute();
    foreach ($result as $r) {
      $i->perms[$r->mode]['users'] = array();
      foreach (mm_content_get_uids_in_group($r->gid) as $uid) {
        $i->perms[$r->mode]['users'][] = $this->uid2username($uid);
      }
    }

    // Owner
    $i->uid = $this->uid2username($i->uid);

    if ($item->perms[Constants::MM_PERMS_IS_GROUP]) {
      if ($i->vgroup = mm_content_is_vgroup($item->mmtid)) {
        // Virtual group
        $vquery = $this->database->query('SELECT qfrom, field FROM {mm_group} g INNER JOIN {mm_vgroup_query} v ON g.vgid = v.vgid WHERE g.gid = :mmtid', array(':mmtid' => $item->mmtid))->fetchObject();
        if ($vquery) {
          $i->qfrom = $vquery->qfrom;
          $i->qfield = $vquery->field;
        }
      }
      else {
        // Regular group
        $i->members = array();
        foreach (mm_content_get_uids_in_group($item->mmtid) as $uid) {
          $i->members[] = $this->uid2username($uid);
        }
      }
    }
    else {
      // Cascaded settings
      $result = $this->database->query('SELECT * FROM {mm_cascaded_settings} WHERE mmtid = :mmtid', array(':mmtid' => $item->mmtid));
      foreach ($result as $r) {
        if ($r->multiple) {
          if (!isset($i->cascaded) || !is_array($i->cascaded[$r->name])) {
            $i->cascaded[$r->name] = array();
          }
          $i->cascaded[$r->name][] = $r->data;
        }
        else {
          $i->cascaded[$r->name] = $r->data;
        }
      }

      // Nodes
      if ($this->include_nodes) {
        if ($nids = mm_content_get_nids_by_mmtid($item->mmtid)) {
          foreach (array_diff($nids, array_keys($this->nodes)) as $new_nid) {
            $this->nodes[$new_nid] = TRUE;
          }
          $this->page_nodes[$item->mmtid] = $nids;
        }
      }
    }

    $this->pool[$item->mmtid] = $item->perms[Constants::MM_PERMS_IS_GROUP] ? new MMExportGroup((array) $i) : new MMExportCat((array) $i);
    if (!$this->subrequest) {
      array_splice($this->aliases, $item->level);
      $this->aliases[] = $item->alias;
      array_splice($this->parents, $item->level);
      $this->parents[] = $item->mmtid;
      $this->pages[join('/', $this->aliases)] = $this->parents;
    }

    return 1;
  }

  public function dump() {
    $out = '$version = ' . $this->export_var(Constants::MM_IMPORT_VERSION) . ";\n";
    $out .= '$pool = array();' . "\n";
    foreach ($this->pool as $mmtid => $item) {
      $out .= '$pool[' . $mmtid . '] = ' . preg_replace('{,\n\)\)$}', ",\n  'pool' => &\$pool,\n))", $this->export_var($item)) . ";\n";
    }
    if ($this->nodes) {
      $out .= '$nodes = array(' . "\n";
      foreach (array_keys($this->nodes) as $nid) {
        if ($node = Node::load($nid)) {
          // FIXME: This needs to change once we know how node_export() works in D8
          $node->uid = $this->uid2username($node->id());
          unset($node->mm_catlist);

          if (isset($node->users_w)) {
            $new_users = array();
            foreach (array_keys($node->users_w) as $uid) {
              $new_users[$this->uid2username($uid)] = '';
            }
            $node->users_w = $new_users;
          }

          if (isset($node->groups_w)) {
            $groups_w = array();
            foreach (array_keys($node->groups_w) as $gid) {
              $groups_w[$gid] = $this->add_group($gid);
            }
            $node->groups_w = $groups_w;
          }

          $node_export = node_export($node, 'drupal');
          if ($node_export['success']) {
            // Remove leading "array(\n  " and trailing ")".
            $out .= "    $nid => " . substr($node_export['output'], 9, -1);
          }
        }
      }
      $out .= ");\n";
      $out .= '$page_nodes = array(' . "\n";
      foreach ($this->pool as $mmtid => $item) {
        if (isset($this->page_nodes[$mmtid])) {
          $out .= "  $mmtid => array(&\$nodes[" . implode('], &$nodes[', $this->page_nodes[$mmtid]) . "]),\n";
        }
      }
      $out .= ");\n";
    }
    else {
      $out .= '$nodes = $page_nodes = array();' . "\n";
    }
    $out .= '$pages = array(' . "\n";
    foreach ($this->pages as $path => $items) {
      $out .= "  array(  // $path\n";
      $out .= '    &$pool[' . join($items, "],\n" . '    &$pool[') . "],\n  ),\n";
    }
    $out .= ");\n";
    return $out;
  }

  /**
   * Export a field.
   *
   * This is a replacement for var_export(), allowing us to more nicely format
   * exports. It will recurse down into arrays and will try to properly export
   * bools when it can, though PHP has a hard time with this since they often
   * end up as strings or ints.
   *
   * This function is adapted from ctools_var_export() in the ctools module.
   */
  private function export_var($var, $prefix = '') {
    if (is_array($var)) {
      if (empty($var)) {
        return 'array()';
      }
      $output = "array(\n";
      foreach ($var as $key => $value) {
        $output .= $prefix . "  " . $this->export_var($key) . " => " . $this->export_var($value, $prefix . '  ') . ",\n";
      }
      return $output . $prefix . ')';
    }
    if (is_object($var) && get_class($var) === 'stdClass') {
      // var_export() will export stdClass objects using an undefined
      // magic method __set_state() leaving the export broken. This
      // workaround avoids this by casting the object as an array for
      // export and casting it back to an object when evaluated.
      return '(object) ' . $this->export_var((array) $var, $prefix);
    }
    if (is_bool($var)) {
      return $var ? 'TRUE' : 'FALSE';
    }
    if (is_object($var)) {
      return get_class($var) . '::__set_state(' . $this->export_var((array) $var, $prefix) . ')';
    }
    return var_export($var, TRUE);
  }

  private function uid2username($uid) {
    if (!isset($this->users[$uid])) {
      $loaded = user_load($uid);
      if (!$loaded) {
        throw new MMImportExportException('Unknown user with uid=@uid.', array('@uid' => $uid));
      }
      $this->users[$uid] = $loaded->getAccountName();
    }
    return $this->users[$uid];
  }

  private function add_group($gid, $orig_mmtid = NULL) {
    $groups_mmtid = mm_content_groups_mmtid();
    $out = array();
    foreach (mm_content_get_parents_with_self($gid) as $mmtid) {
      if ($mmtid != 1 && $mmtid != $groups_mmtid) {
        if (!isset($this->pool[$mmtid])) {
          if ($orig_mmtid && in_array($mmtid, $this->subrequest)) {
            throw new MMImportExportException('The groups with mmtid=@mmtid1 and mmtid=@mmtid2 have a circular reference which cannot be exported.', array(
              '@mmtid1' => $orig_mmtid,
              '@mmtid2' => $mmtid
            ));
          }
          $temp_item = mm_content_get($mmtid, array(Constants::MM_GET_FLAGS));
          $temp_item->perms = mm_content_user_can($mmtid);
          $this->subrequest[] = $mmtid;
          $this->iterate($temp_item);
          array_pop($this->subrequest);
        }
        $out[] = (int) $mmtid;
      }
    }
    return $out;
  }

}
