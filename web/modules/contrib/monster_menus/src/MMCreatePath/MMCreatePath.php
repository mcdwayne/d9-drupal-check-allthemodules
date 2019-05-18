<?php

/**
 * @file
 * Service to create all needed parts of a path in the MM tree.
 */

namespace Drupal\monster_menus\MMCreatePath;

use Drupal\Core\Database\Connection;
use Drupal\monster_menus\Constants;

class MMCreatePath {

  /**
   * Holds statistics concerning the creation of items.
   *
   * @var array[]|string
   */
  private $stats = 'undef';

  /**
   * Holds a cache of username to UID mappings.
   *
   * @var array
   */
  private $uidCache = [];

  /**
   * Keeps track of which items have already had their permissions set.
   *
   * @var array
   */
  private $didExistingPerms = [];

  /**
   * Keeps track of which items have already been created.
   *
   * @var array
   */
  private $createdItems = [];

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructs a MMCreatePath object.
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Clear the caches of items that have already been touched. This allows
   * the same instance to be reused, while adding to the statistics.
   */
  public function clearCaches() {
    $this->didExistingPerms = [];
    $this->createdItems = [];
    $this->uidCache = [];
  }

  /**
   * Create an entire path of MM tree IDs, including any missing parents.
   *
   * @param object[] &$items
   *   An array of MMCreatePathCat and/or MMCreatePathGroup objects, in path
   *   order.
   *
   *   In the perms field, a group can be set to 'self' to include the outer
   *   group in the list or just the mmtid, instead of its full path.
   *
   *   The uid field can be either numeric or the username.
   *
   *   In groups, the members list can contain either numeric uids or usernames.
   *
   *   Normally, each visited entry is cached and therefore only updated once
   *   per run. Call the clearCaches() method beforehand to force an update.
   *
   *   Set 'no_update' to an array of field names which should only be changed
   *   if the tree ID is new, and not if it already exists.
   * @param bool $test
   *   If TRUE, go through the motions, but do not make any changes.
   * @param bool $add_only
   *   If TRUE, don't update existing items, just add anything new.
   * @return bool
   *   TRUE on success
   * @throws \Exception
   */
  public function createPath(&$items, $test = FALSE, $add_only = FALSE) {
    if (is_array($items) && is_array($items[0])) {
      foreach ($items as &$item) {
        if (!$this->createPath($item, $test, $add_only)) {
          return FALSE;
        }
      }

      return TRUE;
    }

    if (!count($items)) {
      _mm_report_error('Tried to create an empty item in MMCreatePath::createPath()', [], $this->stats);
      return FALSE;
    }

    $last = $items[count($items) - 1];
    if (!isset($last->mmtid)) {
      $path = $existing_items = $exists = [];
      foreach ($items as $item) {
        if (isset($item->mmtid)) {
          // PHP doesn't have a way to keep string keys that look like integers
          // from being converted to ints, so flag the true ints with \001.
          $path["\001" . $item->mmtid] = $item->alias;
          $this->didExistingPerms[$item->mmtid] = TRUE;
        }
        else if (empty($item->name)) {
          _mm_report_error('This item has no name: @item', ['@item' => mm_var_export_html($item)], $this->stats);
          return FALSE;
        }
        else {
          $path[$item->name] = $item->alias;
        }
        $existing_items[$item->name][$item->alias] = $item;
      }

      $mmtid = $this->doCreatePath($path, $existing_items, $exists, $test, $add_only);
      if (!$mmtid && $mmtid !== 'test') {
        $msg = $last->type == 'group' ? 'Failed to create group described by @item' : 'Failed to create entry described by @item';
        _mm_report_error($msg, ['@item' => mm_var_export_html($items)], $this->stats);
        return FALSE;
      }

      if ($exists) {
        $i = 0;
        foreach ($exists as $k => $v) {
          if (!$v) {
            break;
          }
          $items[$i++]->mmtid = $k;
        }
      }
      $last->mmtid = $mmtid;

      foreach ($items as $item) {
        if (!($item instanceof MMCreatePathInterface)) {
          throw new \Exception('MMCreatePath::createPath() path segments must be instances of MMCreatePathInterface.');
        }
        if (isset($exists[$item->mmtid]) && $exists[$item->mmtid] && (!isset($this->didExistingPerms[$item->mmtid]) || !empty($item->reset))) {
          $item->uid = $this->getUid($item->uid, 1);

          if (is_array($item->no_update) && ($tree = mm_content_get($item->mmtid))) {
            unset($block);
            unset($vquery);
            foreach ($item->no_update as $nu) {
              if ($nu == 'mmtid') {
                continue;
              }
              if ($nu == 'menu_start' || $nu == 'max_depth' || $nu == 'max_parents') {
                if (empty($block)) {
                  $select = $this->database->select('mm_tree_block', 'b');
                  $select->condition('b.mmtid', $item->mmtid);
                  $select->addField('b', 'bid', 'menu_start');
                  $select->fields('b', ['max_depth', 'max_parents']);
                  $block = $select->execute()->fetchObject();
                }

                $item->$nu = isset($block->$nu) ? $block->$nu : -1;
              }
              else if ($nu == 'members') {
                $item->members = '';
              }
              else if ($nu == 'qfrom' || $nu == 'qfield') {
                if (empty($vquery)) {
                  $select = $this->database->select('mm_group', 'g');
                  $select->join('mm_vgroup_query', 'v', 'g.vgid = v.vgid');
                  $select->fields('v');
                  $select->condition('g.gid', $item->mmtid);
                  $vquery = $select->execute()->fetchObject();
                  if ($vquery) {
                    $vquery->qfield = $vquery->field;
                  }
                }
                if ($vquery && isset($vquery->$nu)) {
                  $item->$nu = $vquery->$nu;
                }
              }
              else if (isset($tree->$nu)) {
                $item->$nu = $tree->$nu;
              }
            }
          }
          $existing_items = $this->clearParms(clone($item));
          $existing_items->recurs_perms = FALSE;
          if (($existing_items->perms = $this->createPerms($item, $test, $add_only)) === FALSE) {
            return FALSE;
          }
          if (!$add_only) {
            if ($test) {
              _mm_report_stat($item->type == 'group', $item->mmtid, 'Would have updated the @thing with mmtid=@mmtid', [], $this->stats);
            }
            else if (!mm_content_insert_or_update(FALSE, $item->mmtid, $existing_items, $this->stats)) {
              return FALSE;
            }
          }

          $this->didExistingPerms[$item->mmtid] = TRUE;
        }
      }
    }

    return TRUE;
  }

  /**
   * @param array[]|string $stats
   *   By default, no statistics are gathered concerning the creation of items.
   *   If this function is called with an array (usually empty to start), it
   *   will be used for statistics. See getStats() for details.
   * @see getStats()
   */
  public function setStats(&$stats) {
    $this->stats = &$stats;
  }

  /**
   * Get the statistics describing a completed path creation.
   *
   * @return array[]|string
   *   Array containing these statistics:
   *   - pages:
   *     An array indexed by mmtid, containing an array of sub-arrays each with
   *     the elements "message" and "vars", which describe the pages that were
   *     acted upon.
   *   - groups:
   *     An array indexed by mmtid, containing an array of sub-arrays each with
   *     the elements "message" and "vars", which describe the groups that were
   *     acted upon.
   *   - errors:
   *     An array containing sub-arrays with the elements "message" and "vars",
   *     which describe any errors that occurred. A count of the number of pages
   *     acted upon can be derived using the count() function.
   */
  public function getStats() {
    return $this->stats;
  }

  /**
   * @param array $path
   *   Array of name => alias pairs, or just names as the array keys, defining
   *   the path to create. If an array key starts with "\001", it is assumed to
   *   be the mmtid of an existing entry, which is then ignored.
   * @param array $existing_items
   *   Array of parent path members which already exist. This array is passed by
   *   reference, so any changes made to it in createOneItem() are permanent.
   * @param array $exists
   *   On completion, this array contains elements, in order, for each segment
   *   of the path, with TRUE for any that already existed.
   * @param bool $test
   *   If TRUE, go through the motions, but do not make any changes.
   * @param bool $add_only
   *   If TRUE, don't update existing items, just add anything new.
   * @return int
   *   The tree ID of the newly-created (or previously existing) entry
   * @throws \Exception
   */
  private function doCreatePath($path, &$existing_items, &$exists, $test, $add_only) {
    if (!$path) {
      // topmost mmtid is root node
      return 1;
    }
    $no_alias = FALSE;
    $elem = array_keys($path);         // name=>alias
    if ($elem[0] === 0) {              // no alias
      $no_alias = TRUE;
      $elem = array_values($path);
    }
    else if ($elem[count($elem) - 1][0] === "\001") {
      // PHP doesn't have a way to keep string keys that look like integers
      // from being converted to ints, so this code expects mmtids to be preceded
      // with \001, instead. That way we can tell the difference between an mmtid
      // and an entry name containing only numbers.
      $string = substr($elem[count($elem) - 1], 1);
      $mmtid = $string === 'test' ? 'test' : intval($string);
      if (is_array($exists)) {
        array_pop($path);
        $this->doCreatePath($path, $existing_items, $exists, $test, $add_only);
        $exists[$mmtid] = TRUE;
      }
      return $mmtid;
    }

    $longpath = implode('|:', $elem);
    if (!isset($this->createdItems[$longpath])) {
      if ($no_alias) {
        $current_name = array_pop($path);
        $current_alias = '';
      }
      else {
        $current_name = array_pop($elem);
        $current_alias = array_pop($path);
      }

      $parent = $this->doCreatePath($path, $existing_items, $exists, $test, $add_only);
      if (!$parent) {
        // error
        return $this->createdItems[$longpath] = 0;
      }

      if ($current_alias != '') {
        $tree = mm_content_get(['parent' => $parent, 'alias' => $current_alias], [], 1);
      }
      else {
        $tree = mm_content_get(['parent' => $parent, 'name' => $current_name], [], 1);
      }

      if ($tree) {
        if (is_array($exists)) {
          $exists[$tree[0]->mmtid] = TRUE;
        }
        $this->createdItems[$longpath] = $tree[0]->mmtid;
      }
      else {
        $this->createdItems[$longpath] = $this->createOneItem($parent, $current_name, $current_alias, $existing_items, $test, $add_only);
        if (is_array($exists) && $this->createdItems[$longpath]) {
          $exists[$this->createdItems[$longpath]] = FALSE;
        }
      }
    }
    elseif (is_array($exists)) {
      array_pop($path);
      $this->doCreatePath($path, $existing_items, $exists, $test, $add_only);
      $exists[$this->createdItems[$longpath]] = $this->createdItems[$longpath] != 0;
    }

    return $this->createdItems[$longpath];
  }

  /**
   * Create a new MM tree entry.
   *
   * @param int $parent
   *   Tree ID of the parent, under which to create a new child
   * @param string $name
   *   Human-readable name of the new child
   * @param string $alias
   *   URL alias of the new child
   * @param array $existing_items
   *   Reference to the array of parent path members which already exist. Any
   *   changes made to it here are permanent.
   * @param bool $test
   *   If TRUE, go through the motions, but do not make any changes.
   * @param bool $add_only
   *   If TRUE, don't update existing items, just add anything new.
   * @return int|bool
   *   The tree ID of the newly-created (or previously existing) entry, or FALSE
   *   on error.
   * @throws \Exception
   */
  private function createOneItem($parent, $name, $alias, $existing_items, $test, $add_only) {
    // Note: $parms is already a reference, and PHP passes it to this function
    // that way.
    $item = &$existing_items[$name][$alias];
    if (isset($item->mmtid)) {
      return $item->mmtid;
    }

    $existing_items = $this->clearParms(clone($item));
    $existing_items->name = $name;
    $existing_items->alias = $alias;
    if (($existing_items->perms = $this->createPerms($item, $test, $add_only)) === FALSE) {
      return FALSE;
    }
    $existing_items->uid = $this->getUid($existing_items->uid, 1);
    $existing_items->recurs_perms = FALSE;

    if ($test) {
      _mm_report_stat($item->type == 'group', $parent, 'Would have created the @thing with name=@name, alias=@alias', ['@name' => $name, '@alias' => $alias], $this->stats);
      return $item->mmtid = 'test';
    }
    return $item->mmtid = mm_content_insert_or_update(TRUE, $parent, $existing_items, $this->stats);
  }

  /**
   * Remove unneeded parameters which could cause mm_content_insert_or_update()
   * to complain
   *
   * @param object $parms
   *   Parameters object to modify
   * @return object
   *   The modified parameters object
   */
  private function clearParms($parms) {
    if (isset($parms->members) && $parms->members !== '') {
      $new_members = [];
      if (is_array($parms->members)) {
        foreach ($parms->members as $m) {
          $uid = $this->getUid($m, FALSE);
          if ($uid !== FALSE) {
            $new_members[] = $uid;
          }
        }
      }
      $parms->members = $new_members;
    }
    unset($parms->mmtid);
    unset($parms->type);
    unset($parms->no_update);
    unset($parms->reset);
    unset($parms->vgroup);
    return $parms;
  }

  /**
   * Get the uid associated with a username
   *
   * @param int|string $uid
   *   uid or username to resolve
   * @param string $failure
   *   Message to return upon failure
   * @return int|string
   *   The resolved uid, or $failure
   */
  private function getUid($uid, $failure) {
    if (!isset($uid)) {
      return 1;
    }
    if (is_numeric($uid)) {
      return $uid;
    }

    if (isset($this->uidCache[$uid])) {
      if ($this->uidCache[$uid] === 'fail') {
        return $failure;
      }
      return $this->uidCache[$uid];
    }

    $q = $this->database->select('users_field_data', 'u')
      ->fields('u', ['uid']);
    $q->condition('u.name', $uid);
    $u = $q->execute()->fetchField();
    if ($u == '') {
      _mm_report_error("Could not find user '@uid' in users table", ['@uid' => $uid], $this->stats);
      $this->uidCache[$uid] = 'fail';
      return $failure;
    }

    return $this->uidCache[$uid] = $u;
  }

  /**
   * Create any groups referred to by another group or entry's permissions
   *
   * @param object &$item
   *   MMCreatePathCat or MMCreatePathGroup object describing the item for which
   *   groups are to be created
   * @param bool $test
   *   If TRUE, go through the motions, but do not make any changes.
   * @param bool $add_only
   *   If TRUE, don't update existing items, just add anything new.
   * @return array|bool
   *   An updated list of permissions, with the resulting group IDs
   * @throws \Exception
   */
  private function createPerms(&$item, $test, $add_only) {
    $perms = [];
    foreach ([Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ] as $m) {
      if (is_array($item->perms) && isset($item->perms[$m]['groups'])) {
        foreach ($item->perms[$m]['groups'] as &$g) {
          if ($g === 'self') {
            $perms[$m]['groups'][] = 'self';
          }
          elseif (is_numeric($g)) {
            $perms[$m]['groups'][] = $g;
          }
          elseif (is_array($g) && $g) {
            $last = &$g[count($g) - 1];
            if (!isset($last->mmtid) && !$this->createPath($g, $test, $add_only)) {
              return FALSE;
            }

            $perms[$m]['groups'][] = $last->mmtid;
          }
        }
      }

      if (isset($item->perms[$m]['users'])) {
        foreach ($item->perms[$m]['users'] as &$u) {
          if (($uid = $this->getUid($u, FALSE)) !== FALSE) {
            $u = $uid;
          }
        }

        $perms[$m]['users'] = $item->perms[$m]['users'];
      }
    }

    return $perms;
  }

}