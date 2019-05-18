<?php

/**
 * @file
 * Functions related to the retrieval of data for monster menus
 */

use Drupal\block\Entity\Block;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;
use Drupal\monster_menus\GetTreeIterator\ContentCopyIter;
use Drupal\monster_menus\GetTreeIterator\ContentDeleteIter;
use Drupal\monster_menus\GetTreeIterator\ContentFindUnmodifiedHomepagesIter;
use Drupal\monster_menus\GetTreeIterator\ContentMoveIter;
use Drupal\monster_menus\GetTreeIterator\ContentUserCanRecycleIter;
use Drupal\monster_menus\GetTreeResults;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Return a nicer version of entry names starting with '.'
 *
 * @param $name
 *   The entry name, obtained from mm_content_get_tree
 * @return string
 *   The nicer version
 */
function mm_content_expand_name($name) {
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    // This is cumbersome, but assigning to an array is the only way that works.
    $drupal_static_fast['aliases'] = &drupal_static(__FUNCTION__);
    $drupal_static_fast['aliases'] = array(
      Constants::MM_ENTRY_NAME_DEFAULT_USER  => t('[New account defaults]'),
      Constants::MM_ENTRY_NAME_DISABLED_USER => t('[Disabled accounts]'),
      Constants::MM_ENTRY_NAME_GROUPS        => t('Permission groups'),
      Constants::MM_ENTRY_NAME_LOST_FOUND    => t('[Lost and found]'),
      Constants::MM_ENTRY_NAME_RECYCLE       => t('[Recycle bin]'),
      Constants::MM_ENTRY_NAME_USERS         => t('User list'),
      Constants::MM_ENTRY_NAME_VIRTUAL_GROUP => t('[Pre-defined groups]'),
    );
    $drupal_static_fast['aliases'] += mm_module_invoke_all('mm_item_name');
  }

  if (!is_string($name)) {
    // The name is not a bare string, so it can't possibly have an alias.
    return $name;
  }
  $aliases = &$drupal_static_fast['aliases'];
  if (isset($aliases[$name])) {
    if (is_array($aliases[$name])) {
      if (isset($aliases[$name]['callback']) && function_exists($aliases[$name]['callback'])) {
        $result = call_user_func($aliases[$name]['callback'], $name);
        if (!empty($result)) {
          return $result;
        }
      }
      if (!empty($aliases[$name]['name'])) {
        return $aliases[$name]['name'];
      }
    }
    else {
      return $aliases[$name];
    }
  }

  return $name;
}

/**
 * Traverse the tree
 *
 * @param int $mmtid (1)
 *   Starting tree ID
 * @param array $params
 *   An array containing parameters. The array is indexed using the constants
 *   below.
 *   - MM_GET_TREE_ADD_SELECT (none):
 *     A string or array of strings to add to the SELECT portion of the query
 *   - MM_GET_TREE_ADD_TO_CACHE (FALSE):
 *     Add results to the caches used by mm_content_get() and
 *     mm_content_get_parents()
 *   - MM_GET_TREE_BIAS_ANON (TRUE):
 *     If TRUE, assume user 0 can't read any groups (more secure)
 *   - MM_GET_TREE_BLOCK (''):
 *     Only retrieve entries that are part of one block. Defaults to all blocks.
 *   - MM_GET_TREE_DEPTH (-1):
 *     When 'mmtid' is used, a query to return all items in the tree below that
 *     point can be returned. This field specifies the depth of recursion:
 *     - 0:  just the item specified by $mmtid
 *     - -1: all levels
 *     - 1:  the item and its immediate children
 *     - N:  any other other number will return that many levels (can be slow)
 *   - MM_GET_TREE_FAKE_READ_BINS (FALSE):
 *     Pretend the user can read all recycle bins (used internally)
 *   - MM_GET_TREE_FILTER_BINS (TRUE):
 *     Get entries that are recycle bins
 *   - MM_GET_TREE_FILTER_DOTS (TRUE):
 *     Get all entries with names that start with '.'. If FALSE, only .Groups,
 *     .Users, and .Virtual are returned.
 *   - MM_GET_TREE_FILTER_GROUPS (TRUE):
 *     Get entries that are groups
 *   - MM_GET_TREE_FILTER_HIDDEN (FALSE):
 *     If TRUE, return entries with the "hidden" attribute set, even if the
 *     current user does not normally have permission to view them
 *   - MM_GET_TREE_FILTER_NORMAL (TRUE):
 *     Get entries that are neither groups nor in /users
 *   - MM_GET_TREE_FILTER_USERS (TRUE):
 *     Get entries in /users
 *   - MM_GET_TREE_HERE (none)
 *     An array of MM Tree IDs currently being viewed by the user. Parent
 *     entries will have their their state set to MM_GET_TREE_STATE_EXPANDED.
 *   - MM_GET_TREE_ITERATOR (none):
 *     GetTreeIterator (or subclass) to call as each new item is found. When
 *     this option is used, memory is conserved by not returning anything.
 *   - MM_GET_TREE_PRUNE_PARENTS (FALSE):
 *     If TRUE, prune parents, depending upon max_parents in the block
 *   - MM_GET_TREE_RETURN_BINS (FALSE):
 *     A comma-separated list of the mmtids of any parent recycle bins
 *   - MM_GET_TREE_RETURN_BLOCK (FALSE):
 *     Attributes from the mm_tree_block table
 *   - MM_GET_TREE_RETURN_FLAGS (FALSE):
 *     Flags from the mm_tree_flags table
 *   - MM_GET_TREE_RETURN_KID_COUNT (FALSE):
 *     A count of the number of children each tree entry has
 *   - MM_GET_TREE_RETURN_MTIME (FALSE):
 *     The muid (user ID who made the last modification) and mtime (time) of the
 *     modification
 *   - MM_GET_TREE_RETURN_NODE_COUNT (FALSE):
 *     If TRUE, return a count of the number of nodes assigned to each item. If
 *     a string or array of strings, return a count of the number of nodes of
 *     that type.
 *   - MM_GET_TREE_RETURN_PERMS (none):
 *     If set, return whether or not the user can perform that action
 *     (MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB, MM_PERMS_APPLY,
 *     MM_PERMS_IS_USER, MM_PERMS_IS_GROUP, MM_PERMS_IS_RECYCLE_BIN,
 *     MM_PERMS_IS_RECYCLED). The requested permission can either be a single
 *     value or an array. If an empty array or TRUE is passed, all permissions
 *     are returned.
 *   - MM_GET_TREE_SORT (FALSE):
 *     If TRUE, sort the entries according to sort_idx; always TRUE when
 *     MM_GET_TREE_DEPTH != 0
 *   - MM_GET_TREE_USER (current user):
 *     User object to test permissions against
 *   - MM_GET_TREE_VIRTUAL (TRUE):
 *     Include virtual user list sub-entries
 *   - MM_GET_TREE_WHERE (none):
 *     Add a WHERE clause to the outermost query
 *   If none of ([...USERS], [...GROUPS], [...NORMAL]) is TRUE, all types are
 *   retrieved. MM_GET_TREE_RETURN_TREE is always TRUE.
 * @return array|null
 *   Array of tree entries, unless MM_GET_TREE_ITERATOR is used
 */
function mm_content_get_tree($mmtid = 1, $params = NULL) {
  $defaults = array(
    Constants::MM_GET_TREE_BLOCK =>         '',
    Constants::MM_GET_TREE_DEPTH =>         -1,
    Constants::MM_GET_TREE_FILTER_BINS =>   TRUE,
    Constants::MM_GET_TREE_FILTER_DOTS =>   TRUE,
    Constants::MM_GET_TREE_FILTER_GROUPS => FALSE,
    Constants::MM_GET_TREE_FILTER_HIDDEN => FALSE,
    Constants::MM_GET_TREE_FILTER_NORMAL => FALSE,
    Constants::MM_GET_TREE_FILTER_USERS =>  FALSE,
    Constants::MM_GET_TREE_HERE =>          NULL,
    Constants::MM_GET_TREE_ITERATOR =>      NULL,
    Constants::MM_GET_TREE_PRUNE_PARENTS => FALSE,
    Constants::MM_GET_TREE_SORT =>          FALSE,
    Constants::MM_GET_TREE_USER =>          \Drupal::currentUser(),
    Constants::MM_GET_TREE_VIRTUAL =>       TRUE,
    'found' =>                   -1,
    'level' =>                   0,
    'parent_level' =>            -1,
    'pprune' =>                  -1,
    'q' =>                       NULL,
  );

  if (!is_array($params)) {
    $params = array();
  }
  $params = array_merge($defaults, $params);

  if (empty($params[Constants::MM_GET_TREE_FILTER_GROUPS]) && empty($params[Constants::MM_GET_TREE_FILTER_USERS]) && empty($params[Constants::MM_GET_TREE_FILTER_NORMAL])) {
    $params[Constants::MM_GET_TREE_FILTER_GROUPS] = $params[Constants::MM_GET_TREE_FILTER_USERS] = $params[Constants::MM_GET_TREE_FILTER_NORMAL] = TRUE;
  }

  if ($params[Constants::MM_GET_TREE_VIRTUAL] && !mm_get_setting('user_homepages.virtual')) {
    $params[Constants::MM_GET_TREE_VIRTUAL] = FALSE;
  }

  if (!empty($params[Constants::MM_GET_TREE_SORT]) || $params[Constants::MM_GET_TREE_DEPTH] != 0) {
    mm_content_update_sort_queue();
  }

  return _mm_content_get_tree($mmtid, $params);
}

function _mm_content_get_tree($mmtid, &$params) {
  $users_mmtid = mm_content_users_mmtid();

  $have_virtual = FALSE;
  if (empty($params['q'])) {
    $params['q'] = _mm_content_get_tree_query($mmtid, $params);
    if (is_array($params[Constants::MM_GET_TREE_HERE])) {
      foreach ($params[Constants::MM_GET_TREE_HERE] as $i => $h) {
        if ($h < 0) {
          unset($params[Constants::MM_GET_TREE_HERE][$i]);
          $have_virtual = TRUE;
          if ($params[Constants::MM_GET_TREE_DEPTH] > 0) {
            $params[Constants::MM_GET_TREE_DEPTH]--;
          }
          break;
        }
        elseif ($params[Constants::MM_GET_TREE_VIRTUAL] && $h == $users_mmtid && $params[Constants::MM_GET_TREE_DEPTH]) {
          $have_virtual = TRUE;
        }
      }
    }
  }

  $rows = array();

  while ($r = $params['q']->next()) {
    if (!isset($params['q']->start_level)) {
      $params['q']->start_level = strlen($r->sort_idx) / Constants::MM_CONTENT_BTOA_CHARS;
    }
    $r->level = strlen($r->sort_idx) / Constants::MM_CONTENT_BTOA_CHARS - $params['q']->start_level + $params['q']->level_offset;

    if ($r->level <= $params['parent_level']) {
      $params['q']->back();
      break;
    }
    elseif ($r->level > $params['parent_level']) {
      if (!isset($r->bid) || is_null($r->bid)) {
        $r->bid = Constants::MM_MENU_DEFAULT;
        $r->max_depth = $r->max_parents = -1;
      }
      $add = _mm_content_get_tree_recurs($r, $params, isset($r->{Constants::MM_PERMS_IS_GROUP}) ? $r->{Constants::MM_PERMS_IS_GROUP} : FALSE, isset($r->{Constants::MM_PERMS_IS_USER}) ? $r->{Constants::MM_PERMS_IS_USER} : FALSE, $last);
    }

    if (is_array($rows) && !empty($add) && is_array($add)) {
      $rows = array_merge($rows, $add);
      unset($add);   // save some memory
    }

    if (!empty($last)) {
      break;
    }
  }

  if ($params['pprune'] > 0 && $params['found']) {
    $params['pprune']--;
  }

  if (isset($params[Constants::MM_GET_TREE_ITERATOR])) {
    return NULL;
  }

  if (!$params['level'] && ($have_virtual || $mmtid == $users_mmtid) && $params[Constants::MM_GET_TREE_DEPTH]) {
    if ($params[Constants::MM_GET_TREE_VIRTUAL]) {
      $select = Database::getConnection()->select('mm_tree', 't');
      $select->addExpression('GROUP_CONCAT(DISTINCT UCASE(SUBSTR(t.name, 1, 1)) ORDER BY t.name SEPARATOR \'\')', 'letters');
      $select->condition('t.parent', $users_mmtid);
      $letters = $select->execute()->fetchField();
      $letters = preg_replace('/[\W_]/', '', $letters, -1, $matches);
      if ($matches) $letters = "~$letters";
      $letters = str_split($letters);
      $parent = NULL;

      for ($i = 0; $i < count($rows); $i++) {
        if ($rows[$i]->mmtid == $users_mmtid) {
          $parent = $rows[$remainder = $i];
          $parent->state &= ~Constants::MM_GET_TREE_STATE_HERE;
          $last = 0;

          while (++$i < count($rows) && $rows[$i]->level > $parent->level) {
            if ($rows[$i]->level == $parent->level + 1) {
              $letr = mb_strtoupper($rows[$i]->name[0]);
              $name = ctype_alpha($letr) ? $letr : t('(other)');
              if (!$last || $name != $rows[$last]->name) {
                $alias = ctype_alpha($letr) ? $letr : '~';
                while ($letters) {
                  $add = array_shift($letters);
                  $new = _mm_content_virtual_dir(-ord($add), $parent->mmtid, $parent->level + 1, $add == $alias ? Constants::MM_GET_TREE_STATE_EXPANDED|Constants::MM_GET_TREE_STATE_HERE : Constants::MM_GET_TREE_STATE_COLLAPSED);
                  $new->default_mode = $parent->default_mode;
                  array_splice($rows, $last = $i++, 0, array($new));  // insert virtual dir
                  $remainder++;
                  if ($add == $alias) {
                    break;
                  }
                }
              }

              $rows[$i]->parent = $rows[$last]->mmtid;
              if ($rows[$i]->state & Constants::MM_GET_TREE_STATE_EXPANDED) {
                $rows[$last]->state = Constants::MM_GET_TREE_STATE_EXPANDED;
              }
            }     // if
            $remainder++;

            $rows[$i]->level++;
          }       // while
          break;  // exit outer for loop
        }         // if
      }           // for
    }

    if (!\Drupal::currentUser()->hasPermission('administer all users')) {
      $hidden_names = \Drupal::state()->get('monster_menus.hidden_user_names', array());
      $dels = array();
      foreach ($rows as $i => $r) {
        if ($r->alias == '~') {
          $other = $i;
        }
        elseif ($r->parent == -126 || $r->parent == $users_mmtid) {  // -126 = -ord('~')
          if (in_array($r->name, $hidden_names)) {
            $dels[] = $i;
          }
          else {
            unset($other);
          }
        }
      }

      if (isset($other)) {
        // All 'other' rows are invisible to the user
        array_unshift($dels, $other);
      }

      foreach (array_reverse($dels) as $i) {
        array_splice($rows, $i, 1);
      }
    }

    if ($params[Constants::MM_GET_TREE_VIRTUAL]) {
      $i = $parent ? $remainder + 1 : count($rows);
      foreach ($letters as $add) {
        $new = _mm_content_virtual_dir(-ord($add), $users_mmtid, $parent ? $parent->level + 1 : 0, Constants::MM_GET_TREE_STATE_COLLAPSED);
        if ($parent) {
          $new->default_mode = $parent->default_mode;
        }
        array_splice($rows, $i++, 0, array($new));  // insert virtual dir
      }
    }
  }

// if( !$params['level'] ) debug_add_dump( $rows );
  return $rows;
}

/**
 * Helper function for _mm_content_get_tree()/mm_content_get()
 */
function _mm_content_split_flags($flags) {
  if (is_array($flags)) {
    return $flags;
  }
  preg_match_all('/(?:(.*?)\|1(.*?)(?:\|2|$))/', $flags, $matches);
  return $matches[0] ? array_combine($matches[1], $matches[2]) : array();
}

/**
 * Helper function for _mm_content_get_tree()
 */
function _mm_content_get_tree_query($mmtid, $params) {
  $params[Constants::MM_GET_TREE_RETURN_TREE] = TRUE;
  if (!empty($params[Constants::MM_GET_TREE_BLOCK])) {
    $params[Constants::MM_GET_TREE_RETURN_BLOCK] = TRUE;
  }

  if (!is_array($params[Constants::MM_GET_TREE_HERE])) {
    $params[Constants::MM_GET_TREE_HERE] = array($mmtid);
  }
  elseif (!count($params[Constants::MM_GET_TREE_HERE])) {
    $params[Constants::MM_GET_TREE_HERE][] = $mmtid;
  }
  elseif ($params[Constants::MM_GET_TREE_DEPTH] != 0) {
    $params[Constants::MM_GET_TREE_DEPTH] = 1;
  }

  $query = array();
  $max = count($params[Constants::MM_GET_TREE_HERE]) - 1;
  $users_mmtid = $params[Constants::MM_GET_TREE_VIRTUAL] ? mm_content_users_mmtid() : -1;
  if (isset($params[Constants::MM_GET_TREE_RETURN_PERMS])) {
    if (!isset($params[Constants::MM_GET_TREE_ITERATOR])) {
      $params[Constants::MM_GET_TREE_RETURN_BINS] = TRUE;
      $params[Constants::MM_GET_TREE_FAKE_READ_BINS] = TRUE;
    }
  }
  else {
    $params[Constants::MM_GET_TREE_RETURN_PERMS] = array(Constants::MM_PERMS_IS_GROUP, Constants::MM_PERMS_IS_USER);
  }

  for ($i = 0; $i <= $max; $i++) {
    $mmtid = $params[Constants::MM_GET_TREE_HERE][$i];
    if ($mmtid != $users_mmtid || $i == $max || $params[Constants::MM_GET_TREE_HERE][$i + 1] >= 0) {
      $params2 = $params;
      if ($mmtid < 0) {
        $ch = chr(-$mmtid);
        $re = $ch == '~' ? "t.name REGEXP '^[^[:alpha:]]'" : "UCASE(t.name) LIKE '$ch%'";
        $params2[Constants::MM_GET_TREE_INNER_FILTER] = " AND $re";
        $params2[Constants::MM_GET_TREE_DEPTH] = 1;
        $params2[Constants::MM_GET_TREE_MMTID] = $users_mmtid;
      }
      else {
        $params2[Constants::MM_GET_TREE_INNER_FILTER] = '';
        $params2[Constants::MM_GET_TREE_DEPTH] = $mmtid == $users_mmtid ? 0 : 1;
        $params2[Constants::MM_GET_TREE_MMTID] = $mmtid;
      }

      if ($i == $max) {
        if ($mmtid != $users_mmtid) $params2[Constants::MM_GET_TREE_DEPTH] = $params[Constants::MM_GET_TREE_DEPTH];
        $params2[Constants::MM_GET_TREE_BLOCK] = $params[Constants::MM_GET_TREE_BLOCK];
        $params2[Constants::MM_GET_TREE_SORT] = $params[Constants::MM_GET_TREE_DEPTH] != 0 || !empty($params[Constants::MM_GET_TREE_SORT]);
        $query[] = mm_content_get_query($params2);
      }
      else {
        $params2[Constants::MM_GET_TREE_BLOCK] = '';
        $params2[Constants::MM_GET_TREE_SORT] = FALSE;
        $query[] = preg_replace('/ ORDER BY NULL$/', '', mm_content_get_query($params2));
      }
    }
  }

// debug_add_dump($mode, $params[Constants::MM_GET_TREE_HERE], Database::getConnection()->prefixTables(join(' UNION ', $query)));
  $params['q'] = new GetTreeResults(join(' UNION ', $query));
  $params['q']->level_offset = $params['level'];
  return $params['q'];
}

function _mm_content_get_tree_recurs($r, $params, $parent_is_group, $parent_is_user, &$last) {
  $_mmtbt_cache = &drupal_static('_mmtbt_cache', array());
  $_mmgp_cache = &drupal_static('_mmgp_cache', array());
  $_mmuc_cache = &drupal_static('_mmuc_cache', array());

  $rows = array();
  $last = TRUE;

  $xlate = array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ, Constants::MM_PERMS_IS_GROUP, Constants::MM_PERMS_IS_USER, Constants::MM_PERMS_ADMIN, Constants::MM_PERMS_IS_RECYCLE_BIN, Constants::MM_PERMS_IS_RECYCLED);
  foreach ($xlate as $field) {
    if (isset($r->$field)) {
      if (!isset($r->perms)) {
        $r->perms = array();
      }
      $r->perms[$field] = $r->$field != 0;
      unset($r->$field);
    }
  }

  if (!empty($params[Constants::MM_GET_TREE_RETURN_PERMS]) && !isset($params[Constants::MM_GET_TREE_ITERATOR])) {
    if ($r->perms[Constants::MM_PERMS_IS_RECYCLE_BIN]) {
      $r->perms[Constants::MM_PERMS_APPLY] = TRUE;
      $r->perms[Constants::MM_PERMS_READ] = mm_content_user_can_recycle($r->mmtid, Constants::MM_PERMS_READ, $params[Constants::MM_GET_TREE_USER]);
    }
    elseif (isset($r->recycle_bins)) {
      foreach (explode(',', $r->recycle_bins) as $bin) {
        $r->perms[Constants::MM_PERMS_READ] = $r->perms[Constants::MM_PERMS_READ] && mm_content_user_can_recycle($bin, Constants::MM_PERMS_READ, $params[Constants::MM_GET_TREE_USER]);
      }
    }
  }

  if (!empty($params[Constants::MM_GET_TREE_ADD_TO_CACHE])) {
    if (!isset($_mmtbt_cache[$r->mmtid])) {
      $_mmtbt_cache[$r->mmtid] = $r;
    }

    if (!isset($_mmgp_cache[$r->mmtid])) {
      $_mmgp_cache[$r->mmtid] = $r->parent;
    }

    if (isset($r->perms)) {
      foreach ($r->perms as $field => $val) {
        if (!isset($_mmuc_cache[$r->mmtid][$params[Constants::MM_GET_TREE_USER]->id()][$field])) {
          $_mmuc_cache[$r->mmtid][$params[Constants::MM_GET_TREE_USER]->id()][$field] = $val;
        }
      }
    }
  }

  if (!empty($params[Constants::MM_GET_TREE_RETURN_FLAGS])) {
    $r->flags = _mm_content_split_flags($r->flags);
  }

  if (!isset($r->is_group)) {
    $r->is_group =
      $parent_is_group || $r->name == Constants::MM_ENTRY_NAME_GROUPS ||
      !empty($params[Constants::MM_GET_TREE_ITERATOR]->parent_is_group) ||
      (isset($r->perms) ? $r->perms[Constants::MM_PERMS_IS_GROUP] : mm_content_user_can($r->mmtid, Constants::MM_PERMS_IS_GROUP, $params[Constants::MM_GET_TREE_USER]));
  }

  if (!isset($r->is_user)) {
    $r->is_user =
      $parent_is_user  || $r->name == Constants::MM_ENTRY_NAME_USERS ||
      !empty($params[Constants::MM_GET_TREE_ITERATOR]->parent_is_user) ||
      (isset($r->perms) ? $r->perms[Constants::MM_PERMS_IS_USER]  : mm_content_user_can($r->mmtid, Constants::MM_PERMS_IS_USER, $params[Constants::MM_GET_TREE_USER]));
  }

  $r->is_dot = $r->name[0] == '.';

  if ($r->is_group) {
    unset($r->nodecount);
  }

  $visible = (!empty($params[Constants::MM_GET_TREE_FILTER_GROUPS]) || !$r->is_group) &&
      (!empty($params[Constants::MM_GET_TREE_FILTER_NORMAL]) || $r->is_group || $r->is_user) &&
      (!empty($params[Constants::MM_GET_TREE_FILTER_USERS]) || !$r->is_user);

  if ($r->is_user && in_array($r->name, \Drupal::state()->get('monster_menus.hidden_user_names', array()))) {
    $r->bid = Constants::MM_MENU_UNSET;
  }

  if ($visible || $r->mmtid == 1) {
    if ($r->is_group || $r->name == Constants::MM_ENTRY_NAME_USERS || $r->mmtid == 1) {
      unset($r->nodecount);
    }

    $params2 = $params;
    if (is_array($params[Constants::MM_GET_TREE_HERE])) {
      $params2[Constants::MM_GET_TREE_HERE] =& $params[Constants::MM_GET_TREE_HERE];
    }
    $params2[Constants::MM_GET_TREE_DEPTH] = $params[Constants::MM_GET_TREE_DEPTH] < 0 ? -1 : $params[Constants::MM_GET_TREE_DEPTH] - 1;

    if ($params[Constants::MM_GET_TREE_HERE] && $r->mmtid == $params[Constants::MM_GET_TREE_HERE][0]) {
      $r->state = count($params[Constants::MM_GET_TREE_HERE]) >= 2 ? Constants::MM_GET_TREE_STATE_EXPANDED : Constants::MM_GET_TREE_STATE_EXPANDED|Constants::MM_GET_TREE_STATE_HERE;
      array_shift($params[Constants::MM_GET_TREE_HERE]);

      if ($params[Constants::MM_GET_TREE_BLOCK] && ($r->bid == Constants::MM_MENU_UNSET && $r->max_depth >= 0 || $r->bid != Constants::MM_MENU_UNSET && $r->bid != Constants::MM_MENU_DEFAULT)) {
        $depth_new = $r->max_depth;
        if ($depth_new == -1) {
          $params2[Constants::MM_GET_TREE_DEPTH] = $params[Constants::MM_GET_TREE_DEPTH] = -1;
        }
        else {
          $params[Constants::MM_GET_TREE_DEPTH] = $depth_new;
          $params2[Constants::MM_GET_TREE_DEPTH] = $depth_new - 1;
        }
      }
      elseif ($params[Constants::MM_GET_TREE_DEPTH] < 0 || $params[Constants::MM_GET_TREE_DEPTH] > 2) {
        $params[Constants::MM_GET_TREE_DEPTH] = count($params[Constants::MM_GET_TREE_HERE]) + 2;
        $params2[Constants::MM_GET_TREE_DEPTH] = $params[Constants::MM_GET_TREE_DEPTH] - 1;
      }

      $params['found'] = $r->mmtid;
      if ($params[Constants::MM_GET_TREE_PRUNE_PARENTS] && $r->max_parents != '' && $r->max_parents >= 0) {
        $params['pprune'] = $r->max_parents + 2;
      }
    }
    else {
      $r->state = $params[Constants::MM_GET_TREE_DEPTH] && $r->parent <= 0 ? Constants::MM_GET_TREE_STATE_EXPANDED : (isset($r->kids) && $r->kids > 0 ? Constants::MM_GET_TREE_STATE_COLLAPSED : Constants::MM_GET_TREE_STATE_LEAF);
      if (is_array($params[Constants::MM_GET_TREE_HERE])) {
        $params2[Constants::MM_GET_TREE_DEPTH] = 0;
        $params2['once'] = TRUE;
        foreach (array(Constants::MM_GET_TREE_PRUNE_PARENTS, Constants::MM_GET_TREE_RETURN_NODE_COUNT) as $mode) {
          $params2[$mode] = FALSE;
        }
      }
    }

    if ((!$params[Constants::MM_GET_TREE_BLOCK] || $r->bid == (string) $params[Constants::MM_GET_TREE_BLOCK] || $r->bid == Constants::MM_MENU_UNSET || $r->bid == Constants::MM_MENU_DEFAULT) && (!$r->hidden || !$r->level || $params[Constants::MM_GET_TREE_FILTER_HIDDEN] || isset($params[Constants::MM_GET_TREE_ITERATOR]) || !isset($r->perms) || !empty($r->perms[Constants::MM_PERMS_WRITE]) || !empty($r->perms[Constants::MM_PERMS_SUB]) || !empty($r->perms[Constants::MM_PERMS_APPLY]) || \Drupal::currentUser()->hasPermission('view all menus'))) {

      if (!isset($params[Constants::MM_GET_TREE_ITERATOR])) {
        $parent = count($rows);
      }

      if ($r->state) {
        if ($r->hidden) {
          $r->state |= Constants::MM_GET_TREE_STATE_HIDDEN;
        }
        elseif ($r->name == Constants::MM_ENTRY_NAME_RECYCLE) {
          $r->state |= Constants::MM_GET_TREE_STATE_RECYCLE;
        }

        if (!$r->is_group) {
          if (isset($r->perms[Constants::MM_PERMS_READ]) && !$r->perms[Constants::MM_PERMS_READ]) {
            $r->state |= Constants::MM_GET_TREE_STATE_DENIED;
            $skip_kids = TRUE;
          }

          if (strpos($r->default_mode, Constants::MM_PERMS_READ) === FALSE) {
            $r->state |= Constants::MM_GET_TREE_STATE_NOT_WORLD;
          }
        }

        if ($visible) {
          if (!isset($params[Constants::MM_GET_TREE_ITERATOR])) {
            $rows[] = $r;
            if (!empty($params['once'])) {
              return $rows;
            }
          }
          elseif (!empty($params['once'])) {
            return;
          }
          elseif (($iter_ok = $params[Constants::MM_GET_TREE_ITERATOR]->iterate($r)) < 0) {
            $last = FALSE;
            $skip_kids = TRUE;
          }
          elseif (!$iter_ok) {
            return;
          }
        }
      }

      if (!isset($skip_kids) && $params[Constants::MM_GET_TREE_DEPTH]) {
        if (isset($params[Constants::MM_GET_TREE_ITERATOR])) {
          $ois_grp = !empty($params[Constants::MM_GET_TREE_ITERATOR]->parent_is_group);
          $ois_user = !empty($params[Constants::MM_GET_TREE_ITERATOR]->parent_is_user);
          $params[Constants::MM_GET_TREE_ITERATOR]->parent_is_group = $r->is_group;
          $params[Constants::MM_GET_TREE_ITERATOR]->parent_is_user = $r->is_user;
        }

        $params2['found'] = -1;
        if (!empty($params2['once'])) $params2['pprune'] = -1;
        $params2['level'] = $params['level'] + 1;
        $params2['parent_level'] = $r->level;
        $kids = _mm_content_get_tree($r->mmtid, $params2);

        if ($params2['pprune'] >= 0) {
          if ($params2['pprune'] == 0) {
            $params['pprune'] = 0;
            return $kids;
          }
          elseif ($params2['found']) {
            $params['pprune'] = $params2['pprune'];
          }
        }

        if (isset($params[Constants::MM_GET_TREE_ITERATOR])) {
          $params[Constants::MM_GET_TREE_ITERATOR]->parent_is_group = $ois_grp;
          $params[Constants::MM_GET_TREE_ITERATOR]->parent_is_user = $ois_user;
        }
        else {
          if (count($rows) > $parent) {
            if ($rows[$parent]->is_group) {
              foreach ($kids as $k) {
                $k->is_group = TRUE;
                unset($k->nodecount);
              }
            }

            if ($rows[$parent]->is_user) {
              foreach ($kids as $k) {
                $k->is_user = TRUE;
                $k->is_user_home = $k->level == $rows[$parent]->level + 1 && $rows[$parent]->name == Constants::MM_ENTRY_NAME_USERS;
              }
            }

            if ($params['found'] != $r->mmtid) {
              $rows[$parent]->state &= ~(Constants::MM_GET_TREE_STATE_EXPANDED|Constants::MM_GET_TREE_STATE_COLLAPSED|Constants::MM_GET_TREE_STATE_LEAF);
              $rows[$parent]->state |= $params2['found'] >= 0 ? Constants::MM_GET_TREE_STATE_EXPANDED :
                  (count($kids) || isset($r->kids) && $r->kids > 0 ? Constants::MM_GET_TREE_STATE_COLLAPSED : Constants::MM_GET_TREE_STATE_LEAF);
            }
          }

          if ((!isset($params['once']) || !$params['once']) && is_array($kids)) {
            $rows = array_merge($rows, $kids);
          }
        }

        if ($params2['found'] >= 0) {
          $params['found'] = $params2['found'];
        }
      }       // if( $params[Constants::MM_GET_TREE_DEPTH] )
      else {
        $skip_kids = TRUE;
      }
    }         // if( !$params[Constants::MM_GET_TREE_BLOCK] || ...
    else {
      $skip_kids = TRUE;
    }
  }           // if( $visible || $r->parent<=0 )
  else {
    $skip_kids = TRUE;
  }

  if (isset($skip_kids)) {
    while (($row = $params['q']->next()) && strlen($row->sort_idx) > strlen($r->sort_idx));
    if ($row) {
      $params['q']->back();
    }
  }

  $last = FALSE;
  return $rows;
}

/**
 * Get the cascaded (inherited by children) settings for an entry.
 *
 * This function returns the exact settings for a particular entry, and does not
 * consider the settings of its parents. To include the parents' settings, see
 * mm_content_resolve_cascaded_setting().
 *
 * @param int $mmtid
 *   ID of the entry to load settings for. If NULL, return a list of possible
 *   settings and their data representation. The structure of the returned array
 *   in this case is:
 *   - data_type:   'int' (integer) or 'string'
 *   - multiple:    TRUE if multiple values are accepted
 *   - user_access: user must have $account->hasPermission() for this value in
 *                  order to set the setting
 *   - not_empty:   TRUE if only !empty() values should be stored
 * @param string|null $name
 *   Name of the setting to return, or NULL to return all settings
 * @return mixed
 *   Either an array or a single value, depending on $name
 */
function mm_content_get_cascaded_settings($mmtid = NULL, $name = NULL) {
  static $drupal_static_fast;

  if (is_null($mmtid)) {
    // $drupal_static_fast['settings'] should never be empty after it has been
    // set once, but for some reason it sometimes is during tests.
    if (!isset($drupal_static_fast) || empty($drupal_static_fast['settings'])) {
      // This is cumbersome, but assigning to an array is the only way that works.
      $drupal_static_fast['settings'] = &drupal_static(__FUNCTION__);
      // Check for mm_cascaded_settings hooks
      $drupal_static_fast['settings'] = \Drupal::moduleHandler()->invokeAll('mm_cascaded_settings');
    }
    return $drupal_static_fast['settings'];
  }

  $cascaded = array();
  $result = Database::getConnection()->select('mm_cascaded_settings', 's')
    ->fields('s')
    ->condition('s.mmtid', $mmtid)
    ->execute();
  foreach ($result as $r) {
    if ($r->data_type == 'int') {
      $r->data = (int) $r->data;
    }

    if ($r->multiple) {
      if (!isset($cascaded[$r->name]) || !is_array($cascaded[$r->name])) {
        $cascaded[$r->name] = array();
      }
      if ($r->array_key != '') {
        $cascaded[$r->name][$r->array_key] = $r->data;
      }
      else {
        $cascaded[$r->name][] = $r->data;
      }
    }
    else {
      $cascaded[$r->name] = $r->data;
    }
  }

  if (!empty($name)) {
    if (isset($cascaded[$name])) {
      return $cascaded[$name];
    }
    $settings = mm_content_get_cascaded_settings();
    return empty($settings[$name]['multiple']) ? NULL : array();
  }
  return $cascaded;
}

/**
 * Set the cascaded (inherited by children) settings for an entry
 *
 * @param int $mmtid
 *   Tree ID of the entry to set settings for
 * @param array $settings
 *   Array containing the settings
 * @param bool $delete
 *   If TRUE, delete all old settings for $mmtid first
 * @param Connection $database
 *   (optional) The database connection to use.
 */
function mm_content_set_cascaded_settings($mmtid, $settings, $delete = TRUE, Connection $database = NULL) {
  $cascaded_settings = mm_content_get_cascaded_settings();
  $database = $database ?: Database::getConnection();

  $insert = function($mmtid, $name, $desc, $array_key, $data) use ($database) {
    if ($desc['data_type'] == 'int') {
      if ($data === '' || ($data = intval($data)) == -1) {
        return;
      }
    }
    elseif (!empty($desc['not_empty']) && empty($data)) {
      return;
    }

    if (!isset($desc['use_keys']) || !$desc['use_keys']) {
      $array_key = '';
    }

    $database->insert('mm_cascaded_settings')
      ->fields(array(
        'mmtid' => $mmtid,
        'name' => $name,
        'data_type' => $desc['data_type'],
        'multiple' => empty($desc['multiple']) ? 0 : 1,
        'array_key' => $array_key,
        'data' => $data,
      ))
      ->execute();
  };

  if ($delete) {
    $database->delete('mm_cascaded_settings')
      ->condition('mmtid', $mmtid)
      ->execute();
    mm_content_notify_change('clear_cascaded', $mmtid, NULL);
  }

  foreach ($cascaded_settings as $name => $desc) {
    if (isset($settings[$name])) {
      if (!empty($desc['multiple'])) {
        foreach ($settings[$name] as $array_key => $data) {
          $insert($mmtid, $name, $desc, $array_key, $data);
        }
      }
      else {
        $insert($mmtid, $name, $desc, '', $settings[$name]);
      }
    }
  }

  if ($added = array_intersect_key($settings, $cascaded_settings)) {
    mm_content_notify_change('insert_cascaded', $mmtid, NULL, $added);
  }
}

/**
 * Notify hook_mm_notify_change() implementations that a change has occurred in
 * one or more nodes or MM pages.
 *
 * @param string $type
 *   A string representing the type of change that occurred:
 *   - 'clear_cascaded':
 *     All cascaded settings have been cleared for the tree entries.
 *   - 'clear_flags':
 *     All flags have been cleared for the tree entries.
 *   - 'delete_node':
 *     The nodes with $nids, described by $data, have been permanently deleted.
 *   - 'delete_page':
 *     The tree entries with $mmtids have been permanently deleted.
 *   - 'insert_cascaded':
 *     One or more cascaded settings were added to the tree entries.
 *   - 'insert_flags':
 *     One or more flags were added to the tree entries.
 *   - 'insert_node':
 *     A node has been created. $data describes it.
 *   - 'insert_page':
 *     A tree entry has been created. $data describes it.
 *   - 'move_node':
 *     The nodes described by $nids have moved from $data['old_mmtid'] to
 *     $data['new_mmtid'].
 *   - 'move_page':
 *     The tree entries at $mmtids have moved from $data['old_parent'] to
 *     $data['new_parent'].
 *   - 'update_node':
 *     A node has been updated. $data describes the entire new state.
 *   - 'update_node_perms':
 *     The nodes' permissions have been modified to match $data.
 *   - 'update_page':
 *     A tree entry has been updated. $data describes the entire new state.
 *   - 'update_page_quick':
 *     A portion of the tree entry's settings have changed, according to $data.
 * @param int|array|null $mmtids
 *   A single tree ID or an array of IDs that were affected, or NULL if none
 * @param int|array|null $nids
 *   A single node ID or an array of IDs that were affected, or NULL if none
 * @param mixed $data
 *   A $type-specific description of the change
 */
function mm_content_notify_change($type, $mmtids = NULL, $nids = NULL, $data = NULL) {
  if (isset($nids) && !is_array($nids)) {
    $nids = array($nids);
  }
  else if (empty($nids)) {
    $nids = array();
  }

  if (isset($mmtids) && !is_array($mmtids)) {
    $mmtids = array($mmtids);
  }
  else if (empty($mmtids)) {
    $mmtids = array();
  }

  \Drupal::moduleHandler()->invokeAll('mm_notify_change', [$type, $mmtids, $nids, $data]);
}

/**
 * Scan a tree entry and its parents upward, looking for the closest change in a
 * cascaded setting.
 *
 * To retrieve the settings for a particular entry without considering its
 * parents, see mm_content_get_cascaded_settings().
 *
 * @param string $name
 *   Setting to look for
 * @param int $mmtid
 *   Tree ID of the entry (and its parents) to query
 * @param int $at
 *   Tree ID where the closest change occurs
 * @param int $parent
 *   Tree ID of the nearest parent after $at containing a change in state
 * @param bool $new_entry
 *   Set to TRUE if $mmtid is that of the (future) parent of a new child
 * @return mixed
 *   An array or single value (depending on the data type) containing the state
 *   of the given settings at the level $at
 */
function mm_content_resolve_cascaded_setting($name, $mmtid, &$at, &$parent, $new_entry = FALSE) {
  $q = Database::getConnection()->query('SELECT s.* FROM (SELECT :mmtid1 AS mmtid, 10000 AS depth UNION SELECT parent, depth FROM {mm_tree_parents} WHERE mmtid = :mmtid2) t INNER JOIN {mm_cascaded_settings} s ON s.mmtid = t.mmtid WHERE s.name = :name ORDER BY t.depth DESC',
    array(
      ':mmtid1' => $mmtid,
      ':mmtid2' => $mmtid,
      ':name' => $name,
    )
  );

  $out = array();
  $r = $q->fetch();
  while ($r) {
    $this_mmtid = $r->mmtid;
    if (is_array($out) && !$out) {
      if ($r->multiple) {
        do {
          if ($r->data_type == 'int') {
            $r->data = (int) $r->data;
          }

          if ($r->array_key != '') {
            $out[$r->array_key] = $r->data;
          }
          else {
            $out[] = $r->data;
          }

          $r = $q->fetch();
        } while ($r && $r->multiple && $r->mmtid == $this_mmtid);
      }
      else {
        if ($r->data_type == 'int') {
          $r->data = (int) $r->data;
        }
        $out = $r->data;
      }
      $at = $this_mmtid;
    }
    elseif ($r->multiple) {
      do {
        $r = $q->fetch();
      } while ($r && $r->multiple && $r->mmtid == $this_mmtid);
    }
    else {
      $r = $q->fetch();
    }

    if ($new_entry || $this_mmtid != $mmtid) {
      $parent = $this_mmtid;
      return $out;
    }
  }

  $parent = 0;
  if (!$out && $out !== 0) {
    $cascaded_settings = mm_content_get_cascaded_settings();
    if (!isset($cascaded_settings[$name]['multiple']) || !$cascaded_settings[$name]['multiple']) return NULL;
  }
  return $out;
}

/**
 * Get the parent tree ID of an entry
 *
 * @param int|array $mmtids
 *   Tree ID (or array of Tree IDs) of the entry whose parent we are looking for
 * @return int|array|null
 *   Tree ID of the parent (if a single $mmtid is supplied), or an array where
 *   the key is the child Tree ID and the value is the parent
 */
function mm_content_get_parent($mmtids) {
  if (is_array($mmtids)) {
    if (count($mmtids) == 1) {
      // If only one mmtid, use the simple case because it might be cached.
      $mmtid = array_pop($mmtids);
      $parent = mm_content_get_parent($mmtid);
      if (isset($parent)) {
        return array($mmtid => $parent);
      }
      return array();
    }
    return Database::getConnection()->select('mm_tree', 't')
      ->fields('t', array('mmtid', 'parent'))
      ->condition('mmtid', $mmtids, 'IN')
      ->execute()
      ->fetchAllKeyed();
  }

  $t = mm_content_get($mmtids);
  if ($t) {
    return $t->parent;
  }
  return NULL;
}

/**
 * Get all parent tree IDs of a entry
 *
 * @param int $mmtid
 *   Tree ID of the entry whose parent we are looking for
 * @param bool $slow
 *   If TRUE, don't rely on the 'parents' field of the mm_tree table, instead
 *   slowly traverse up the tree
 * @param bool $virtual
 *   If TRUE, include the negative IDs that are added to children of the /.Users
 *   entry by mm_content_get_tree().
 * @return array
 *   Array of parent tree IDs, listed highest-first
 */
function mm_content_get_parents($mmtid, $slow = FALSE, $virtual = TRUE) {
  $_mmtbt_cache = &drupal_static('_mmtbt_cache', array());
  $_mmgp_cache = &drupal_static('_mmgp_cache', array());

  $list = array();
  $mmtid0 = $mmtid;

  if ($mmtid < 0) {
    return array(1, mm_content_users_mmtid());
  }

  if (!$slow) {
    if ($mmtid == 1) {
      return $list;
    }
    while ($mmtid > 1 && isset($_mmgp_cache[$mmtid])) {
      array_unshift($list, $mmtid = $_mmgp_cache[$mmtid]);
    }

    if ($mmtid > 1) {
      $r = mm_content_get($mmtid, Constants::MM_GET_PARENTS);
      if (empty($r)) {
        return $list;
      }

      $list = array_merge($r->parents, $list);
      $prev = $mmtid0;
      foreach (array_reverse($list) as $m) {
        $_mmgp_cache[$prev] = $m;
        $prev = $m;
      }
    }
  }
  else {
    $last = -1;
    do {
      $mmtid = mm_content_get_parent($mmtid);
      if ($mmtid) {
        array_unshift($list, $mmtid);
      }

      if ($mmtid == $last) {
        // shouldn't happen, but just in case
        break;
      }

      $last = $mmtid;
    }
    while ($mmtid > 1);
  }

  $virtual = $virtual && mm_get_setting('user_homepages.virtual');
  if ($virtual && count($list) >= 2 && $list[1] == mm_content_users_mmtid()) {
    $m = count($list) >= 3 ? $list[2] : $mmtid0;
    $tree = isset($_mmtbt_cache[$m]) ? $_mmtbt_cache[$m] : mm_content_get($m);

    if ($tree) {
      $letr = mb_strtoupper($tree->name[0]);
      $alias = ctype_alpha($letr) ? $letr : '~';
      array_splice($list, 2, 0, -ord($alias));
    }
  }

  return $list;
}

/**
 * Get all parent tree IDs of an entry, plus the ID itself
 *
 * @param int $mmtid
 *   Tree ID of the entry whose parent we are looking for
 * @param bool $slow
 *   If TRUE, don't rely on the 'parents' field of the mm_tree table, instead
 *   slowly traverse up the tree
 * @param bool $virtual
 *   If TRUE, include the negative IDs that are added to children of the
 *   /.Users entry by mm_content_get_tree().
 * @return array
 *   Array of parent tree IDs, listed highest-first, with $mmtid at the end
 */
function mm_content_get_parents_with_self($mmtid, $slow = FALSE, $virtual = TRUE) {
  $list = mm_content_get_parents($mmtid, $slow, $virtual);
  $list[] = $mmtid;
  return $list;
}

/**
 * Get the full tree path of a tree ID
 *
 * @param int $mmtid
 *   Tree ID of the page whose path we are looking for
 * @return string
 *   Full path in the format 1/7/234/847
 */
function mm_content_get_full_path($mmtid) {
  return join('/', mm_content_get_parents_with_self($mmtid));
}

/**
 * Get a page's name.
 *
 * @param int|object $mmtid_or_tree
 *   Tree ID or full tree object of the page whose name is being requested. If
 *   known, it is better to provide the full object, to avoid extra queries to
 *   the database.
 * @return string
 *   The expanded name
 * @see hook_mm_mmtid_name
 */
function mm_content_get_name($mmtid_or_tree) {
  static $drupal_static_fast;

  if (!is_object($mmtid_or_tree)) {
    if (!($mmtid_or_tree = mm_content_get($mmtid_or_tree))) {
      return '';
    }
  }

  if (!isset($drupal_static_fast)) {
    // This is cumbersome, but assigning to an array is the only way that works.
    $drupal_static_fast['table'] = &drupal_static(__FUNCTION__, array());
    // We can't use (mm_)module_invoke_all() as it does not handle numeric keys
    // (mmtids, in this case) correctly.
    foreach (mm_module_implements('mm_mmtid_name') as $module) {
      $drupal_static_fast['table'] += \Drupal::moduleHandler()->invoke($module, 'mm_mmtid_name');
    }
  }

  $mmtid = $mmtid_or_tree->mmtid;
  if (empty($mmtid_or_tree->name)) {
    $mmtid_or_tree->name = t('Undefined @number', ['@number' => $mmtid]);
  }

  $table = &$drupal_static_fast['table'];
  if (isset($table[$mmtid])) {
    if (is_array($table[$mmtid])) {
      if (isset($table[$mmtid]['callback']) && function_exists($table[$mmtid]['callback'])) {
        $result = call_user_func($table[$mmtid]['callback'], $mmtid_or_tree);
        if (!empty($result)) {
          return $result;
        }
      }
      if (!empty($table[$mmtid]['name'])) {
        return $table[$mmtid]['name'];
      }
    }
    else {
      return $table[$mmtid];
    }
  }

  return mm_content_expand_name($mmtid_or_tree->name);
}

/**
 * Get a list of tree entries, using their tree IDs or other attributes.
 *
 * @param mixed $options
 *   Either a single tree ID, an array of tree IDs, or an associative array
 *   containing key => value pairs of attributes to query. When using an
 *   associative array, the value can be an array of values. The allowed keys
 *   are all of the columns in the mm_tree table, plus:
 *   - query: a sub-query which returns a list of mmtids to query against
 *   - flags: an array of key => value pairs which are ANDed together; a NULL
 *            value becomes IS NULL in the query
 * @param array|string $return
 *   A single value, or an array of values, from the list of constants below:
 *   - MM_GET_ARCHIVE: return archive status (mm_archive)
 *   - MM_GET_FLAGS:   return flags (mm_tree_flags)
 *   - MM_GET_PARENTS: return parents (mm_tree_parents)
 * @param int $limit
 *   Optional maximum number of results to return (0)
 * @param bool $sort
 *   If TRUE, sort the results by their position in the tree (FALSE)
 * @return array|object
 *   If $options['mmtids'] is a single tree ID, return the one tree object.
 *   Otherwise, return an array of tree objects (order is random).
 */
function mm_content_get($options, $return = array(), $limit = 0, $sort = FALSE) {
  $_mmtbt_cache = &drupal_static('_mmtbt_cache', array());

  $single = FALSE;
  if (!is_array($options)) {
    $single = TRUE;
    $options = array('mmtid' => array($options));
  }
  elseif (is_numeric(mm_ui_mmlist_key0($options))) {
    $options = array('mmtid' => $options);
  }

  if (!is_array($return)) {
    $return = array($return);
  }
  $return = array_flip($return);

  $out = $args = $wheres = $joins = array();
  $add_field = $group_by = '';

  // Use a cache in the simple case where the only keys are mmtids
  if (isset($options['mmtid']) && count(array_keys($options)) == 1 && !$sort) {
    if (!is_array($options['mmtid'])) $options['mmtid'] = array($options['mmtid']);

    foreach ($options['mmtid'] as $key => $mmtid) {
      if (isset($_mmtbt_cache[$mmtid]) && (!isset($return[Constants::MM_GET_ARCHIVE]) || isset($_mmtbt_cache[$mmtid]->archive_cached)) && (!isset($return[Constants::MM_GET_FLAGS]) || isset($_mmtbt_cache[$mmtid]->flags)) && (!isset($return[Constants::MM_GET_PARENTS]) || isset($_mmtbt_cache[$mmtid]->parents))) {
        if (!$limit || count($out) < $limit) {
          $out[] = clone $_mmtbt_cache[$mmtid];
        }
        unset($options['mmtid'][$key]);
      }
      elseif ($mmtid < 0) {
        if (!$limit || count($out) < $limit) {
          $out[] = _mm_content_virtual_dir($mmtid, mm_content_users_mmtid(), 0, 0);
        }
        unset($options['mmtid'][$key]);
      }
      elseif (!is_numeric($mmtid) || !$mmtid) {
        unset($options['mmtid'][$key]);
      }
    }
    // Reset array keys after unset()
    $options['mmtid'] = array_merge($options['mmtid']);
  }

  if (isset($options['query']) && !isset($options['mmtid'])) {
    $wheres[] = 't.mmtid IN (' . $options['query'] . ')';
    $single = FALSE;
    unset($options['query']);
  }

  if (isset($options['flags']) && is_array($options['flags'])) {
    $n = 0;
    foreach ($options['flags'] as $flag => $data) {
      $joins[] = "LEFT JOIN {mm_tree_flags} f$n ON f$n.mmtid = t.mmtid";
      $wheres[] = "f$n.flag = :ff$n";
      $args[":ff$n"] = $flag;
      if (is_null($data)) {
        $wheres[] = "f$n.data IS NULL";
      }
      else {
        $wheres[] = "f$n.data = :fd$n";
        $args[":fd$n"] = $data;
      }
      $n++;
    }
    $single = FALSE;
    unset($options['flags']);
  }

  $n = 0;
  foreach ($options as $k => $v) {
    if (!is_array($v) || $v) {
      if (is_array($v) && count($v) == 1) {
        $v = $v[0];
      }

      $k = strtolower($k);
      if (strchr($k, '.') === FALSE) {
        $k = "t.$k";
      }

      if (is_array($v)) {
        $vals = array();
        foreach ($v as $v2) {
          $vals[] = ":v$n";
          $args[":v$n"] = $v2;
          $n++;
        }
        $wheres[] = "$k IN(" . join(', ', $vals) . ')';
      }
      else {
        $wheres[] = "$k = :v$n";
        $args[":v$n"] = $v;
        $n++;
      }
    }
  }

  if ($limit) {
    // Consider cached data already copied to $out
    $limit -= count($out);
    if ($limit <= 0) {
      return $single ? $out[0] : $out;
    }
  }

  if (isset($return[Constants::MM_GET_FLAGS])) {
    $joins[] = 'LEFT JOIN {mm_tree_flags} f ON f.mmtid = t.mmtid';
    $add_field .= ", GROUP_CONCAT(DISTINCT CONCAT_WS('|1', f.flag, f.data) SEPARATOR '|2') AS flags";
    $group_by = ' GROUP BY t.mmtid';
  }

  if (isset($return[Constants::MM_GET_PARENTS])) {
    $joins[] = 'LEFT JOIN {mm_tree_parents} p ON p.mmtid = t.mmtid';
    $add_field .= ', GROUP_CONCAT(DISTINCT p.parent ORDER BY p.depth) AS parents';
    $group_by = ' GROUP BY t.mmtid';
  }

  if (isset($return[Constants::MM_GET_ARCHIVE])) {
    $joins[] = 'LEFT JOIN {mm_archive} a ON a.main_mmtid = t.mmtid OR a.archive_mmtid = t.mmtid';
    $add_field .= ', ' . ($group_by ? mm_all_db_columns('mm_archive', ['main_mmtid'], 'a.') : 'a.*') . ', 1 AS archive_cached';
  }

  if (!empty($wheres)) {
    $space = count($joins) ? ' ' : '';
    $query = 'SELECT ' . ($group_by ? mm_all_db_columns('mm_tree', ['mmtid'], 't.') : 't.*') . "$add_field FROM {mm_tree} t$space" . join(' ', $joins) . ' WHERE ' . join(' AND ', $wheres) . $group_by;
    if ($sort) {
      $query .= ' ORDER BY t.sort_idx';
    }

    if ($limit) {
      $q = Database::getConnection()->queryRange($query, 0, $limit, $args);
    }
    else {
      $q = Database::getConnection()->query($query, $args);
    }

    foreach ($q as $r) {
      if (isset($return[Constants::MM_GET_FLAGS])) {
        $r->flags = _mm_content_split_flags($r->flags);
      }
      elseif (isset($_mmtbt_cache[$r->mmtid]) && isset($_mmtbt_cache[$r->mmtid]->flags)) {
        $r->flags = $_mmtbt_cache[$r->mmtid]->flags;
      }

      if (isset($return[Constants::MM_GET_PARENTS]) && !is_array($r->parents)) {
        $r->parents = empty($r->parents) ? array() : explode(',', $r->parents);
      }
      elseif (isset($_mmtbt_cache[$r->mmtid]) && isset($_mmtbt_cache[$r->mmtid]->parents)) {
        $r->parents = $_mmtbt_cache[$r->mmtid]->parents;
      }

      if (!isset($return[Constants::MM_GET_ARCHIVE]) && isset($_mmtbt_cache[$r->mmtid]) && isset($_mmtbt_cache[$r->mmtid]->archive_cached)) {
        $r->archive_cached = 1;
        $r->main_mmtid = $_mmtbt_cache[$r->mmtid]->main_mmtid;
        $r->archive_mmtid = $_mmtbt_cache[$r->mmtid]->archive_mmtid;
        $r->frequency = $_mmtbt_cache[$r->mmtid]->frequency;
        $r->main_nodes = $_mmtbt_cache[$r->mmtid]->main_nodes;
      }

      $_mmtbt_cache[$r->mmtid] = $out[] = $r;
    }
  }

  return $single && isset($out[0]) ? $out[0] : $out;
}

/**
 * Update a tree entry's list of parent nodes, or update the lists for all
 * entries in the tree.
 *
 * @param int $mmtid
 *   ID of the entry to update, or NULL to update all entries
 * @param array|null $parents
 *   Array of parent IDs, or NULL to recalculate from the tree
 * @param bool $is_new
 *   Set to TRUE if the entry doesn't already have parents, to avoid an extra
 *   DELETE
 * @param bool $force
 *   When $mmtid is NULL, set this parameter to TRUE in order to force all
 *   entries to be updated, not just those that currently have no 'parents' info
 */
function mm_content_update_parents($mmtid = NULL, $parents = NULL, $is_new = FALSE, $force = FALSE) {
  $db = Database::getConnection();
  if (is_null($mmtid)) {
    $select = $db->select('mm_tree', 't')
      ->fields('t', array('mmtid'));

    if ($force) {
      // SELECT t.mmtid FROM {mm_tree} t
      // LEFT JOIN {mm_tree_parents} p ON p.mmtid = t.mmtid
      // WHERE t.parent > 0 AND p.parent IS NULL
      $select->leftJoin('mm_tree_parents', 'p', 'p.mmtid = t.mmtid');
      $select->condition('t.parent', 0, '>')
        ->isNull('p.parent');
    }

    $result = $select->execute();

    foreach ($result as $r) {
      mm_content_update_parents($r->mmtid, NULL);
    }

    return;
  }

  $txn = $db->startTransaction();  // Lock DB.

  if (is_null($parents)) {
    $parents = mm_content_get_parents($mmtid, TRUE, FALSE);
  }

  if ($parents && !$is_new) {
    _mm_content_clear_access_cache($mmtid);
  }

  $db->delete('mm_tree_parents')
    ->condition('mmtid', $mmtid)
    ->execute();

  if ($parents) {
    if (!$is_new) {
      // UPDATE {mm_tree} SET parent = <last parent> WHERE mmtid = <mmtid>
      $db->update('mm_tree')
        ->fields(array('parent' => $parents[count($parents) - 1]))
        ->condition('mmtid', $mmtid)
        ->execute();
    }

    $insert = $db->insert('mm_tree_parents')
      ->fields(array('mmtid', 'parent', 'depth'));
    foreach ($parents as $depth => $parent) {
      $insert->values(array(
        'mmtid' => $mmtid,
        'parent' => $parent,
        'depth' => $depth));
    }
    $insert->execute();
  }

  // Update the variable which controls how many nested joins are performed when
  // rewriting inbound URLs.
  $max_depth = $db->query('SELECT MAX(depth) FROM {mm_tree_parents}')->fetchField() + 1;
  $new_max = min($max_depth, Constants::MM_CONTENT_MYSQL_MAX_JOINS);
  if (\Drupal::state()->get('monster_menus.mysql_max_joins', Constants::MM_CONTENT_MYSQL_MAX_JOINS) !== $new_max) {
    \Drupal::state()->set('monster_menus.mysql_max_joins', $new_max);
  }

  mm_module_invoke_all('mm_content_update_parents', $mmtid, $parents, $is_new);
}

/**
 * Return the Url for a tree entry
 *
 * @param int $mmtid
 *   ID of the entry
 * @param array $options
 *   @see \Drupal\Core\Url::fromUri() for details.
 * @return Url
 *   A new Url object for a routed (internal to Drupal) URL.
 */
function mm_content_get_mmtid_url($mmtid, array $options = []) {
  return Url::fromRoute('entity.mm_tree.canonical', ['mm_tree' => $mmtid], $options);
}

/**
 * Return a list of tree IDs to which a given node is assigned
 *
 * @param int|array $nids
 *   Node ID or array of node IDs to query
 * @param bool $reset
 *   If TRUE, clear the cache
 * @param Connection $database
 *   (optional) The database connection to use.
 * @return array
 *   If $nids is a single nid, return an array of tree IDs, otherwise return an
 *   outer array keyed by nid, where each value is an array of mmtids.
 */
function mm_content_get_by_nid($nids, $reset = FALSE, Connection $database = NULL) {
  if ($reset) {
    drupal_static(__FUNCTION__, array(), TRUE);
    return;
  }

  $database = $database ?: Database::getConnection();
  $db_key = $database->getKey();
  $cache = &drupal_static(__FUNCTION__, array());
  if (!isset($cache[$db_key])) {
    $cache[$db_key] = array();
  }

  $want_array = TRUE;
  if (!is_array($nids)) {
    $want_array = FALSE;
    $nids = array($nids);
  }

  $needed = array_diff($nids, array_keys($cache[$db_key]));
  if ($needed) {
    $result = $database->select('mm_node2tree', 't')
      ->fields('t', array('mmtid', 'nid'))
      ->condition('t.nid', $needed, 'IN')
      ->execute();
    foreach ($result as $r) {
      $cache[$db_key][$r->nid][] = $r->mmtid;
    }
  }

  if (!$want_array) {
    return isset($cache[$db_key][$nids[0]]) ? $cache[$db_key][$nids[0]] : array();
  }

  return array_intersect_key($cache[$db_key], array_flip($nids));
}

/**
 * Figure out if a user can see or delete a recycle bin
 *
 * @param int $mmtid
 *   ID of the bin being queried
 * @param string $mode
 *   If set, return whether or not the user can perform that action
 *   (MM_PERMS_READ (see), MM_PERMS_WRITE (delete)). Otherwise, return an array
 *   containing these elements with either TRUE or FALSE values. There is also a
 *   special mode, MM_PERMS_IS_EMPTYABLE, which returns TRUE if the user has
 *   permission to empty the entire bin (i.e.: has write on everything in it.)
 * @param AccountInterface $usr
 *   User object of the user to test, or NULL to test the current user.
 * @return mixed
 *   See above
 */
function mm_content_user_can_recycle($mmtid, $mode = '', AccountInterface $usr = NULL) {
  $user = \Drupal::currentUser();
  $_mmucr_cache = &drupal_static('_mmucr_cache', array());

  if (!$usr) $usr = $user;
  $uid = $usr->id();

  if ($mode != '' ? !isset($_mmucr_cache[$mmtid][$uid][$mode]) : !isset($_mmucr_cache[$mmtid][$uid])) {
    $iter = new ContentUserCanRecycleIter($mode, $uid == $user->id() ? NULL : $usr);
    if (mm_content_user_can(mm_content_get_parent($mmtid), Constants::MM_PERMS_READ, $usr)) {
      $params = array(
        Constants::MM_GET_TREE_FAKE_READ_BINS => TRUE,
        Constants::MM_GET_TREE_USER           => $usr,
        Constants::MM_GET_TREE_RETURN_PERMS   => TRUE,
        Constants::MM_GET_TREE_DEPTH          => 1,
        Constants::MM_GET_TREE_ITERATOR       => $iter,
      );
      mm_content_get_tree($mmtid, $params);
    }
    elseif ($mode == Constants::MM_PERMS_IS_EMPTYABLE) {
      $iter->emptyable = FALSE;
    }

    if ($mode == Constants::MM_PERMS_IS_EMPTYABLE) {
      $_mmucr_cache[$mmtid][$uid][Constants::MM_PERMS_IS_EMPTYABLE] = $iter->emptyable && $usr->hasPermission('delete permanently');
    }
    else {
      $_mmucr_cache[$mmtid][$uid][Constants::MM_PERMS_READ] = $iter->readable;
      $_mmucr_cache[$mmtid][$uid][Constants::MM_PERMS_WRITE] = $iter->writable;
    }
  }       // !isset($_mmucr_cache[$mmtid][$uid])

  if ($mode != '') {
    return isset($_mmucr_cache[$mmtid][$uid][$mode]) ? $_mmucr_cache[$mmtid][$uid][$mode] : FALSE;
  }

  return $_mmucr_cache[$mmtid][$uid];
}

/**
 * Figure out if a given user can access a particular tree ID
 *
 * @param int $mmtid
 *   ID of the term being queried
 * @param string $mode
 *   If set, return whether or not the user can perform that action
 *   (MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB, MM_PERMS_APPLY,
 *   MM_PERMS_IS_USER, MM_PERMS_IS_GROUP, MM_PERMS_IS_RECYCLE_BIN,
 *   MM_PERMS_IS_RECYCLED). Otherwise, return an array containing each of these
 *   permissions with either TRUE or FALSE values.
 * @param AccountInterface $usr
 *   User object to test against. Defaults to the current user.
 * @param bool $bias_anon
 *   If TRUE, assume user 0 can't read any groups (faster, more secure)
 * @return bool|array
 *   See above
 */
function mm_content_user_can($mmtid, $mode = '', AccountInterface $usr = NULL, $bias_anon = TRUE) {
  $_mmuc_cache = &drupal_static('_mmuc_cache', array());
  if (!$usr) $usr = \Drupal::currentUser();
  $uid = $usr->id();

  $mmtid = intval($mmtid);
  if (!empty($mode) ? !isset($_mmuc_cache[$mmtid][$uid][$mode]) : !isset($_mmuc_cache[$mmtid][$uid])) {
    // set default values, in case mmtid does not exist
    $_mmuc_cache[$mmtid][$uid] = array(
      Constants::MM_PERMS_WRITE          => FALSE,
      Constants::MM_PERMS_SUB            => FALSE,
      Constants::MM_PERMS_APPLY          => FALSE,
      Constants::MM_PERMS_READ           => FALSE,
      Constants::MM_PERMS_IS_USER        => FALSE,
      Constants::MM_PERMS_IS_GROUP       => FALSE,
      Constants::MM_PERMS_IS_RECYCLE_BIN => FALSE,
      Constants::MM_PERMS_IS_RECYCLED    => FALSE,
    );
    if ($mmtid < 0) {
      // speedup for virtual user directory (A-Z)
      $_mmuc_cache[$mmtid][$uid][Constants::MM_PERMS_READ] = $_mmuc_cache[$mmtid][$uid][Constants::MM_PERMS_IS_USER] = TRUE;
    }
    elseif ($mmtid) {
      $cid = "$mmtid::$uid";
      $cached = _mm_content_access_cache($cid);
      if (is_array($cached)) {
        $_mmuc_cache[$mmtid][$uid] = $cached;
      }
      else {
        $params = array(
          Constants::MM_GET_TREE_BIAS_ANON      => $bias_anon,
          Constants::MM_GET_TREE_FAKE_READ_BINS => TRUE,
          Constants::MM_GET_TREE_MMTID          => $mmtid,
          Constants::MM_GET_TREE_RETURN_BINS    => TRUE,
          Constants::MM_GET_TREE_RETURN_PERMS   => TRUE,
          Constants::MM_GET_TREE_USER           => $usr,
        );
        $row = Database::getConnection()->query(mm_content_get_query($params))->fetchObject();
        if ($row) {
          $bins = array();
          foreach ((array)$row as $key => $val) {
            if ($key == 'recycle_bins') {
              if (!empty($val)) $bins = explode(',', $val);
            }
            else {
              $_mmuc_cache[$mmtid][$uid][$key] = $val != 0;
            }
          }

          // it's too expensive to do this in the query
          if ($_mmuc_cache[$mmtid][$uid][Constants::MM_PERMS_IS_RECYCLE_BIN]) {
            $_mmuc_cache[$mmtid][$uid][Constants::MM_PERMS_APPLY] = TRUE;
            $_mmuc_cache[$mmtid][$uid][Constants::MM_PERMS_READ] = mm_content_user_can_recycle($mmtid, Constants::MM_PERMS_READ, $usr);
          }
          else {
            // re-calculate the MM_PERMS_READ flag for anything in a bin
            foreach ($bins as $bin) {
              $_mmuc_cache[$mmtid][$uid][Constants::MM_PERMS_READ] = $_mmuc_cache[$mmtid][$uid][Constants::MM_PERMS_READ] && mm_content_user_can_recycle($bin, Constants::MM_PERMS_READ, $usr);
            }
          }
        }
        _mm_content_access_cache($cid, $_mmuc_cache[$mmtid][$uid], $uid, 0, $mmtid);
      }
    }
  }

  if (!empty($mode)) {
    return $_mmuc_cache[$mmtid][$uid][$mode];
  }

  return $_mmuc_cache[$mmtid][$uid];
}

/**
 * Get a database query to return a part of the tree, or to determine whether or
 * not a user has permission to access a particular node or part of the tree
 *
 * @param array $params
 *   An array containing parameters. The array is indexed using the constants
 *   below. Either [MM_GET_TREE_NODE] or [MM_GET_TREE_MMTID] must be specified.
 *   - MM_GET_TREE_ADD_SELECT (none):
 *     A string or array of strings to add to the SELECT portion of the query
 *   - MM_GET_TREE_BIAS_ANON (TRUE):
 *     If TRUE, assume user 0 can't read any groups (more secure)
 *   - MM_GET_TREE_DEPTH (0):
 *     When 'mmtid' is used, a query to return all items in the tree below that
 *     point can be returned. This field specifies the depth of recursion:
 *     - 0:  just the item specified by MM_GET_TREE_MMTID
 *     - -1: all levels
 *     - 1:  the item and its immediate children
 *     - N:  any other other number will return that many levels (can be slow)
 *   - MM_GET_TREE_FAKE_READ_BINS (FALSE):
 *     Pretend the user can read all recycle bins (used internally)
 *   - MM_GET_TREE_FILTER_BINS (TRUE):
 *     Get entries that are recycle bins
 *   - MM_GET_TREE_FILTER_DOTS (TRUE):
 *     Get all entries with names that start with '.'. If FALSE, only .Groups,
 *     .Users, and .Virtual are returned.
 *   - MM_GET_TREE_FILTER_GROUPS (TRUE):
 *     Get entries that are groups (MM_GET_TREE_MMTID mode)
 *   - MM_GET_TREE_FILTER_NORMAL (TRUE):
 *     Get entries that are neither groups nor in /users (MM_GET_TREE_MMTID
 *     mode)
 *   - MM_GET_TREE_FILTER_USERS (TRUE):
 *     Get entries in /users (MM_GET_TREE_MMTID mode)
 *   - MM_GET_TREE_INNER_FILTER:
 *     Used internally
 *   - MM_GET_TREE_MMTID:
 *     Tree ID to query
 *   - MM_GET_TREE_NODE:
 *     NodeInterface object to query permissions for
 *   - MM_GET_TREE_RETURN_BINS (FALSE):
 *     A comma-separated list of the mmtids of any parent recycle bins
 *   - MM_GET_TREE_RETURN_BLOCK (FALSE):
 *     Attributes from the mm_tree_block table (MM_GET_TREE_MMTID mode)
 *   - MM_GET_TREE_RETURN_FLAGS (FALSE):
 *     Flags from the mm_tree_flags table (MM_GET_TREE_MMTID mode)
 *   - MM_GET_TREE_RETURN_KID_COUNT (FALSE):
 *     A count of the number of children each tree entry has (MM_GET_TREE_MMTID
 *     mode)
 *   - MM_GET_TREE_RETURN_MTIME (FALSE):
 *     The muid (user ID who made the last modification) and mtime (time) of the
 *     modification
 *   - MM_GET_TREE_RETURN_NODE_COUNT (FALSE):
 *     If TRUE, return a count of the number of nodes assigned to each item. If
 *     a string or array of strings, return a count of the number of nodes of
 *     that type.
 *   - MM_GET_TREE_RETURN_PERMS (none):
 *     If set, return whether or not the user can perform that action
 *     (MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB, MM_PERMS_APPLY,
 *     MM_PERMS_IS_USER, MM_PERMS_IS_GROUP, MM_PERMS_IS_RECYCLE_BIN,
 *     MM_PERMS_IS_RECYCLED). Only (MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB,
 *     MM_PERMS_APPLY) are supported when [MM_GET_TREE_NODE] is used. The
 *     requested permission can either be a single value or an array. If an
 *     empty array or TRUE is passed, all permissions are returned.
 *   - MM_GET_TREE_RETURN_TREE (FALSE):
 *     Attributes from the mm_tree table (MM_GET_TREE_MMTID mode)
 *   - MM_GET_TREE_SORT (FALSE):
 *     If TRUE, sort the entries according to sort_idx; always TRUE when
 *     MM_GET_TREE_DEPTH != 0
 *   - MM_GET_TREE_USER (current user):
 *     User object to test permissions against
 *   - MM_GET_TREE_WHERE (none):
 *     Add a WHERE clause to the outermost query
 *   If none of ([...USERS], [...GROUPS], [...NORMAL]) is TRUE, all types are
 *   retrieved by the query.
 * @return string|void
 *   The query string
 */
function mm_content_get_query(array $params) {
  static $user_access;
  /** @var NodeInterface $params[MM_GET_TREE_NODE] */

  if (!isset($user_access)) {
    $user_access = &drupal_static(__FUNCTION__);
  }

  $defaults = array(
    Constants::MM_GET_TREE_BIAS_ANON        => TRUE,
    Constants::MM_GET_TREE_DEPTH            => 0,
    Constants::MM_GET_TREE_FAKE_READ_BINS   => FALSE,
    Constants::MM_GET_TREE_FILTER_BINS      => TRUE,
    Constants::MM_GET_TREE_FILTER_DOTS      => TRUE,
    Constants::MM_GET_TREE_FILTER_GROUPS    => FALSE,
    Constants::MM_GET_TREE_FILTER_NORMAL    => FALSE,
    Constants::MM_GET_TREE_FILTER_USERS     => FALSE,
    Constants::MM_GET_TREE_INNER_FILTER     => '',
    Constants::MM_GET_TREE_RETURN_BINS      => FALSE,
    Constants::MM_GET_TREE_RETURN_BLOCK     => FALSE,
    Constants::MM_GET_TREE_RETURN_FLAGS     => FALSE,
    Constants::MM_GET_TREE_RETURN_KID_COUNT => FALSE,
    Constants::MM_GET_TREE_RETURN_MTIME     => FALSE,
    Constants::MM_GET_TREE_RETURN_TREE      => FALSE,
    Constants::MM_GET_TREE_USER             => \Drupal::currentUser(),
    Constants::MM_GET_TREE_WHERE            => '',
  );

  $params = array_merge($defaults, $params);
  if (!$params[Constants::MM_GET_TREE_FILTER_GROUPS] && !$params[Constants::MM_GET_TREE_FILTER_USERS] && !$params[Constants::MM_GET_TREE_FILTER_NORMAL]) {
    $params[Constants::MM_GET_TREE_FILTER_GROUPS] = $params[Constants::MM_GET_TREE_FILTER_USERS] = $params[Constants::MM_GET_TREE_FILTER_NORMAL] = TRUE;
  }

  $is_node = isset($params[Constants::MM_GET_TREE_NODE]);

  if (isset($params[Constants::MM_GET_TREE_RETURN_PERMS]) && $params[Constants::MM_GET_TREE_RETURN_PERMS] === TRUE ||
      ($is_node ? !isset($params[Constants::MM_GET_TREE_RETURN_PERMS]) || !$params[Constants::MM_GET_TREE_RETURN_PERMS] :
      isset($params[Constants::MM_GET_TREE_RETURN_PERMS]) && empty($params[Constants::MM_GET_TREE_RETURN_PERMS]))) {
    $params[Constants::MM_GET_TREE_RETURN_PERMS] = array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ, Constants::MM_PERMS_IS_USER, Constants::MM_PERMS_IS_GROUP, Constants::MM_PERMS_IS_RECYCLE_BIN, Constants::MM_PERMS_IS_RECYCLED);
  }
  elseif (!isset($params[Constants::MM_GET_TREE_RETURN_PERMS])) {
    $params[Constants::MM_GET_TREE_RETURN_PERMS] = array();
  }
  elseif (!is_array($params[Constants::MM_GET_TREE_RETURN_PERMS])) {
    $params[Constants::MM_GET_TREE_RETURN_PERMS] = array($params[Constants::MM_GET_TREE_RETURN_PERMS]);
  }
  $perms = array_flip($params[Constants::MM_GET_TREE_RETURN_PERMS]);

  if (!$is_node && empty($params[Constants::MM_GET_TREE_MMTID])) {
    \Drupal::logger('mm')->error('mm_content_get_query() called without a node ID or MM tree ID.', array());
    return;
  }

  // TODO: fix for recursive where mmtid<0
  if (!$is_node && $params[Constants::MM_GET_TREE_DEPTH] == 0 && $params[Constants::MM_GET_TREE_MMTID] < 0) {
    // virtual user directory (A-Z)
    return 'SELECT 0 AS ' . Constants::MM_PERMS_WRITE . ', 0 AS ' . Constants::MM_PERMS_SUB . ', 0 AS ' . Constants::MM_PERMS_APPLY . ', 0 AS ' . Constants::MM_PERMS_READ . ', 1 AS ' . Constants::MM_PERMS_IS_USER . ', 0 AS ' . Constants::MM_PERMS_IS_GROUP . ', 0 AS ' . Constants::MM_PERMS_IS_RECYCLE_BIN . ', 0 AS ' . Constants::MM_PERMS_IS_RECYCLED;
  }

  $uid = $params[Constants::MM_GET_TREE_USER]->id();

  $is_admin = $uid == 1;
  if (!isset($user_access[$uid])) {
    foreach (array('administer all menus', 'administer all users', 'administer all groups', 'view all menus') as $access_mode) {
      if ($uid || substr($access_mode, 0, 4) == 'view') {
        $user_access[$uid][$access_mode] = $params[Constants::MM_GET_TREE_USER]->hasPermission($access_mode);
      }
      else {
        $user_access[$uid][$access_mode] = FALSE;
      }
    }
  }
  $is_admin |= $user_access[$uid]['administer all menus'];

  $outside_selects = array();
  if (isset($params[Constants::MM_GET_TREE_ADD_SELECT])) {
    if (is_array($params[Constants::MM_GET_TREE_ADD_SELECT])) {
      $outside_selects = $params[Constants::MM_GET_TREE_ADD_SELECT];
    }
    else {
      $outside_selects[] = $params[Constants::MM_GET_TREE_ADD_SELECT];
    }
  }

  if (isset($params[Constants::MM_GET_TREE_RETURN_NODE_COUNT]) && !empty($params[Constants::MM_GET_TREE_RETURN_NODE_COUNT])) {
    if (is_array($params[Constants::MM_GET_TREE_RETURN_NODE_COUNT])) {
      $compare = " AND node.type IN ('" . join("', '", $params[Constants::MM_GET_TREE_RETURN_NODE_COUNT]) . "')";
    }
    elseif (is_string($params[Constants::MM_GET_TREE_RETURN_NODE_COUNT])) {
      $compare = " AND node.type = '" . $params[Constants::MM_GET_TREE_RETURN_NODE_COUNT] . "'";
    }
    else {
      $compare = '';
    }
    $outside_selects[] = "(SELECT COUNT(DISTINCT n.nid) FROM {mm_node2tree} n INNER JOIN {node} node ON node.nid = n.nid WHERE n.mmtid = o.container$compare) AS nodecount";
  }

  $outside_group_by = $node_selects = array();
  $outside_where = $params[Constants::MM_GET_TREE_WHERE];
  $inside_joins = $outside_joins = $outside_order_by = $anon_group = '';
  $inside_selects = $inside_group_by = array('i.mmtid');
  $having = '';
  $filter_dots = "(SUBSTR(name, 1, 1) <> '.' OR name IN('" . Constants::MM_ENTRY_NAME_GROUPS . "', '" . Constants::MM_ENTRY_NAME_USERS . "', '" . Constants::MM_ENTRY_NAME_VIRTUAL_GROUP . "'))";

  $inside_selects[] = 'i.container';
  $inside_group_by[] = 'i.container, i.name';
  $outside_group_by[] = 'o.container';

  if (!$is_node && (!$params[Constants::MM_GET_TREE_FILTER_GROUPS] || !$params[Constants::MM_GET_TREE_FILTER_USERS] || !$params[Constants::MM_GET_TREE_FILTER_NORMAL] || !$params[Constants::MM_GET_TREE_FILTER_BINS] || !$params[Constants::MM_GET_TREE_FILTER_DOTS])) {
    $havings = array();
    if ($params[Constants::MM_GET_TREE_FILTER_GROUPS]) {
      $perms[Constants::MM_PERMS_IS_GROUP] = 1;
      $havings[] = 'SUM(o.is_group) > 0';
    }

    if ($params[Constants::MM_GET_TREE_FILTER_NORMAL]) {
      $perms[Constants::MM_PERMS_IS_USER] = $perms[Constants::MM_PERMS_IS_GROUP] = 1;
      if ($params[Constants::MM_GET_TREE_FILTER_USERS] && !$params[Constants::MM_GET_TREE_FILTER_GROUPS]) {
        $havings[] = 'SUM(o.is_group) = 0';
      }
      else {
        $havings[] = 'SUM(o.is_user) = 0 AND SUM(o.is_group) = 0';
      }
    }
    elseif ($params[Constants::MM_GET_TREE_FILTER_USERS]) {
      $perms[Constants::MM_PERMS_IS_USER] = 1;
      $havings[] = 'SUM(o.is_user) > 0';
    }

    $having = join(' OR ', $havings);
    $condit = array();
    if (!$params[Constants::MM_GET_TREE_FILTER_DOTS]) {
      $condit[] = $filter_dots;
      $outside_group_by[] = 'name';   // Needed for HAVING clause
    }
    if (!$params[Constants::MM_GET_TREE_FILTER_BINS]) {
      $condit[] = 'SUM(o.is_recycled) = 0';
    }

    if (!$condit) {
      $having = ' HAVING ' . $having;
    }
    elseif ($having) {
      $having = ' HAVING (' . $having . ') AND ' . join(' AND ', $condit);
    }
    else {
      $having = ' HAVING ' . join(' AND ', $condit);
    }

    if (!$outside_group_by) {
      $outside_group_by[] = 'o.mmtid';
    }
  }

  if ($perms) {
    if ($uid) {
      if (!$is_admin) {
        $inside_joins .=
          'LEFT JOIN {mm_tree_access} a ON a.mmtid = i.mmtid ' .
          'LEFT JOIN {mm_group} g ON g.gid = a.gid ' .
          "LEFT JOIN {mm_virtual_group} v ON v.vgid = g.vgid AND v.uid = $uid ";
      }
    }
    elseif ($params[Constants::MM_GET_TREE_BIAS_ANON]) {
      $anon_group = 'SUM(o.is_group) = 0 AND ';
    }

    if ($is_admin) {
      foreach (array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ) as $m) {
        if (isset($perms[$m])) {
          $outside_selects[$m] = "COUNT(*) > 0 AS $m";
        }
      }
      $outside_selects[] = '1 AS ' . Constants::MM_PERMS_ADMIN;
    }
    else {
      $outside_admin = array();
      if (!empty($user_access[$uid]['administer all groups'])) {
        $outside_admin[] = 'SUM(o.is_group) > 0';
      }
      if (!empty($user_access[$uid]['administer all users'])) {
        $outside_admin[] = 'SUM(o.is_user) > 0';
      }
      $outside_admin = count($outside_admin) ? join(' OR ', $outside_admin) . ' OR ' : $anon_group;

      foreach (array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY) as $m) {
        if (isset($perms[$m])) {
          if ($uid) {
            $mode_cmp = $m == Constants::MM_PERMS_WRITE ? "= '" . Constants::MM_PERMS_WRITE . "'" : "IN ('" . Constants::MM_PERMS_WRITE . "', '$m')";
            $not_anon = "a.mode $mode_cmp AND (v.uid = $uid OR g.vgid = 0 AND g.uid = $uid) OR i.uid = $uid OR ";
          }
          else $not_anon = '';  // ignore anon user when owner or in a group

          $outside_selects[$m] = "{$outside_admin}(SUM(o.container = o.mmtid AND o.can_$m) > 0" . ($user_access[$uid]['view all menus'] ? '' : ' AND COUNT(*) = SUM(o.can_r)') . ") AS $m";
          $mode_cmp = "LOCATE('" . Constants::MM_PERMS_WRITE . "', i.default_mode) > 0";
          if ($m != Constants::MM_PERMS_WRITE) $mode_cmp .= " OR LOCATE('$m', i.default_mode) > 0";
          $inside_selects[$m] = "SUM($not_anon$mode_cmp) > 0 AS can_$m";
          $node_selects[] = "SUM($m) > 0 AS $m";
        }
      }

      if (isset($perms[Constants::MM_PERMS_READ])) {
        if ($user_access[$uid]['view all menus']) {
          $outside_selects[] = 'SUM(o.is_recycled) = 0 AND (SUM(o.is_group) = 0 AND COUNT(*) > 0 OR COUNT(*) = SUM(o.can_r)) AS r';
        }
        elseif ($is_node) {
          $outside_selects[] = "{$outside_admin}COUNT(o.container = o.mmtid) = SUM(o.can_r) AS r";
        }
        else {
          $outside_selects[] = "{$outside_admin}IF(SUM(o.is_group), SUM(o.container = o.mmtid AND o.can_m) > 0, COUNT(o.container = o.mmtid) = SUM(o.can_r)) AS r";
        }
        $node_selects[] = 'SUM(r) > 0 AS r';
      }

      $not_anon = $uid ? "(v.uid = $uid OR g.vgid = 0 AND g.uid = $uid) OR i.uid = $uid OR " : '';
      if ($params[Constants::MM_GET_TREE_FAKE_READ_BINS]) {
        $not_anon .= "i.name = '" . Constants::MM_ENTRY_NAME_RECYCLE . "' OR ";
      }
      $inside_selects[] = "SUM({$not_anon}i.default_mode <> '') > 0 AS can_r";

      if (!$is_node) {
        $not_anon = $uid ? "a.mode IN ('" . Constants::MM_PERMS_WRITE . "', '" . Constants::MM_PERMS_SUB . "', '" . Constants::MM_PERMS_READ . "') AND (v.uid = $uid OR g.vgid = 0 AND g.uid = $uid) OR i.uid = $uid OR " : '';
        $inside_selects[] = "SUM({$not_anon}LOCATE('" . Constants::MM_PERMS_WRITE . "', i.default_mode) > 0 OR LOCATE('" . Constants::MM_PERMS_SUB . "', i.default_mode) > 0 OR LOCATE('" . Constants::MM_PERMS_READ . "', i.default_mode) > 0) > 0 AS can_m";
      }
    }

    if (isset($perms[Constants::MM_PERMS_IS_USER]) || $user_access[$uid]['administer all users']) {
      $inside_selects[] = 'i.mmtid = ' . mm_content_users_mmtid() . ' AS is_user';
      if (isset($perms[Constants::MM_PERMS_IS_USER]) && !$is_node) {
        $outside_selects[] = 'SUM(o.is_user) > 0 AS ' . Constants::MM_PERMS_IS_USER;
      }
    }

    if (isset($perms[Constants::MM_PERMS_IS_GROUP]) || !empty($anon_group) || $user_access[$uid]['administer all groups'] || $user_access[$uid]['view all menus']) {
      $inside_selects[] = 'i.mmtid = ' . mm_content_groups_mmtid() . ' AS is_group';
      if (isset($perms[Constants::MM_PERMS_IS_GROUP]) && !$is_node) {
        $outside_selects[] = empty($anon_group) ? 'SUM(o.is_group) > 0 AS ' . Constants::MM_PERMS_IS_GROUP : '0 AS ' . Constants::MM_PERMS_IS_GROUP;
      }
    }

    if (isset($perms[Constants::MM_PERMS_IS_RECYCLE_BIN])) {
      $inside_selects[] = "i.mmtid = i.container AND i.name = '" . Constants::MM_ENTRY_NAME_RECYCLE . "' AS is_recycle_bin";
      $outside_selects[] = 'SUM(o.container = o.mmtid AND o.is_recycle_bin) > 0 AS ' . Constants::MM_PERMS_IS_RECYCLE_BIN;
      $node_selects[] = 'SUM(' . Constants::MM_PERMS_IS_RECYCLE_BIN . ') AS ' . Constants::MM_PERMS_IS_RECYCLE_BIN;
    }

    if (isset($perms[Constants::MM_PERMS_IS_RECYCLED]) || $params[Constants::MM_GET_TREE_RETURN_BINS] || $user_access[$uid]['view all menus'] || !$params[Constants::MM_GET_TREE_FILTER_BINS]) {
      $inside_selects[] = "SUM(i.name = '" . Constants::MM_ENTRY_NAME_RECYCLE . "') AS is_recycled";
      if (isset($perms[Constants::MM_PERMS_IS_RECYCLED]) || !$params[Constants::MM_GET_TREE_FILTER_BINS]) {
        $outside_selects[] = 'SUM(o.is_recycled) > 0 AS ' . Constants::MM_PERMS_IS_RECYCLED;
        $node_selects[] = 'SUM(' . Constants::MM_PERMS_IS_RECYCLED . ') AS ' . Constants::MM_PERMS_IS_RECYCLED;
      }

      if ($params[Constants::MM_GET_TREE_RETURN_BINS]) {
        $inside_selects[] = "IF(i.name = '" . Constants::MM_ENTRY_NAME_RECYCLE . "', i.mmtid, NULL) AS recycle_bins";
        $outside_selects[] = 'GROUP_CONCAT(o.recycle_bins ORDER BY o.recycle_bins) AS recycle_bins';
        $node_selects[] = 'GROUP_CONCAT(recycle_bins) AS recycle_bins';
      }
    }
  }   // if ($perms)

  if ($is_node) {
    $i =
      'SELECT t.mmtid, t.default_mode, t.uid, t.name, n2.mmtid AS container ' .
        'FROM {mm_tree_parents} p ' .
          'LEFT JOIN {mm_tree} t ON t.mmtid = p.parent ' .
          'INNER JOIN {mm_node2tree} n2 ON n2.mmtid = p.mmtid ' .
        'WHERE n2.nid = ' . $params[Constants::MM_GET_TREE_NODE]->id() . ' ' .
      'UNION SELECT t.mmtid, t.default_mode, t.uid, t.name, n2.mmtid AS container ' .
        'FROM {mm_tree} t ' .
          'LEFT JOIN {mm_node2tree} n2 ON n2.mmtid = t.mmtid ' .
        'WHERE n2.nid = ' . $params[Constants::MM_GET_TREE_NODE]->id();
  }
  else {
    if ($params[Constants::MM_GET_TREE_RETURN_TREE] || $params[Constants::MM_GET_TREE_DEPTH] || $params[Constants::MM_GET_TREE_RETURN_MTIME]) {
      if ($params[Constants::MM_GET_TREE_RETURN_TREE]) {
        $outside_selects[] = mm_all_db_columns('mm_tree', [], 't.');
      }

      if ($params[Constants::MM_GET_TREE_DEPTH] && $params[Constants::MM_GET_TREE_SORT]) {
        $outside_order_by = ' ORDER BY sort_idx';
      }

      if ($perms || $params[Constants::MM_GET_TREE_DEPTH] || $params[Constants::MM_GET_TREE_RETURN_MTIME]) {
        $outside_joins .= ' INNER JOIN {mm_tree} t ON t.mmtid = o.container';
      }
    }

    if ($params[Constants::MM_GET_TREE_RETURN_FLAGS]) {
      $outside_selects[] = "(SELECT GROUP_CONCAT(CONCAT_WS('|1', flag, data) SEPARATOR '|2') FROM {mm_tree_flags} WHERE mmtid = o.container) AS flags";
    }

    if ($params[Constants::MM_GET_TREE_RETURN_MTIME]) {
      $outside_selects[] = 'MAX(tr.muid) AS muid, MAX(tr.mtime) AS mtime';
      $outside_joins .= ' LEFT JOIN {mm_tree_revision} tr ON tr.vid = t.vid';
    }

    if ($params[Constants::MM_GET_TREE_RETURN_BLOCK]) {
      $outside_selects[] = 'MAX(b.bid) AS bid, MAX(b.max_depth) AS max_depth, MAX(b.max_parents) AS max_parents';
      $outside_joins .= ' LEFT JOIN {mm_tree_block} b ON b.mmtid = o.container';
    }

    if ($params[Constants::MM_GET_TREE_RETURN_KID_COUNT]) {
      $condit = array();
      if (!$params[Constants::MM_GET_TREE_FILTER_BINS]) {
        $condit[] = "name <> '" . Constants::MM_ENTRY_NAME_RECYCLE . "'";
      }
      if (!$params[Constants::MM_GET_TREE_FILTER_DOTS]) {
        $condit[] = $filter_dots;
      }

      if ($condit) {
        $outside_selects[] = '(SELECT COUNT(*) FROM {mm_tree} WHERE parent = o.container AND ' . join(' AND ', $condit) . ') AS kids';
      }
      else {
        $outside_selects[] = '(SELECT COUNT(*) FROM {mm_tree} WHERE parent = o.container) AS kids';
      }
    }

    switch ($params[Constants::MM_GET_TREE_DEPTH]) {
      case -1:
        $i =
          // the item
          'SELECT mmtid AS container, mmtid, default_mode, uid, name ' .
            'FROM {mm_tree} ' .
            'WHERE mmtid = ' . $params[Constants::MM_GET_TREE_MMTID] . ' ' .
          // the item's children
          'UNION SELECT p.mmtid, t.mmtid, t.default_mode, t.uid, t.name ' .
            'FROM {mm_tree_parents} p ' .
              'INNER JOIN {mm_tree} t ON t.mmtid = p.mmtid ' .
            'WHERE p.parent = ' . $params[Constants::MM_GET_TREE_MMTID];
        if ($perms) {
          $i .= ' ' .
          // the item's parents
          'UNION SELECT p.mmtid, t.mmtid, t.default_mode, t.uid, t.name ' .
            'FROM {mm_tree_parents} p ' .
              'INNER JOIN {mm_tree} t ON t.mmtid = p.parent ' .
            'WHERE p.mmtid = ' . $params[Constants::MM_GET_TREE_MMTID] . ' ' .
          // its children's parents
          'UNION SELECT p0.mmtid, t.mmtid, t.default_mode, t.uid, t.name ' .
            'FROM {mm_tree_parents} p0 ' .
              'INNER JOIN {mm_tree_parents} p1 ON p1.mmtid = p0.mmtid ' .
              'INNER JOIN {mm_tree} t ON t.mmtid = p1.parent ' .
            'WHERE p0.parent = ' . $params[Constants::MM_GET_TREE_MMTID];
        }
        break;

      case 0:
        if (!$perms) {
          return
            'SELECT ' . join(', ', $outside_selects) . ' FROM {mm_tree} t' .
            $outside_joins .
            (empty($params[Constants::MM_GET_TREE_WHERE]) ? '' : ' WHERE ' . $params[Constants::MM_GET_TREE_WHERE]);
        }

        $i =
          // the item
          'SELECT mmtid AS container, mmtid, default_mode, uid, name ' .
            'FROM {mm_tree} ' .
            'WHERE mmtid = ' . $params[Constants::MM_GET_TREE_MMTID] . ' ' .
          // its parents
          'UNION SELECT p.mmtid, t.mmtid, t.default_mode, t.uid, t.name ' .
            'FROM {mm_tree_parents} p ' .
              'INNER JOIN {mm_tree} t ON t.mmtid = p.parent ' .
            'WHERE p.mmtid = ' . $params[Constants::MM_GET_TREE_MMTID];
        break;

      case 1:
        $i =
          // the item
          'SELECT t.mmtid AS container, t.mmtid, t.default_mode, t.uid, t.name ' .
            'FROM {mm_tree} t ' .
            'WHERE t.mmtid = ' . $params[Constants::MM_GET_TREE_MMTID] . ' ' .
          // the item's immediate children
          'UNION SELECT t.mmtid AS container, t.mmtid, t.default_mode, t.uid, t.name ' .
            'FROM {mm_tree} t ' .
            'WHERE t.parent = ' . $params[Constants::MM_GET_TREE_MMTID] . $params[Constants::MM_GET_TREE_INNER_FILTER];
        if ($perms) {
          $i .= ' ' .
          // the item's parents
          'UNION SELECT p.mmtid, t.mmtid, t.default_mode, t.uid, t.name ' .
            'FROM {mm_tree_parents} p ' .
              'INNER JOIN {mm_tree} t ON t.mmtid = p.parent ' .
            'WHERE p.mmtid = ' . $params[Constants::MM_GET_TREE_MMTID] . ' ' .
          // its immediate children's parents
          'UNION SELECT t.mmtid, t2.mmtid, t2.default_mode, t2.uid, t2.name ' .
            'FROM {mm_tree} t ' .
            'INNER JOIN {mm_tree_parents} p ON t.mmtid = p.mmtid ' .
            'INNER JOIN {mm_tree} t2 ON t2.mmtid = p.parent ' .
            'WHERE t.parent = ' . $params[Constants::MM_GET_TREE_MMTID] . $params[Constants::MM_GET_TREE_INNER_FILTER];
        }
        break;

      default:
        $select = Database::getConnection()->select('mm_tree_parents', 'p');
        $select->addExpression('MAX(p.depth)', 'depth');
        $select->condition('p.mmtid', $params[Constants::MM_GET_TREE_MMTID]);
        $depth = $select->execute()->fetchField() + $params[Constants::MM_GET_TREE_DEPTH] + 1;

        $i =
          'SELECT x.container, t.mmtid, t.default_mode, t.uid, t.name ' .
            'FROM (' .
              'SELECT p0.mmtid AS container, p1.parent AS mmtid ' .
                'FROM (' .
                  'SELECT p0.mmtid ' .
                    'FROM {mm_tree_parents} p0 ' .
                    'INNER JOIN {mm_tree_parents} p1 ON p1.mmtid = p0.mmtid ' .
                    'WHERE p0.parent = ' . $params[Constants::MM_GET_TREE_MMTID] . ' ' .
                    'GROUP BY p0.mmtid HAVING MAX(p1.depth) < ' . $depth .
                ') p0 ' .
                'INNER JOIN {mm_tree_parents} p1 ON p1.mmtid = p0.mmtid ' .
              'UNION (' .
                'SELECT p0.mmtid, p0.mmtid ' .
                  'FROM {mm_tree_parents} p0 ' .
                  'INNER JOIN {mm_tree_parents} p1 ON p1.mmtid = p0.mmtid ' .
                  'WHERE p0.parent = ' . $params[Constants::MM_GET_TREE_MMTID] . ' ' .
                  'GROUP BY p0.mmtid HAVING MAX(p1.depth) < ' . $depth .
              ') ' .
              'UNION SELECT ' . $params[Constants::MM_GET_TREE_MMTID] . ', parent ' .
                'FROM {mm_tree_parents} ' .
                'WHERE mmtid = ' . $params[Constants::MM_GET_TREE_MMTID] . ' ' .
              'UNION SELECT ' . $params[Constants::MM_GET_TREE_MMTID] . ', ' . $params[Constants::MM_GET_TREE_MMTID] .
            ') AS x ' .
            'INNER JOIN {mm_tree} t ON t.mmtid = x.mmtid';
        break;
    }
  }

  // Note: Always use ORDER BY NULL instead of GROUP BY without ORDER BY, as
  // this is slightly faster.
  $query =
    'SELECT ' . join(', ', $outside_selects) . ' FROM (' .
      'SELECT ' . join(', ', $inside_selects) . ' ' .
      'FROM (' .
        $i .
      ') AS i ' .
      $inside_joins .
      'GROUP BY ' . join(', ', $inside_group_by) . ' ' .
      'ORDER BY NULL' .
    ') AS o' .
    $outside_joins .
    (empty($outside_where) ? '' : ' WHERE ' . $outside_where) .
    ($outside_group_by ? ' GROUP BY ' . join(', ', $outside_group_by) . $having : '') .
    ($outside_order_by ? $outside_order_by : ($outside_group_by ? ' ORDER BY NULL' : ''));

  if ($is_node && $node_selects && !$is_admin) {
    return 'SELECT ' . join(', ', $node_selects) . " FROM ($query) q";
  }

  return $query;
}

/**
 * Clear one element of the caches used by various functions, or completely
 * clear all caches.
 *
 * @param int|null $mmtid
 *   If set, delete just the cached mm_content_user_can data for this tree ID or
 *   array of tree IDs. Otherwise, clear the whole cache.
 */
function mm_content_clear_caches($mmtid = NULL) {
  $_mmtbt_cache = &drupal_static('_mmtbt_cache', array());
  $_mmgp_cache = &drupal_static('_mmgp_cache', array());
  $_mmuc_cache = &drupal_static('_mmuc_cache', array());
  $_mmucr_cache = &drupal_static('_mmucr_cache', array());
  $_mmcucn_cache = &drupal_static('_mmcucn_cache');
  $_mm_custom_url_rewrite_outbound_cache = &drupal_static('_mm_custom_url_rewrite_outbound_cache');

  if (isset($mmtid)) {
    if (is_array($mmtid)) {
      $flipped = array_flip($mmtid);
      $_mmuc_cache = array_diff_key($_mmuc_cache, $flipped);
      $_mmucr_cache = array_diff_key($_mmucr_cache, $flipped);
      $_mmtbt_cache = array_diff_key($_mmtbt_cache, $flipped);
      $_mmgp_cache = array_diff_key($_mmgp_cache, $flipped);
    }
    else {
      unset($_mmuc_cache[$mmtid]);
      unset($_mmucr_cache[$mmtid]);
      unset($_mmtbt_cache[$mmtid]);
      unset($_mmgp_cache[$mmtid]);
    }
    _mm_content_clear_access_cache($mmtid);
  }
  else {
    $_mmuc_cache = $_mmucr_cache = $_mmtbt_cache = $_mmgp_cache = array();
  }
  // Always clear arrays not indexed by mmtid
  $_mmcucn_cache = $_mm_custom_url_rewrite_outbound_cache = array();
}

/**
 * Queue mm_tree entries that have changed, or update the sort index for all
 * queued entries.
 *
 * If neither $parent nor $child is set, perform all queued updates. This
 * usually happens during monster_menus_exit().
 *
 * @param int|null $parent
 *   If set, queue this portion of the tree for future update; this should be
 *   the parent term ID of the entry that has changed. If the parent is not
 *   known, use NULL and specify a value in $child, instead.
 * @param int|null $child
 *   Instead of using $parent, a $child can be specified. In this case, the
 *   parent is queried in an additional step, so this method should be avoided
 *   when possible.
 * @param bool $all
 *   If TRUE, update all entries, not just the dirty ones; this is generally
 *   only done the very first time the sort index is generated.
 * @param int $semaphore_time
 *   If non-zero, use a semaphore to ensure exclusive access to updates
 *   performed when neither $parent nor $child is set. If another process has
 *   set the semaphore, the current process will wait for up to this number of
 *   seconds for it to clear. This number must be less than 2 hours. Do not use
 *   this parameter during normal page updates (such as hook_exit()), as they
 *   should never block.
 */
function mm_content_update_sort_queue($parent = NULL, $child = NULL, $all = FALSE, $semaphore_time = 0) {
  static $drupal_static_fast;
  $_mm_content_defer_sort_index_update = &drupal_static('_mm_content_defer_sort_index_update');

  if (!isset($drupal_static_fast)) {
    // This is cumbersome, but assigning to an array is the only way that works.
    $drupal_static_fast['q'] = &drupal_static(__FUNCTION__, array());
  }
  $queue = &$drupal_static_fast['q'];

  if (empty($parent) && !empty($child)) {
    $parent = mm_content_get_parent($child);
    if (empty($parent)) {
      return;
    }
  }

  if (!empty($parent)) {
    if (isset($queue[$parent])) {
      $queue[$parent] |= $all;
    }
    else {
      $queue[$parent] = $all;
    }
  }
  elseif ($queue) {
    if (!empty($_mm_content_defer_sort_index_update)) {
      return;
    }

    $uniq = array_unique($queue);
    if (count($uniq) == 1 && !$uniq[mm_ui_mmlist_key0($uniq)]) {
      mm_content_update_sort(array_keys($queue), FALSE, $semaphore_time);
    }
    else {
      foreach ($queue as $parent => $all) {
        mm_content_update_sort($parent, $all, $semaphore_time);
      }
    }
    $queue = array();
  }
}

/**
 * Update the mm_tree column containing the sort index.
 *
 * @param int $mmtid
 *   Point from which to update entries downward in the tree. Can be an array of
 *   values when $all is FALSE.
 * @param bool $all
 *   If TRUE, update all entries, not just the dirty ones; this is generally
 *   only done the very first time the sort index is generated.
 * @param int $semaphore_time
 *   If non-zero, use a semaphore to ensure exclusive access to updates. If
 *   another process has set the semaphore, the current process will wait for
 *   up to this number of seconds for it to clear. If it does not clear in time,
 *   a textual error message is returned. This number must be less than 2 hours.
 *   Do not use this parameter during normal page updates (such as hook_exit()),
 *   as they should never block.
 * @return string|void
 *   If $semaphore_time is non-zero and the semaphore could not be grabbed, an
 *   error message is returned.
 */
function mm_content_update_sort($mmtid = 1, $all = TRUE, $semaphore_time = 0) {
  $database = Database::getConnection();
  if ($semaphore_time) {
    $sem_err = mm_content_update_sort_test_semaphore($semaphore_time);
    if (is_string($sem_err)) {
      return $sem_err;
    }
  }

  if ($all) {
    $update_all = function ($sort_idx, $mmtid) use (&$update_all, $database) {
      $order = 0;
      $select = $database->select('mm_tree', 't');
      $select->addField('t', 'mmtid');
      $select->addExpression('IF(hidden, 1, IF(name = :name, 2, 0))', 'sort_column', array(':name' => Constants::MM_ENTRY_NAME_RECYCLE));
      $select->condition('t.parent', $mmtid);
      $select->orderBy('sort_column')
        ->orderBy('weight')
        ->orderBy('name');
      $result = $select->execute();
      foreach ($result as $r) {
        $new_idx = $sort_idx . _mm_content_btoa($order++);
        _mm_content_test_sort_length($new_idx, $r->mmtid, TRUE);
        $database->update('mm_tree')
          ->fields(array('sort_idx' => $new_idx, 'sort_idx_dirty' => 0))
          ->condition('mmtid', $r->mmtid)
          ->execute();
        $update_all($new_idx, $r->mmtid);
      }
    };

    if ($mmtid <= 1) {
      $mmtid = 1;
      $sort_idx = '';
      $database->update('mm_tree')
        ->fields(array('sort_idx' => $sort_idx, 'sort_idx_dirty' => 0))
        ->condition('mmtid', $mmtid)
        ->execute();
    }
    else {
      $sort_idx = $database->select('mm_tree', 't')
        ->fields('t', array('sort_idx'))
        ->condition('t.mmtid', $mmtid)
        ->execute()->fetchField();
    }
    $update_all($sort_idx, $mmtid);
  }
  else {
    // Start a transaction.
    $txn = $database->startTransaction();
    for ($last = -1;;) {
      $in = is_array($mmtid) ? 'IN (:mmtid[])' : '= :mmtid';
      $parent_q = $database->queryRange(
        'SELECT t.parent, t.sort_idx, t.mmtid ' .
          'FROM {mm_tree} t ' .
          'INNER JOIN {mm_tree_parents} p ON p.mmtid = t.mmtid ' .
        "WHERE t.sort_idx_dirty = 1 AND p.parent $in " .
        'ORDER BY LENGTH(t.sort_idx) DESC',
        0, 1, array((is_array($mmtid) ? ':mmtid[]' : ':mmtid') => $mmtid)
      );
      if ($parent = $parent_q->fetchObject()) {
        // Prevent the outer for loop from running amok if there is a DB error
        if ($parent->mmtid == $last) {
          break;
        }
        $last = $parent->mmtid;
        $sort_idx = substr_replace($parent->sort_idx, '', -Constants::MM_CONTENT_BTOA_CHARS);
        $sort_len = strlen($sort_idx);
        $order = 0;
        $quick = array();

        $select = $database->select('mm_tree', 't');
        $select->fields('t', array('mmtid', 'sort_idx', 'sort_idx_dirty'));
        $select->addExpression(
          'IF(t.hidden, :hidden_weight, IF(name = :recycle_name, :recycle_weight, :normal_weight))',
          'sort_group',
          array(
            ':hidden_weight' => 1,
            ':recycle_name' => Constants::MM_ENTRY_NAME_RECYCLE,
            ':recycle_weight' => 2,
            ':normal_weight' => 0,
          )
        );
        $select->condition('t.parent', $parent->parent);
        $select->orderBy('sort_group')
          ->orderBy('t.weight')
          ->orderBy('t.name');

        $result = $select->execute();
        foreach ($result as $r) {
          $new_idx = _mm_content_btoa($order++);
          if ($new_idx != substr($r->sort_idx, -Constants::MM_CONTENT_BTOA_CHARS)) {
            $database->query(
              'UPDATE {mm_tree} t ' .
              'INNER JOIN {mm_tree_parents} p ON p.mmtid = t.mmtid ' .
              'SET t.sort_idx = CONCAT(' .
                  'SUBSTRING(t.sort_idx, :sort_start, :sort_len), :new_idx, SUBSTRING(t.sort_idx, :my_idx)' .
                '), t.sort_idx_dirty = :dirty ' .
              'WHERE p.parent = :mmtid OR t.mmtid = :mmtid',
              array(
                ':sort_start' => 1,
                ':sort_len' => $sort_len,
                ':new_idx' => $new_idx,
                ':my_idx' => $sort_len + Constants::MM_CONTENT_BTOA_CHARS + 1,
                ':dirty' => 0,
                ':mmtid' => $r->mmtid,
              )
            );
          }
          elseif ($r->sort_idx_dirty) {
            $quick[] = $r->mmtid;
          }
        }

        if ($quick) {
          $database->update('mm_tree')
            ->fields(array('sort_idx_dirty' => 0))
            ->condition('mmtid', $quick, 'IN')
            ->execute();
        }
      }
      else {
        break;
      }
    }
  }
  unset($txn);
  if ($semaphore_time) {
    mm_content_update_sort_test_semaphore(-1);
  }
}

/**
 * Use a semaphore to ensure exclusive access to sort index updates.
 *
 * @param int $semaphore_time
 *   If another process has set the semaphore, the current process will wait for
 *   up to this number of seconds for it to clear. If it does not clear in time,
 *   a textual error message is returned. This number must be less than 2 hours.
 *
 *   Pass a negative number to clear a semaphore that is owned by the current
 *   process.
 *
 *   Do not use this function during normal page updates (such as hook_exit()),
 *   as they should never block.
 * @return TranslatableMarkup|true
 *   Either TRUE if the semaphore was obtained, or an error message.
 */
function mm_content_update_sort_test_semaphore($semaphore_time) {
  if ($semaphore_time < 0) {
    \Drupal::state()->delete('mm_update_sort_semaphore');
  }
  else if ($semaphore_time) {
    for (;;) {
      // Intentionally don't use REQUEST_TIME here, because we need to get the
      // true time.
      $now = time();
      if (!($running = \Drupal::state()->get('mm_update_sort_semaphore', 0))) {
        break;
      }

      if ($now - $running > 2 * 60 * 60 + 5) {
        \Drupal::logger('mm')->error('mm_content_update_sort() has been running for more than two hours, or terminated unexpectedly. Ignoring semaphore.');
        break;
      }

      if ($now - $running >= $semaphore_time) {
        return t('Another process already has the mm_content_update_sort() semaphore.');
      }

      // Wait .1 sec before polling again.
      usleep(100000);
    }
    \Drupal::state()->set('mm_update_sort_semaphore', $now);
  }
  return TRUE;
}

/**
 * Decode a 32-bit word represented by four ASCII characters, derived from the
 * number's base-64 equivalent. This is the inverse of _mm_content_btoa().
 *
 * @param string $str
 *   The string containing the encoded integer
 * @return int
 *   The resulting integer
 */
function _mm_content_atob($str) {
  $out = 0;
  while (!empty($str) || $str === '0') {
    $out = $out * Constants::MM_CONTENT_BTOA_BASE + ord($str[0]) - Constants::MM_CONTENT_BTOA_START;
    $str = substr($str, 1);
  }
  return $out;
}

/**
 * Encode a 32-bit word using four ASCII characters, derived from the number's
 * base-64 equivalent. This produces a sequence that is suitable for sorting in
 * SQL, without sacrificing too much space. A larger base cannot be used without
 * the possibility of causing case-insensitive sorting errors.
 *
 * @param int $uword
 *   The unsigned word to encode
 * @return string
 *   The word, encoded in a string
 */
function _mm_content_btoa($uword) {
  static $pows, $max;

  if (!isset($pows)) {
    $max = (int) pow(Constants::MM_CONTENT_BTOA_BASE, Constants::MM_CONTENT_BTOA_CHARS);
    $pows = array();
    for ($i = 1; $i < Constants::MM_CONTENT_BTOA_CHARS; $i++) {
      array_unshift($pows, (int) pow(Constants::MM_CONTENT_BTOA_BASE, $i));
    }
  }

  if ($uword < 0 || $uword >= $max) {
    die('Range error');
  }

  $out = '';
  foreach ($pows as $pow) {
    $out .= chr(Constants::MM_CONTENT_BTOA_START + $uword / $pow);
    // The % operator doesn't work correctly, here
    $uword = $uword - intval($uword / $pow) * $pow;
  }
  $out .= chr(Constants::MM_CONTENT_BTOA_START + $uword);
  return $out;
}

/**
 * Automatically empty all recycle bins of content that has been there for more
 * than a set time. Run as uid=1 during monster_menus_cron().
 *
 * @param int $limit
 *   The maximum number of nodes/pages to delete
 */
function mm_content_empty_all_bins($limit = 0) {
  if (($empty = mm_get_setting('recycle_auto_empty')) > 0) {
    $db = Database::getConnection();
    $now = mm_request_time();
    $query = $db->select('mm_recycle', 'rc')
      ->fields('rc')
      ->fields('n', ['type']);
    $query->leftJoin('node', 'n', "rc.type = 'node' AND n.nid = r.id");
    $query->condition('rc.recycle_date', $now - $empty, '<');
    if ($limit > 0) {
      $query->range(0, $limit);
    }
    $result = $query->execute();

    $bins = $nodes = array();
    foreach ($result as $r) {
      if ($r->type == 'node') {
        if (empty($nodes[$r->id])) {
          $only_in_emptyable_bin = $db->query('SELECT COUNT(*) = 0 FROM {mm_node2tree} n2 LEFT JOIN {mm_recycle} r ON n2.nid = r.id AND `type` = :type AND (r.from_mmtid = n2.mmtid OR r.bin_mmtid = n2.mmtid) WHERE n2.nid = :nid AND (r.id IS NULL OR r.recycle_date >= :date)',
            array(
              ':type' => 'node',
              ':nid' => $r->id,
              ':date' => $now - $empty,
            ))->fetchField();
          if ($only_in_emptyable_bin) {
            \Drupal::logger('mm')->notice('Automatically emptying nid=@nid from recycle bin', array('@nid' => $r->id));
            $r->id->delete();
            // It's remotely possible for a node to be left over in mm_recycle,
            // even though it has already been deleted. In this case MM's deletion
            // code isn't called during node_delete(), so call it (again) now.
            $node = Node::create(['nid' => $r->id, 'type' => $r->type]);
            monster_menus_node_delete($node);
            $nodes[$r->id] = TRUE;
          }
          else {
            // Node cannot yet be deleted yet, so just remove it from this bin.
            $db->delete('mm_recycle')
              ->condition('type', 'node')
              ->condition('id', $r->id)
              ->condition('bin_mmtid', $r->bin_mmtid)
              ->execute();
            $db->delete('mm_node2tree')
              ->condition('nid', $r->id)
              ->condition('mmtid', $r->bin_mmtid)
              ->execute();
          }
        }
      }
      elseif ($r->type == 'cat') {
        \Drupal::logger('mm')->notice('Automatically deleting mmtid=@mmtid from recycle bin', array('@mmtid' => $r->id));
        mm_content_delete($r->id, TRUE);
      }
      $bins[$r->bin_mmtid] = 1;
    }

    foreach (array_keys($bins) as $bin) {
      mm_content_delete_bin($bin);
    }
  }
}

/**
 * Figure out if a given user can access a particular node. When a node belongs
 * to more than one entry, logically OR the permissions for all entries.
 *
 * @param NodeInterface|int $node
 *   The node object or node number being queried
 * @param string $mode
 *   If set, return whether or not the user can perform that action
 *   (MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB, MM_PERMS_APPLY). Otherwise,
 *   return an array containing each of these elements with either TRUE or FALSE
 *   values.
 * @param AccountInterface $account
 *   User object of user to test against. Defaults to the current user.
 * @return bool|array
 *   See above
 */
function mm_content_user_can_node($node, $mode = '', AccountInterface $account = NULL) {
  static $recursive = FALSE;
  $_mmcucn_cache = &drupal_static('_mmcucn_cache');
  if (empty($account)) {
    $account = \Drupal::currentUser();
  }

  $perms = array(
    Constants::MM_PERMS_WRITE => FALSE,
    Constants::MM_PERMS_SUB   => FALSE,
    Constants::MM_PERMS_APPLY => FALSE,
    Constants::MM_PERMS_READ  => FALSE,
  );

  // There's a chance that the calls to mm_content_node_access() below can lead
  // to a recursive call to this function.
  if ($recursive) {
    if (empty($mode)) {
      return $perms;
    }
    return FALSE;
  }
  $recursive = TRUE;

  if (is_object($node)) {
    if ($node->isNew()) {
      $recursive = FALSE;
      if (empty($mode)) {
        return $perms;
      }
      return FALSE;
    }
    $nid = $node->id();
  }
  else {
    $nid = $node;
    $node = Node::load($nid);
  }

  $uid = $account->id();
  if (!isset($_mmcucn_cache[$nid][$uid])) {
    $cid = ":$nid:$uid";
    $cached = _mm_content_access_cache($cid);
    if (is_array($cached)) {
      $_mmcucn_cache[$nid][$uid] = $cached;
    }
    else {
      $params = array(
        Constants::MM_GET_TREE_NODE           => $node,
        Constants::MM_GET_TREE_RETURN_BINS    => TRUE,
        Constants::MM_GET_TREE_RETURN_PERMS   => array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ, Constants::MM_PERMS_IS_RECYCLE_BIN),
        Constants::MM_GET_TREE_USER           => $account,
      );
      $row = Database::getConnection()->query(mm_content_get_query($params))->fetchObject();
      if ($row) {
        foreach ((array)$row as $key => $val) {
          if (strlen($key) == 1) {
            $perms[$key] = $val != 0;
          }
        }

        // mm_content_get_query() only considers the permissions of the page, so
        // now consider the node itself.
        if ($perms[Constants::MM_PERMS_READ]) {
          $has_page_write = $perms[Constants::MM_PERMS_WRITE];
          $perms[Constants::MM_PERMS_WRITE] = mm_content_node_access($node, 'update', $account);
        }

        if (isset($row->recycle_bins) && $row->{Constants::MM_PERMS_IS_RECYCLE_BIN}) {
          // Node is in a bin and possibly outside at the same time: if user
          // can access it in any bin, allow it here.
          if (!$perms[Constants::MM_PERMS_READ]) {
            foreach (array_unique(explode(',', $row->recycle_bins)) as $bin) {
              if ($perms[Constants::MM_PERMS_READ] = mm_content_user_can_recycle($bin, Constants::MM_PERMS_READ, $account)) {
                $perms[Constants::MM_PERMS_APPLY] = TRUE;
                break;
              }
            }
          }
        }
      }
      else {
        // not assigned to any pages
        $perms[Constants::MM_PERMS_READ] = $perms[Constants::MM_PERMS_APPLY] = TRUE;
      }

      // If the user does not have write permission, consider the appearance schedule
      if (empty($has_page_write) && !$perms[Constants::MM_PERMS_WRITE] && !mm_content_node_access($node, 'update', $account)) {
        $now = mm_request_time();
        $scheduled = $node->isPublished() && (!isset($node->publish_on) || $node->publish_on == 0 || $node->publish_on <= $now) && (!isset($node->unpublish_on) || $node->unpublish_on == 0 || $now < $node->unpublish_on);
        $perms[Constants::MM_PERMS_READ] = $perms[Constants::MM_PERMS_READ] && $scheduled;  // must use logical && to preserve boolean type
      }
      $_mmcucn_cache[$nid][$uid] = $perms;
      // Don't bother trying to figure out cache intervals when
      // publish/unpublish is used.
      if (empty($node->publish_on) && empty($node->unpublish_on)) {
        _mm_content_access_cache($cid, $perms, $uid, $nid, 0);
      }
    }
  }

  $recursive = FALSE;
  if (empty($mode)) {
    return $_mmcucn_cache[$nid][$uid];
  }
  return $_mmcucn_cache[$nid][$uid][$mode];
}

/**
 * Delete an entry and all of its children
 *
 * @param int $mmtid
 *   ID of the entry to delete
 * @param bool $delete_nodes
 *   If TRUE, also delete any nodes using these IDs (TRUE)
 * @param bool $allow_non_empty_bin
 *   If TRUE, allow a non-empty recycle bin at the top level to be deleted
 *   (FALSE)
 * @return string|void
 *   An error message, if an error occurs
 */
function mm_content_delete($mmtid, $delete_nodes = TRUE, $allow_non_empty_bin = FALSE) {
  $iter = new ContentDeleteIter($delete_nodes, $allow_non_empty_bin);
  mm_content_get_tree($mmtid, array(
    Constants::MM_GET_TREE_RETURN_PERMS => TRUE,
    Constants::MM_GET_TREE_ITERATOR => $iter,
    Constants::MM_GET_TREE_FILTER_HIDDEN => TRUE,
  ));
  if (!$iter->count) {
    return t('Page not found');
  }

  $iter->delete();
  if ($iter->err) {
    return $iter->err;
  }
}

/**
 * Return the long username ('last, first middle.' or 'first middle last')
 * associated with a uid
 *
 * @param int $uid
 *   ID of the user to query
 * @param string $order
 *   Either 'lfm', 'fml', 'lfmu', or 'fmlu' to choose the order. Defaults to
 *   'lfmu' [last, first, middle, (username)].
 * @param AccountInterface|StdClass $usr
 *   Optional object, from which the name, pref_lfm, pref_fml, firstname,
 *   middlename, and lastname fields are used to construct (and cache) the
 *   output
 * @param string $hover
 *   Optionally return the mouse 'hover' text associated with this user
 * @return string|false
 *   The user's long name or FALSE if not found
 */
function mm_content_uid2name($uid, $order = 'lfmu', $usr = NULL, &$hover = NULL) {
  static $drupal_static_fast;

  // FIXME: This should probably be reworked to use $usr->getDisplayName and hook_user_format_name_alter().
  if (!isset($drupal_static_fast)) {
    // This is cumbersome, but assigning to an array is the only way that works.
    $drupal_static_fast['cache'] = &drupal_static(__FUNCTION__, array());
  }
  $cache = &$drupal_static_fast['cache'];

  if (!isset($cache[$uid])) {
    if ($uid == 0) {
      $cache[$uid] = (object) array(
        'name' => mm_get_setting('usernames.anon'),
      );
    }
    elseif ($uid == 1) {
      $cache[$uid] = (object) array(
        'name' => mm_get_setting('usernames.admin'),
      );
    }
    else {
      if (!$usr) {
        $usr = User::load($uid);
      }

      if (!is_object($usr)) {
        $cache[$uid] = FALSE;
      }
      else {
        $disabled = class_implements($usr, 'isActive') ? !$usr->isActive() : isset($usr->status) && $usr->status == 0;

        mm_module_invoke_all_array('mm_uid2name_alter', array(&$usr, &$disabled));

        if ($disabled && !\Drupal::currentUser()->hasPermission('administer all users')) {
          $cache[$uid] = (object) array(
            'name' => mm_get_setting('usernames.disabled', t('Inactive user')),
          );
        }
        else {
          $middle = '';
          if (isset($usr->middlename) && mb_strlen($usr->middlename)) {
            $middle = ' ' . $usr->middlename[0] . '.';
          }
          $cache[$uid] = (object) array(
            'pref_fml' => isset($usr->pref_fml) ? $usr->pref_fml : '',
            'pref_lfm' => isset($usr->pref_lfm) ? $usr->pref_lfm : '',
            'last'     => isset($usr->lastname) ? $usr->lastname : '',
            'first'    => isset($usr->firstname) ? $usr->firstname : '',
            'name'     => isset($usr->name) ? (is_scalar($usr->name) ? $usr->name : $usr->name->getValue()[0]['value']) : '',
            'middle'   => $middle,
            'hover'    => isset($usr->hover) ? $usr->hover : '',
            'disabled' => $disabled ? ' ' . t('(inactive)') : '',
          );
        }
      }
    }
  }

  if (($u = $cache[$uid]) !== FALSE) {
    $hover = isset($u->hover) ? $u->hover : '';

    $uname_only = FALSE;
    $fml = mb_substr($order, 0, 3) == 'fml';
    if ($fml ? !empty($u->pref_fml) : !empty($u->pref_lfm)) {
      $out = $fml ? $u->pref_fml : $u->pref_lfm;
    }
    elseif (!empty($u->last) && !empty($u->first)) {
      $out = $fml ? "$u->first$u->middle $u->last" : "$u->last, $u->first$u->middle";
    }
    elseif (!empty($u->last)) {
      $out = $u->last;
    }
    elseif (!empty($u->first)) {
      $out = $u->first;
    }
    else {
      $out = $u->name;
      $uname_only = TRUE;
    }

    if (isset($order) && isset($order[3]) && $order[3] == 'u' && $u->name != '' && !$uname_only) {
      $out .= " ($u->name)";
    }
    $out .= isset($u->disabled) ? $u->disabled : '';

    return $out;
  }

  return FALSE;
}

/**
 * Return the uid associated with a username
 *
 * @param string $username
 *   ID of the user to query
 * @return int|false
 *   The user's ID or FALSE if not found
 */
function mm_content_name2uid($username) {
  $mmc_u2uid_cache = &drupal_static(__FUNCTION__, array());

  if (!isset($mmc_uid2u_cache[$username])) {
    $usr = user_load_by_name($username);
    $mmc_u2uid_cache[$username] = is_object($usr) ? $usr->id() : FALSE;
  }
  return $mmc_u2uid_cache[$username];
}

/**
 * Determine if a tree entry is a group
 *
 * @param int $mmtid
 *   The numeric ID of the potential group
 * @return bool
 *   TRUE if the group ID is that of an existing (possibly virtual) group, and
 *   not a different type of tree entry
 */
function mm_content_is_group($mmtid) {
  $mmc_isgrp_cache = &drupal_static(__FUNCTION__, array());

  if (!isset($mmc_isgrp_cache[$mmtid])) {
    $list = mm_content_get_parents_with_self($mmtid, FALSE, TRUE);
    $mmc_isgrp_cache[$mmtid] = isset($list[1]) && $list[1] == mm_content_groups_mmtid();
  }
  return $mmc_isgrp_cache[$mmtid];
}

/**
 * Determine if a tree entry is a virtual group
 *
 * @param int $mmtid
 *   The numeric ID of the group
 * @return bool
 *   TRUE if the group ID is that of an existing virtual group, and not a
 *   different type of tree entry
 */
function mm_content_is_vgroup($mmtid) {
  $list = mm_content_get_parents_with_self($mmtid);
  return count($list) >= 3 && ($tree = mm_content_get($list[2])) && $tree->name == Constants::MM_ENTRY_NAME_VIRTUAL_GROUP;
}

/**
 * Determine if a tree ID belongs to a normal entry, as opposed to a group
 *
 * @param int $mmtid
 *   The tree ID to test
 * @param bool $user_is_normal
 *   If TRUE, consider anything in .Users to be a normal entry
 * @return bool
 *   TRUE if the ID refers to a normal entry
 */
function mm_content_is_normal($mmtid, $user_is_normal = TRUE) {
  $cache = &drupal_static(__FUNCTION__, array());

  if (!isset($cache[$mmtid][$user_is_normal])) {
    $list = mm_content_get_parents_with_self($mmtid);
    if (count($list) == 1) {   // root
      $cache[$mmtid][$user_is_normal] = FALSE;
    }
    else {
      $cache[$mmtid][$user_is_normal] = $list[1] != mm_content_groups_mmtid() &&
          ($user_is_normal || $list[1] != mm_content_users_mmtid());
    }
  }
  return $cache[$mmtid][$user_is_normal];
}

/**
 * Determine if a tree ID refers to the main page or the archive page of an
 * archive
 *
 * @param int $mmtid
 *   The tree ID to test
 * @param bool $test_main
 *   If TRUE, see if the ID refers to the main page, otherwise the archive page
 * @return bool
 *   TRUE if the ID refers to an archive page of the requested type
 */
function mm_content_is_archive($mmtid, $test_main = FALSE) {
  $tree = mm_content_get($mmtid, Constants::MM_GET_ARCHIVE);
  return is_object($tree) && $mmtid == ($test_main ? $tree->main_mmtid : $tree->archive_mmtid);
}

/**
 * Determine if a tree entry is a child of another entry; useful in preventing
 * bad moves.
 *
 * @param int $child
 *   The tree ID of the child entry
 * @param int $of
 *   The tree ID of the entry to test for a relationship
 * @return bool
 *   TRUE if $child is a child of $of
 */
function mm_content_is_child($child, $of) {
  return in_array($of, mm_content_get_parents($child));
}

/**
 * Determine if a tree entry is in a recycle bin.
 *
 * @param int $mmtid
 *   The tree ID of the entry to test
 * @return bool
 *   TRUE if $mmtid is in a bin
 */
function mm_content_is_recycled($mmtid) {
  return mm_content_user_can($mmtid, Constants::MM_PERMS_IS_RECYCLED);
}

/**
 * Determine if a tree entry is a recycle bin.
 *
 * @param int $mmtid
 *   The tree ID of the entry to test
 * @return bool
 *   TRUE if $mmtid is a recycle bin
 */
function mm_content_is_recycle_bin($mmtid) {
  return mm_content_user_can($mmtid, Constants::MM_PERMS_IS_RECYCLE_BIN);
}

/**
 * Get a list of all blocks
 *
 * @param bool $allowed
 *   If set, only return the blocks the current user can apply to a page
 * @return Drupal\block\Entity\Block[]
 *   An array, indexed on block ID, containing Drupal\block\Entity\Block
 *   elements
 */
function mm_content_get_blocks($allowed = FALSE) {
  static $cache;

  if (!is_array($cache)) {
    $cache = mm_get_mm_blocks();
  }

  if (!$allowed || \Drupal::currentUser()->hasPermission('administer all menus')) {
    return $cache;
  }

  return array_filter($cache, function(Block $block) {
    return empty($block->toArray()['settings']['admin_only']);
  });
}

/**
 * Search up the path, looking for the bottom-most block with an entry in
 * mm_tree_block
 *
 * @param int[] $mmtids
 *   List of tree IDs comprising the path to search
 * @param string $block_id
 *   - On entry:  Set to the block ID of the block to match, or leave as '' to
 *                search all blocks
 *   - On return: If '' on entry, this variable is set to the ID of the block
 *                that was found
 * @param $multiple
 *   If TRUE, return all blocks in the list
 * @return mixed
 *   Tree ID of the starting point, all blocks in the list (if $multiple), or 0
 *   on error
 */
function mm_content_get_blocks_at_mmtid(array $mmtids, &$block_id = '', $multiple = FALSE) {
  // Remove virtual directory
  $mmtids = array_filter($mmtids, function($mmtid) { return $mmtid >= 0; });
  // Reindex
  $mmtids = array_values($mmtids);

  if (!count($mmtids)) {
    return 0;
  }

  $db = Database::getConnection();
  $select = $db->select('mm_tree_block', 'tb');
  $select->join('mm_tree', 't', 'tb.mmtid = t.mmtid');
  $cond = new Condition('AND');
  $cond->condition('t.parent', (array) $mmtids, 'IN');
  if ($block_id != Constants::MM_MENU_DEFAULT && $block_id != Constants::MM_MENU_UNSET) {
    $cond->condition('tb.bid', (string) $block_id);
  }

  $or = new Condition('OR');
  $select->fields('t', array('mmtid', 'parent'))
    ->fields('tb', array('bid', 'max_depth', 'max_parents'))
    ->condition($or
      ->condition('t.mmtid', (array) $mmtids, 'IN')
      ->condition($cond));
  $direct = $by_parent = array();
  $result = $select->execute();
  foreach ($result as $r) {
    if (!in_array($r->mmtid, $mmtids)) {
      // Starting point of a sub-menu
      $by_parent[$r->parent][] = (array)$r;
    }
    else {
      $direct[$r->mmtid] = (array)$r;
    }
  }

  $blocks_with_node_contents = mm_get_mm_blocks(['settings.show_node_contents' => 1]);

  if ($direct || $by_parent) {
    for ($i = count($mmtids); --$i >= 0;) {
      $t = $mmtids[$i];
      if (isset($direct[$t])) {
        $entry = $direct[$t];
        if (!$block_id || $entry['bid'] == $block_id) {
          if (!$block_id) {
            $block_id = $entry['bid'];
          }
          if ($multiple) {
            return array($entry);
          }
          return $t;
        }
      }

      if (isset($by_parent[$t])) {
        foreach ($by_parent[$t] as $entry) {
          if (!$block_id || $entry['bid'] == $block_id) {
            // If show_node_contents is set, just go with it.
            $ok = isset($blocks_with_node_contents[$entry['bid']]);

            // Otherwise, see what block the parent is in.
            for ($j = $i; $j >= 0 && !$ok; $j--) {
              // If the parent is in a different block, it's OK.
              if (isset($direct[$mmtids[$j]])) {
                if ($direct[$mmtids[$j]]['bid'] == $entry['bid']) {
                  // Same block as parent, ignore it.
                  break;
                }
                $ok = TRUE;
              }
            }

            if ($ok) {
              if (!$block_id) {
                $block_id = $entry['bid'];
              }
              if ($multiple) {
                return $by_parent[$t];
              }
              return $entry['mmtid'];
            }
          }
        }
      }
    }
  }

  return 0;
}

/**
 * Return the tree ID of the /root/.Groups entry
 *
 * @return int
 *   The tree ID
 */
function mm_content_groups_mmtid() {
  $mmtid = \Drupal::state()->get('monster_menus.groups_mmtid');
  if (empty($mmtid)) {
    $tree = mm_content_get(array('parent' => 1, 'name' => Constants::MM_ENTRY_NAME_GROUPS));
    \Drupal::state()->set('monster_menus.groups_mmtid', $mmtid = $tree[0]->mmtid);
  }
  return $mmtid;
}

/**
 * Return the tree ID of the /root/.Users entry
 *
 * @return int
 *   The tree ID
 */
function mm_content_users_mmtid() {
  $mmtid = \Drupal::state()->get('monster_menus.users_mmtid');
  if (empty($mmtid)) {
    $tree = mm_content_get(array('parent' => 1, 'name' => Constants::MM_ENTRY_NAME_USERS));
    \Drupal::state()->set('monster_menus.users_mmtid', $mmtid = $tree[0]->mmtid);
  }
  return $mmtid;
}

/**
 * Return the alias of the /root/.Users entry
 *
 * @return string
 *   The alias
 */
function mm_content_users_alias() {
  if (is_null($alias = \Drupal::state()->get('monster_menus.users_alias', NULL))) {
    $tree = mm_content_get(array('parent' => 1, 'name' => Constants::MM_ENTRY_NAME_USERS));
    \Drupal::state()->set('monster_menus.users_alias', $alias = $tree[0]->alias);
  }
  return $alias;
}

/**
 * Return a list of user IDs in an MM group, query whether or not a given uid is
 * in one or more groups, or return all groups to which a uid belongs. This
 * function differs from mm_content_get_uids_in_group(), in that permissions are
 * not considered and the user's name is not returned.
 *
 * @param int|array $mmtids
 *   A single tree ID (gid) or an array of gids (can be empty or NULL)
 * @param int|array $uids
 *   A single user ID, or an array of user IDs to query (optional)
 * @param bool $normal
 *   If TRUE, consider "normal" (not ad-hoc or virtual) groups (optional)
 * @param bool $virtual
 *   If TRUE, consider virtual groups (optional)
 * @param bool $ad_hoc
 *   If TRUE, consider ad-hoc groups (optional)
 * @param Connection $database
 *   (optional) The database connection to use.
 * @return array
 *   If both $mmtids and $uids are set:
 *   - If $uids is a single value, return an array containing the groups
 *     matching $mmtids to which the user belongs
 *   - If $uids is an array, return an array where the key is the uid and the
 *     value is an array containing the groups matching $mmtids to which the
 *     user belongs
 *   If only $mmtids is set:
 *   - If $mmtids is a single value, return an array containing all uids that
 *     are members of the group
 *   - If $mmtids is an array, return an array where the key is the mmtid and
 *     the value is an array containing all uids that are members of the group
 *   If only $uids is set:
 *   - If $uids is a single value, return all mmtids (gids) to which the user
 *     belongs
 *   - If $uids is an array, return an array where the key is the uid and the
 *     value is an array containing all mmtids (gids) to which the user belongs
 *
 * At least one of $normal, $virtual, or $ad_hoc must be TRUE.
 */
function mm_content_get_uids_in_group($mmtids, $uids = NULL, $normal = TRUE, $virtual = TRUE, $ad_hoc = TRUE, Connection $database = NULL) {
  if (!$virtual && !$normal && !$ad_hoc) {
    return array();
  }

  if (!empty($mmtids)) {
    $in_mmtids = 'IN (:mmtids[])';
    if (!is_array($mmtids)) {
      $mmtids_single = TRUE;
    }
  }

  if (!empty($uids)) {
    $in_uids = 'IN (:uids[])';
    if (!is_array($uids)) {
      $uids = array($uids);
      $uids_single = TRUE;
    }

    // uid=0 should never appear in any group
    $uids = array_diff($uids, array(0));
    if (!$uids) {
      return array();
    }
  }

  $out = array();
  if (!empty($mmtids) && !empty($uids)) {
    $where1 = "g.gid $in_mmtids AND v.uid $in_uids";
    $where2 = "gid $in_mmtids AND uid $in_uids";
    $params = array(':mmtids[]' => (array) $mmtids, ':uids[]' => $uids);
  }
  elseif (!empty($mmtids)) {
    $where1 = "g.gid $in_mmtids";
    $where2 = "gid $in_mmtids AND uid > 0";
    $params = array(':mmtids[]' => (array) $mmtids);
  }
  elseif (!empty($uids)) {
    $where1 = "v.uid $in_uids";
    $where2 = "uid $in_uids";
    $params = array(':uids[]' => $uids);
  }
  else {
    return array();
  }

  $qs = array();
  if ($virtual) {
    $qs[] = 'SELECT g.gid, v.uid FROM {mm_group} g ' .
      'INNER JOIN {mm_virtual_group} v ON v.vgid = g.vgid ' .
      "WHERE $where1";
  }
  if ($normal || $ad_hoc) {
    if (!$normal) $where2 .= ' AND gid < 0';
    elseif (!$ad_hoc) $where2 .= ' AND gid > 0';

    $qs[] = 'SELECT gid, uid FROM {mm_group} ' .
      "WHERE $where2";
  }
  $database = $database ?: Database::getConnection();
  $query = $database->query(join(' UNION ', $qs), $params);

  if (!empty($uids)) {
    foreach ($query as $row) {
      $out[$row->uid][] = $row->gid;
    }
    if (!empty($uids_single)) {
      return isset($out[$uids[0]]) ? $out[$uids[0]] : array();
    }
    return $out;
  }

  foreach ($query as $row) {
    $out[$row->gid][] = $row->uid;
  }
  if (!empty($mmtids_single)) {
    return isset($out[$mmtids]) ? $out[$mmtids] : array();
  }
  return $out;
}

/**
 * Return a list of users in an MM group, suitable for presentation in the UI.
 * This function calls hook_mm_get_users_in_group_alter(), which can be used to
 * prevent the disclosure of group membership to unauthorized viewers.
 *
 * @param int|array $mmtid
 *   Tree ID (gid) of the group
 * @param string $sep
 *   If not set, return an array. If set, join the list of users with this
 *   string and return the result.
 * @param bool $halt
 *   If TRUE, and there are more than $limit matches, return NULL
 * @param int $limit
 *   If non-zero, limit the number of results. If the number of results would
 *   exceed the limit, either append '...' to the return (when a string), or add
 *   a '...' element to the returned array (when an array is requested).
 * @param bool $see_all
 *   If TRUE, and there are more than $limit matches, include a "see all users"
 *   link
 * @param array $render_array
 *   If $see_all is TRUE, this parameter must contain a reference to the render
 *   array being returned in the response. If there are more than $limit users,
 *   it will be modified to include the necessary Javascript libraries to
 *   display the full list in a modal dialog.
 * @return string | array
 *   The textual or array list, or possibly NULL if $halt is set
 */
function mm_content_get_users_in_group($mmtid, $sep = NULL, $halt = FALSE, $limit = 20, $see_all = FALSE, &$render_array = []) {
  $mmtids = is_array($mmtid) ? $mmtid : array($mmtid);
  foreach ($mmtids as $test_mmtid) {
    if (!mm_content_user_can($test_mmtid, Constants::MM_PERMS_READ)) {
      $msg = t('(not permitted to see list)');
      if (isset($sep)) {
        return $msg;
      }
      return array($msg);
    }
  }

  $limit_str = $limit ? "AND v.preview <= $limit + 1 " : '';
  $lim = '';
  $db = Database::getConnection();

  $mmtid_match = 'IN(' . join(', ', $mmtids) . ')';
  $qs = 'SELECT %s FROM ((SELECT %s FROM ' .
          '(SELECT u.uid, u.name FROM ' .
            "(SELECT * FROM {mm_group} WHERE gid $mmtid_match) AS g " .
          "INNER JOIN {mm_virtual_group} v ON v.vgid = g.vgid $limit_str" .
          'INNER JOIN {users_field_data} u ON u.uid = v.uid) AS u) ' .
        'UNION ' .
        '(SELECT %s FROM ' .
          '(SELECT u.uid, u.name FROM ' .
            "(SELECT * FROM {mm_group} WHERE gid $mmtid_match AND uid > 0) AS g " .
          'INNER JOIN {users_field_data} u ON u.uid = g.uid) AS u)) x';

  $query = sprintf($qs . ' ORDER BY x.name', '*', 'u.uid, u.name', 'u.uid, u.name');
  $countquery = sprintf($qs, 'SUM(c)', 'COUNT(DISTINCT u.uid) AS c', 'COUNT(DISTINCT u.uid) AS c', '');

  mm_module_invoke_all_array('mm_get_users_in_group_alter', array($mmtids, &$query, &$countquery));

  if ($limit > 0) {
    if ($halt) {
      $r = $db->query($countquery)->fetchField();
      if ($r == NULL || $r > $limit) {
        return NULL;
      }
    }
    else {
      $lim .= ' LIMIT ' . ($limit + 1);
    }
  }

  $query .= $lim;
  $q = $db->query($query);

  $users = array();
  foreach ($q as $r) {
    $users[$r->uid] = mm_content_uid2name($r->uid, 'lfmu', $r);
  }

  if (!$halt && $limit > 0 && count($users) == $limit + 1 && count($mmtids) == 1) {
    $overflow = TRUE;
    array_pop($users);
    if ($see_all && $mmtids[0] > 0) {
      $tree = mm_content_get($mmtids[0]);
      $see_link = Link::fromTextAndUrl(
        t('See all users in this group'),
        Url::fromRoute('monster_menus.show_group', ['mm_tree' => $mmtids[0]],
        array(
          'attributes' => array(
            // The content of the iframe is loaded quite a while after the
            // dialog opens, so set a minHeight to get it to center correctly.
            'id' => mm_ui_modal_dialog(array('iframe' => TRUE, 'height' => 570, 'minHeight' => 570), $render_array),
            'title' => t('All users in the group @name', array('@name' => mm_content_get_name($tree))), // can't use %name here
          ),
        ))
      )->toString();
      $users = array_merge(array(-1 => $see_link), $users);
    }
  }

  if (!empty($overflow)) {
    $users[''] = '...';
  }

  if (!isset($sep)) {
    return $users;
  }

  return implode($sep, $users);
}

/**
 * Ensure that a particular alias will not conflict with the core menu tree, or
 * other entries already at the same level, by adding "_N" to it until it no
 * longer conflicts.
 *
 * @param string $alias
 *   The initial alias
 * @param parent $mmtid
 *   The tree ID of the parent entry
 * @return string
 *   The safe alias
 */
function mm_content_get_safe_alias($alias, $mmtid) {
  $i = 0;
  $test = $alias;
  while (mm_content_alias_conflicts($test, $mmtid, TRUE) || mm_content_alias_exists($alias, $mmtid)) {
    $test = $alias . '_' . (++$i);
  }
  return $test;
}

/**
 * Get the list of words that can never be used as URL aliases.
 *
 * @return array
 *   An array containing the list of words
 */
function mm_content_reserved_aliases() {
  return \Drupal::state()->get('monster_menus.reserved_alias', mm_content_reserved_aliases_base());
}

/**
 * Get the base list of words that can never be used as URL aliases. This is
 * before any words that are added based on Drupal menu entries.
 *
 * @return array
 *   An array containing the list of words
 */
function mm_content_reserved_aliases_base() {
  return array(
    'feed',   // Appending "feed" onto a URL produces an RSS feed of the page.
  );
}

/**
 * Ensure that a particular alias will not conflict with the core menu tree
 * and/or one of the reserved aliases.
 *
 * @param string $alias
 *   The initial alias
 * @param int $mmtid
 *   The tree ID of the parent entry
 * @param bool $check_reserved
 *   If TRUE, check against mm_content_reserved_aliases() list and core menus;
 *   otherwise just core menus
 * @return bool
 *   TRUE if the alias conflicts
 */
function mm_content_alias_conflicts($alias, $mmtid, $check_reserved = TRUE) {
  return
    $check_reserved && in_array($alias, mm_content_reserved_aliases()) ||
    in_array($alias, \Drupal::state()->get('monster_menus.top_level_reserved', [])) && mm_content_get_parent($mmtid) == mm_home_mmtid();
}

/**
 * Ensure that a particular alias does not already exist at a particular level
 * of the tree.
 *
 * @param string $alias
 *   The initial alias
 * @param int $mmtid
 *   The tree ID of the parent entry
 * @return bool
 *   TRUE if a tree entry with the alias already exists
 */
function mm_content_alias_exists($alias, $mmtid) {
  return (bool) mm_content_get(array('parent' => $mmtid, 'alias' => $alias));
}

/**
 * Create a user's home directory in the MM tree.
 *
 * @param AccountInterface $account
 *   The user object describing the account being added.
 * @return RedirectResponse|null
 *   The response object which redirects the user to the new home directory.
 * @throws NotFoundHttpException
 */
function mm_content_create_homepage(AccountInterface $account) {
  if (!empty($account->user_mmtid) && ($perms = mm_content_user_can($account->user_mmtid)) && $perms[Constants::MM_PERMS_IS_RECYCLED]) {
    // Restore a page that is in the recycle bin.
    mm_content_move_from_bin($account->user_mmtid);
  }
  else {
    if (($exists = mm_content_get($account->user_mmtid, Constants::MM_GET_FLAGS)) && ($parent = mm_content_get($exists->parent)) && $parent->name == Constants::MM_ENTRY_NAME_DISABLED_USER) {
      // Unset flag in a homepage that is in the "disabled" tree.
      unset($exists->flags['user_home']);
      mm_content_set_flags($exists->mmtid, $exists->flags);
      $account->user_mmtid = NULL;
    }
    // Create from scratch.
    mm_content_add_user($account);
  }

  if ($account->user_mmtid) {
    return new RedirectResponse(mm_content_get_mmtid_url($account->user_mmtid)->toString());
  }

  throw new NotFoundHttpException();
}

/**
 * Create a user's home directory in the MM tree.
 *
 * @param AccountInterface $account
 *   The user object describing the account being added
 */
function mm_content_add_user(AccountInterface $account) {
  if (!empty($account->user_mmtid) || !mm_get_setting('user_homepages.enable')) {
    return;
  }

  $users_mmtid = mm_content_users_mmtid();
  $default_tree = mm_content_get(array('name' => Constants::MM_ENTRY_NAME_DEFAULT_USER, 'parent' => $users_mmtid));
  if (!count($default_tree)) {
    \Drupal::logger('user')->error('Missing @path', array('@path' => '/' . Constants::MM_ENTRY_NAME_USERS . '/' . Constants::MM_ENTRY_NAME_DEFAULT_USER));
    return;
  }

  $fullname = mm_content_uid2name($account->id(), 'lfmu', NULL, $hover);

  $dest_mmtid = $users_mmtid;
  foreach (mm_module_implements('mm_add_user_alter') as $module) {
    $function = $module . '_mm_add_user_alter';
    if ($function($account, $dest_mmtid, $fullname) === FALSE) {
      return;
    }
  }

  // Find the first empty slot with a name ending in either $name or
  // '$name (username)'
  for ($i = 0;;) {
    $test = !$i ? preg_replace('/ \(.*?\)$/', '', $fullname) : $fullname;
    $exists = mm_content_get(array('name' => $test, 'parent' => $users_mmtid));
    if (!count($exists)) {
      $name = $test;
      break;
    }

    $other = User::load($exists[0]->uid);
    // If owner of homedir no longer exists or his username is the same as
    // the one on the new account, move the old homedir out of the way.
    if ($other === FALSE || $other->getAccountName() == $account->getAccountName()) {
      $name = $test;
      if (($err = mm_content_move_to_disabled($exists[0]->mmtid)) !== FALSE) {
        \Drupal::logger('user')->error('Error moving existing account %name into @path: @message', array('%name' => $name, '@path' => Constants::MM_ENTRY_NAME_DISABLED_USER, '@message' => $err));
        return;
      }
      else {
        \Drupal::logger('user')->warning('Moved existing account %name into @path', array('%name' => $name, '@path' => Constants::MM_ENTRY_NAME_DISABLED_USER));
      }

      break;
    }

    if ($i++) {
      \Drupal::logger('user')->error('Could not create %test homepage because it already exists', array('%test' => $test));
      return;
    }
  }

  $copy_params = array(
    Constants::MM_COPY_ALIAS =>    mm_content_get_safe_alias($account->getAccountName(), $dest_mmtid),
    Constants::MM_COPY_CONTENTS => TRUE,
    Constants::MM_COPY_NAME =>     $name,
    Constants::MM_COPY_OWNER =>    $account->id(),
  );
  $new_mmtid = mm_content_copy($default_tree[0]->mmtid, $dest_mmtid, $copy_params);

  if (is_numeric($new_mmtid)) {
    $account->user_mmtid = $new_mmtid;
    mm_content_set_flags($new_mmtid, array('user_home' => $account->id()), FALSE);
    mm_content_update_quick(array('hover' => $hover), array('mmtid' => $new_mmtid));
    mm_module_invoke_all_array('mm_add_user_post', array(&$account, $new_mmtid, $dest_mmtid));
  }
  else {
    \Drupal::logger('user')->error('Error copying default user home dir into %name: @message', array('%name' => $name, '@message' => $new_mmtid));
  }
  mm_content_clear_caches($users_mmtid);
}

/**
 * Copy an item (and, optionally, its children) within the MM tree.
 *
 * @param int $src_mmtid
 *   Tree ID of the entry to start copying from
 * @param int $dest_mmtid
 *   Tree ID of the entry to copy to
 * @param array $options
 *   An array containing options. The array is indexed using the constants
 *   below.
 *   - MM_COPY_ALIAS (NULL):
 *     URL alias of the new item, or NULL to keep the original value
 *   - MM_COPY_COMMENTS (FALSE):
 *     If TRUE, and MM_COPY_CONTENTS is also TRUE, copy the comments associated
 *     with any contents
 *   - MM_COPY_CONTENTS (FALSE):
 *     If TRUE, copy the contents of the page(s)
 *   - MM_COPY_ITERATE_ALTER (none):
 *     If set, this function or array of functions is called before any
 *     processing is done on each entry in the tree. If the function returns -1,
 *     the entry and any children will be skipped; if it returns 1, just the
 *     current entry is skipped; if it returns 0, all further processing is
 *     canceled; any other return value leads to no change. The function can
 *     also alter the item passed to it.
 *   - MM_COPY_NAME (NULL):
 *     Name of the new, top-level item, or NULL to keep the original value
 *   - MM_COPY_NODE_PRESAVE_ALTER (none):
 *     If set, this function or array of functions is passed copied nodes just
 *     before creation, for possible alteration
 *   - MM_COPY_OWNER (no change):
 *     If set, change the owner of the copies to this UID
 *   - MM_COPY_READABLE (FALSE):
 *     If TRUE, only copy entries readable by the user
 *   - MM_COPY_RECUR (TRUE):
 *     If TRUE, copy recursively (include all children)
 *   - MM_COPY_TREE (TRUE):
 *     If TRUE, copy the page(s)
 *   - MM_COPY_TREE_PRESAVE_ALTER (none):
 *     If set, this function or array of functions is passed the new page's
 *     description just before creation, for possible alteration
 *   - MM_COPY_TREE_SKIP_DUPS (FALSE):
 *     If TRUE, a check is done to ensure that tree entries with the same
 *     aliases as existing entries are not created in the destination
 * @return int|string
 *   If successful, the tree ID of the first, new entry; otherwise, a human-
 *   readable error message
 */
function mm_content_copy($src_mmtid, $dest_mmtid, $options) {
  $iter = new ContentCopyIter($src_mmtid, $dest_mmtid, $options);
  if ($iter->options[Constants::MM_COPY_RECUR]) {
    if ($msg = _mm_content_test_copy_move($src_mmtid, $dest_mmtid)) {
      return $msg;
    }
  }

  $params = array(
    Constants::MM_GET_TREE_DEPTH        => $iter->options[Constants::MM_COPY_RECUR] ? -1 : 0,
    Constants::MM_GET_TREE_ITERATOR     => $iter,
    Constants::MM_GET_TREE_RETURN_PERMS => $iter->options[Constants::MM_COPY_READABLE] ? TRUE : NULL,
    Constants::MM_GET_TREE_RETURN_FLAGS => TRUE,
  );
  mm_content_get_tree($src_mmtid, $params);

  mm_content_clear_caches($dest_mmtid);
  \Drupal::logger('mm')->notice('Copied mmtid=@src to parent=@dest@recur, new name=%name (%alias)', array('@src' => $src_mmtid, '@dest' => $dest_mmtid, '@recur' => $iter->options[Constants::MM_COPY_RECUR] ? ' ' . t('recursively') : '', '%name' => $iter->options[Constants::MM_COPY_NAME], '%alias' => $iter->options[Constants::MM_COPY_ALIAS]));

  return $iter->output();
}

/**
 * Move an entry within the MM tree.
 *
 * @param int $src_mmtid
 *   Tree ID of the entry to move
 * @param int $dest_mmtid
 *   Tree ID of the destination entry (new parent)
 * @param string $recycle_mode
 *   Set to either 'recycle' or 'restore' to indicate if we are manipulating the
 *   recycle bin.
 * @return bool|string
 *   FALSE if successful; otherwise, a human-readable error message
 */
function mm_content_move($src_mmtid, $dest_mmtid, $recycle_mode = '') {
  if ($msg = _mm_content_test_copy_move($src_mmtid, $dest_mmtid)) {
    return $msg;
  }

  $src = mm_content_get($src_mmtid);
  $list = mm_content_get_parents_with_self($dest_mmtid, FALSE, FALSE); // don't include virtual parents
  $bin = $recycle_mode == 'recycle' ? $dest_mmtid : mm_content_get_parent($src_mmtid);
  $iter = new ContentMoveIter($list, $recycle_mode, $bin, $src->sort_idx);
  $params = array(
    Constants::MM_GET_TREE_RETURN_PERMS => $recycle_mode == 'recycle' ? TRUE : NULL,
    Constants::MM_GET_TREE_DEPTH        => -1,
    Constants::MM_GET_TREE_ITERATOR     => $iter,
  );
  mm_content_get_tree($src_mmtid, $params);

  mm_content_clear_caches(array($src_mmtid, $dest_mmtid));

  foreach (array_keys($iter->delete_bins) as $bin) {
    mm_content_delete_bin($bin);
  }

  $vals = array('@src' => $src_mmtid, '@dest' => $dest_mmtid);
  switch ($recycle_mode) {
    case 'recycle':
      \Drupal::logger('mm')->notice('Recycled mmtid=@src to bin=@dest', $vals);
      break;

    case 'restore':
      mm_content_clear_caches($iter->bin);
      \Drupal::logger('mm')->notice('Restored mmtid=@src to parent=@dest', $vals);
      break;

    default:
      \Drupal::logger('mm')->notice('Moved mmtid=@src to new parent=@dest', $vals);
  }

  if ($iter->error) {
    \Drupal::logger('mm')->error('Move error: %error', array('%error' => $iter->error));
    return $iter->error;
  }
  return FALSE;
}

/**
 * Move a user's home directory into /.Users/.Disabled within the MM tree.
 *
 * @param int $mmtid
 *   Tree ID of the entry to move
 * @return bool|string
 *   FALSE if successful; otherwise, a human-readable error message
 */
function mm_content_move_to_disabled($mmtid) {
  $user_dir = mm_content_get(array('name' => Constants::MM_ENTRY_NAME_USERS, 'parent' => 1));
  if (!$user_dir) {
    return t('@dir not found', array('@dir' => Constants::MM_ENTRY_NAME_USERS));
  }

  $disab_dir = mm_content_get(array('name' => Constants::MM_ENTRY_NAME_DISABLED_USER, 'parent' => $user_dir[0]->mmtid));
  if (!$disab_dir) {
    return t('@dir not found', array('@dir' => Constants::MM_ENTRY_NAME_DISABLED_USER));
  }

  // This might create duplicate names in /.Users/.Disabled, but it's probably
  // best to leave them.
  return mm_content_move($mmtid, $disab_dir[0]->mmtid);
}

function _mm_content_get_next_sort($parent) {
  $db = Database::getConnection();
  $max = $db->select('mm_tree', 't')
    ->fields('t', array('sort_idx'))
    ->condition('t.parent', $parent)
    ->orderBy('t.sort_idx', 'DESC')
    ->range(0, 1)
    ->execute()->fetchField();
  if (empty($max)) {
    $parent_sort_idx = $db->select('mm_tree', 't')
      ->fields('t', array('sort_idx'))
      ->condition('t.mmtid', $parent)
      ->execute()->fetchField();
    return $parent_sort_idx . _mm_content_btoa(0);
  }
  return substr($max, 0, -Constants::MM_CONTENT_BTOA_CHARS) . _mm_content_btoa(_mm_content_atob(substr($max, -Constants::MM_CONTENT_BTOA_CHARS)) + 1);
}

function _mm_content_test_sort_length($sort_idx, $msg, $critical = FALSE) {
  static $max, $did_error;

  if (empty($max)) {
    $max = \Drupal::service('entity_field.manager')->getActiveFieldStorageDefinitions('mm_tree')['sort_idx']->getSetting('max_length');
  }

  if (strlen($sort_idx) > $max) {
    if (empty($did_error)) {
      if (is_numeric($msg)) {
        $vars = array('@mmtid' => $msg);
        $msg = 'The tree is nested too deeply, starting at mmtid=@mmtid. Presentation of sorted trees will suffer. To correct this problem, increase the length of mm_tree.sort_idx then run mm_content_update_sort().';
      }
      else {
        $vars = $msg[1];
        $msg = $msg[0];
      }

      $critical ? \Drupal::logger('mm')->critical($msg, $vars) : \Drupal::logger('mm')->notice($msg, $vars);
      $did_error = TRUE;
    }
    return FALSE;
  }
  return TRUE;
}

function _mm_content_test_copy_move($src_mmtid, $dest_mmtid) {
  // Don't allow a copy/move to happen if the resulting sort_idx would be too long
  $max_sort_idx = Database::getConnection()->query('SELECT ' .
    'CONCAT(' .
      '(SELECT sort_idx FROM {mm_tree} WHERE mmtid = :dest_mmtid), ' .
      'SUBSTR(' .
        "REPEAT('x', " .
          '(SELECT MAX(LENGTH(t.sort_idx)) ' .
            'FROM {mm_tree_parents} p ' .
            'INNER JOIN {mm_tree} t ON t.mmtid = p.mmtid ' .
            'WHERE p.parent = :src_mmtid1 OR t.mmtid = :src_mmtid2' .
          ')' .
        '), ' .
        '(SELECT LENGTH(sort_idx) ' .
          'FROM {mm_tree} WHERE mmtid = :src_mmtid3' .
        ') - :length' .
      ')' .
    ')',
    array(
      ':dest_mmtid' => $dest_mmtid,
      ':src_mmtid1' => $src_mmtid,
      ':src_mmtid2' => $src_mmtid,
      ':src_mmtid3' => $src_mmtid,
      ':length' => Constants::MM_CONTENT_BTOA_CHARS - 1,
    )
  )->fetchField();
  $msg = array(
    'An attempt to copy or move mmtid=@src to mmtid=@dest failed, because it would result in a tree that is too deeply nested. To correct this problem, increase the length of mm_tree.sort_idx then run mm_content_update_sort().',
    array('@src' => $src_mmtid, '@dest' => $dest_mmtid),
  );
  if (!_mm_content_test_sort_length($max_sort_idx, $msg)) {
    return t('This operation cannot be performed because it would cause the tree to become too deeply nested. Please contact a system administrator.');
  }
  return FALSE;
}

/**
 * Add a new entry or replace an existing entry in the MM tree.
 *
 * @param bool $add
 *   TRUE if the entry is new
 * @param int $mmtid
 *   Tree ID of the new entry's parent ($add=TRUE), or the ID of the entry to
 *   replace
 * @param array|object $parameters
 *   Either an array or an object with one more more of the attributes listed
 *   below. Other modules can add settings using hook_mm_cascaded_settings().
 *   - 'alias':
 *     The entry's alias
 *   - 'archive_mmtid':
 *     If non-zero, the MM Tree ID of the page containing the archive contents.
 *     See also: frequency, main_nodes
 *   - 'cascaded':
 *     An array containing these possible elements, which describe settings that
 *     are cascaded downward in the tree to any children:
 *     - 'allowed_node_types':
 *       If not NULL, then set the entry's allowed node type list to this array
 *       of values. To indicate that the parent settings should be inherited,
 *       pass an empty array or NULL. To indicate that there should be no node
 *       types allowed, pass array('').
 *     - 'allow_reorder':
 *       Allow users with write access to reorder the menu of this page and its
 *       children (-1 = inherit)
 *     - 'allowed_themes':
 *       If not NULL, then set the entry's allowed theme list to this array of
 *       values
 *     - 'comments_readable':
 *       Default comment readability for new nodes added to this entry
 *     - 'hide_menu_tabs':
 *       Hide the Contents/Settings/etc. tabs from non-admin users
 *       (-1 = inherit)
 *   - 'comment':
 *     Default comment mode for new content added to this entry (0)
 *   - 'default_mode':
 *     Default access mode for the entry, a comma-separated list of
 *     MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB, MM_PERMS_APPLY ('')
 *   - 'flags':
 *     A single string or array of strings in flag => value format to be added
 *     to the entry (admins. only)
 *   - 'frequency':
 *     The time division for the archive. Must be one of 'day", 'week', 'month'
 *     or 'year'.
 *   - 'hidden':
 *     If set, the entry only appears in menus if the user can edit it or add
 *     content to it (FALSE)
 *   - 'hover':
 *     Text for title attribute, displayed when the mouse hovers over the link
 *     ('')
 *   - 'main_nodes':
 *     The number of pieces of content to show on the main page of an archive
 *   - 'max_depth':
 *     If $menu_start is set, the maximum depth of the menu block (-1)
 *   - 'max_parents':
 *     If $menu_start is set, the maximum number of parent levels to display
 *     (-1)
 *   - 'members':
 *     For non-virtual groups, an array of the uids of the group's members; if
 *     an empty string (''), do not change any existing users
 *   - 'menu_start':
 *     If non-zero, a menu block using this block ID starts at this level ('')
 *   - 'name':
 *     The entry's name
 *   - 'node_info':
 *     The default value for showing 'Submitted by' lines on nodes added to this
 *     page (TRUE)
 *   - 'perms':
 *     An array of arrays [MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB,
 *     MM_PERMS_APPLY]['groups', 'users']. All are optional. For 'groups',
 *     provide an array of gids; for 'users' an array of uids.
 *   - 'previews':
 *     If set, show all nodes on the page as teasers (FALSE)
 *   - 'propagate_node_perms':
 *     If TRUE, and !$add, set the permissions on all nodes for which the user
 *     has write access to match the entry. If recurs_perms is also true,
 *     recursively set the permissions on the nodes on all children as well.
 *     (FALSE)
 *   - 'qfield':
 *     For a virtual group, the "column to select" portion of the query; ignored
 *     for non-virtual groups
 *   - 'qfrom':
 *     For a virtual group, the "FROM clause" portion of the query; ignored for
 *     non-virtual groups
 *   - 'recurs_perms':
 *     If TRUE, and !$add, recursively set the permissions on all children for
 *     which the user has write access to match the parent. (FALSE)
 *   - 'rss':
 *     If set, show an 'Add this page to my portal' button on the page (FALSE)
 *   - 'theme':
 *     Name of the entry's theme, if any (none)
 *   - 'uid':
 *     User ID of the entry's owner (1)
 *   - 'weight':
 *     Order of the entry among its siblings (0)
 * @param array|string $stats
 *   (optional) Array with which to populate statistics:
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
 *     which describe any errors that occurred.
 *   A count of the number of pages acted upon can be derived using the count()
 *   function.
 * @return
 *   Tree ID of the entry that was added or replaced, or 0 on error
 * @throws \Exception
 *   Any exception occurring during the insert/update
 */
function mm_content_insert_or_update($add, $mmtid, $parameters, &$stats = 'undef') {
  static $defaults = array(
    'alias' => '',
    'archive_mmtid' => 0,
    'cascaded' => array(),
    'comment' => 0,
    'default_mode' => '',
    'flags' => '',
    'frequency' => '',
    'hidden' => FALSE,
    'hover' => '',
    'large_group_form_token' => '',
    'main_nodes' => 10,
    'max_depth' => -1,
    'max_parents' => -1,
    'members' => '',
    'menu_start' => Constants::MM_MENU_UNSET,
    'name' => '',
    'node_info' => TRUE,
    'perms' => array(),
    'previews' => FALSE,
    'propagate_node_perms' => FALSE,
    'qfield' => '',
    'qfrom' => '',
    'recurs_perms' => FALSE,
    'rss' => FALSE,
    'theme' => '',
    'uid' => 1,
    'weight' => 0,
  );
  static $cascaded_settings;

  $parameters = (array)$parameters;
  foreach (array_keys($parameters) as $p) {
    if (!isset($defaults[$p])) {
      \Drupal::logger('mm')->critical('Unknown parameter %name to mm_content_insert_or_update', array('%name' => $p));
      \Drupal::messenger()->addStatus(t('An error occurred.'));
      return 0;
    }

    if ($p == 'cascaded') {
      if (!isset($cascaded_settings)) {
        $cascaded_settings = mm_content_get_cascaded_settings();
      }
      foreach (array_keys($parameters[$p]) as $c) {
        if (!isset($cascaded_settings[$c])) {
          \Drupal::logger('mm')->critical('Unknown cascaded setting %name in mm_content_insert_or_update', array('%name' => $c));
          \Drupal::messenger()->addStatus(t('An error occurred.'));
          return 0;
        }
      }
    }
  }
  $parameters = array_merge($defaults, $parameters);

  $is_group = mm_content_is_group($mmtid);
  $database = Database::getConnection();

  if ($add) {
    $parameters['parent'] = $mmtid;
    try {
      $new = MMTree::create($parameters);
      // Note: save() automatically writes a revision.
      $new->save();
      $mmtid = $new->id();
      mm_content_notify_change('insert_page', $mmtid, NULL, $parameters);
      _mm_report_stat($is_group, $mmtid, 'Added %name (%alias) mmtid=@mmtid', array('%name' => $parameters['name'], '%alias' => $parameters['alias']), $stats, TRUE);
    }
    catch (\Exception $e) {
      watchdog_exception('mm', $e);
      throw $e;
    }
  }
  else {
    if ($parameters['recurs_perms']) {
      $list = array();
      foreach (mm_content_get_tree($mmtid, array(Constants::MM_GET_TREE_RETURN_PERMS => TRUE)) as $t) {
        if ($t->perms[Constants::MM_PERMS_WRITE]) {
          $list[] = $t->mmtid;
        }
        if (!isset($old)) {
          $old = $t;
        }
      }
    }
    else {
      $list = array($mmtid);
      $old = mm_content_get($mmtid);
    }

    $count = 0;
    foreach ($list as $t) {
      unset($parameters['sort_idx_dirty']);

      $transaction = $database->startTransaction();
      try {
        if ($count++) {    // recursive: item after the first
          unset($parameters['parent']);
          $updated_rows = $database->update('mm_tree', array('return' => Database::RETURN_AFFECTED))
            ->fields(array('default_mode' => $parameters['default_mode'], 'uid' => $parameters['uid']))
            ->condition('mmtid', $t)
            ->execute();
          if ($updated_rows) {
            mm_content_write_revision($t);
            mm_content_set_perms($t, $parameters['perms'], $is_group, TRUE);
            mm_content_clear_routing_cache_tagged($t);
          }
        }
        else {             // first item only
          $parameters['parent'] = $old->parent;
          $parameters['mmtid'] = $parameters['id'] = $t;
          $parameters['sort_idx'] = $old->sort_idx;
          $parameters['sort_idx_dirty'] = $old->sort_idx_dirty;
          $parameters['ctime'] = $old->ctime;
          $parameters['cuid'] = $old->cuid;
          // Note: save() automatically writes a revision.
          MMTree::create($parameters)
            ->setOldSortValues($old->name, $old->weight, $old->hidden, $old->parent)
            ->enforceIsNew(FALSE)
            ->save();
        }

        mm_content_notify_change('update_page', $t, NULL, $parameters);

        // Copy the permissions onto nodes attached to the entry if requested.
        if ($parameters['propagate_node_perms']) {
          /** @var NodeInterface $node */
          foreach (Node::loadMultiple(mm_content_get_nids_by_mmtid($t)) as $node) {
            if ($node && $node->access('update')) {
              $node->users_w = is_array($parameters['perms'][Constants::MM_PERMS_WRITE]['users']) ? array_flip($parameters['perms'][Constants::MM_PERMS_WRITE]['users']) : array();
              if (is_array($parameters['perms'][Constants::MM_PERMS_APPLY]['users'])) {
                $node->users_w += array_flip($parameters['perms'][Constants::MM_PERMS_APPLY]['users']);
              }
              $node->groups_w = is_array($parameters['perms'][Constants::MM_PERMS_WRITE]['groups']) ? array_flip($parameters['perms'][Constants::MM_PERMS_WRITE]['groups']) : array();
              if (is_array($parameters['perms'][Constants::MM_PERMS_APPLY]['groups'])) {
                $node->groups_w += array_flip($parameters['perms'][Constants::MM_PERMS_APPLY]['groups']);
              }
              $node->others_w = strpos($parameters['default_mode'], Constants::MM_PERMS_WRITE) !== FALSE || strpos($parameters['default_mode'], Constants::MM_PERMS_APPLY) !== FALSE;

              mm_content_set_node_perms($node);
            }
          }
        }
      }
      catch (\Exception $e) {
        $transaction->rollback();
        watchdog_exception('mm', $e);
        throw $e;
        break;
      }
    }

    mm_content_clear_caches();  // clear everything, because there may be affected kids, even without recursion

    $did_perms = array();
    if ($parameters['recurs_perms']) {
      $did_perms[] = ' ' . t('Permissions were copied to all children.');
    }
    if ($parameters['propagate_node_perms']) {
      $did_perms[] = ' ' . t('Permissions were copied to nodes.');
    }
    _mm_report_stat($is_group, $mmtid, 'Updated %name (%alias) mmtid = @mmtid.@perms', array('%name' => $parameters['name'], '%alias' => $parameters['alias'], '@perms' => join('', $did_perms)), $stats, TRUE);
  }

  return $mmtid;
}

/**
 * Update an existing entry in the MM tree. Only attributes which are stored in
 * the mm_tree table are supported.
 *
 * @param array|object $parameters
 *   Either an array or an object with one more more of the attributes listed
 *   below. Only those attributes which are listed can be updated.
 *   - 'alias':
 *     The entry's alias
 *   - 'comment':
 *     Default comment mode for new content added to this entry
 *   - 'default_mode':
 *     Default access mode for the entry, a comma-separated list of
 *     MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB, MM_PERMS_APPLY
 *   - 'hidden':
 *     If set, the entry only appears in menus if the user can edit it or add
 *     content to it
 *   - 'hover':
 *     Text for title attribute, displayed when the mouse hovers over the link
 *   - 'name':
 *     The entry's name
 *   - 'node_info':
 *     The default value for showing 'Submitted by' lines on nodes added to this
 *     page
 *   - 'previews':
 *     If set, show all nodes on the page as teasers
 *   - 'rss':
 *     If set, show an 'Add this page to my portal' button on the page
 *   - 'theme':
 *     Name of the entry's theme, if any
 *   - 'uid':
 *     User ID of the entry's owner
 *   - 'weight':
 *     Order of the entry among its siblings
 * @param int|array $where
 *   Defines what tree entries to update. If a single value is supplied, then it
 *   it treated as the tree ID (mmtid) of an entry.
 *
 *   An associative array can also be supplied; it must contain one or more
 *   attributes from the list above, or "mmtid", as the array key. The array
 *   value becomes part of the update query WHERE. At a minimum, "mmtid" must be
 *   specified.
 * @param int|null $parent
 *   If the name, hidden, or weight attribute is being changed, the parent tree
 *   ID may need to be queued for later update. If it is needed and not
 *   specified in $parent, it will be retrieved using an additional query.
 * @param bool $revision
 *   If TRUE, update the mm_tree_revision table
 */
function mm_content_update_quick($parameters, $where, $parent = NULL, $revision = TRUE) {
  $parameters = (array)$parameters;
  if (!is_array($where)) {
    $where = array('mmtid' => $where);
  }

  $special = array_intersect_key($parameters, array('name' => 1, 'hidden' => 1, 'weight' => 1));
  $not_special = array_diff_key($parameters, $special);
  if ($special) {
    if ($not_special) {
      mm_content_update_quick($special, $where, $parent, FALSE);
      $parameters = $not_special;
      unset($special);
    }
    else {
      $parameters['sort_idx_dirty'] = 1;
    }
  }

  $schema = \Drupal::service('entity_field.manager')->getActiveFieldStorageDefinitions('mm_tree');

  foreach (array_keys($parameters) as $p) {
    if (!isset($schema[$p])) {
      \Drupal::logger('mm')->critical('Unknown attribute %name in $parameters to mm_content_update_quick', array('%name' => $p));
      \Drupal::messenger()->addStatus(t('An error occurred.'));
      return;
    }
  }

  foreach (array_keys($where) as $p) {
    if (!isset($schema[$p])) {
      \Drupal::logger('mm')->critical('Unknown attribute %name in $where to mm_content_update_quick', array('%name' => $p));
      \Drupal::messenger()->addStatus(t('An error occurred.'));
      return;
    }
  }

  $update = Database::getConnection()->update('mm_tree', array('return' => Database::RETURN_AFFECTED));
  $special_conditions = new Condition('OR');
  $fields = array();

  foreach (array_intersect(array_keys($schema), array_keys($parameters)) as $field) {
    if ($field != 'mmtid') {
      $fields[$field] = $parameters[$field];
      if (!empty($special) && $field != 'sort_idx_dirty') {
        $special_conditions->condition($field, $parameters[$field], '<>');
        $have_special = TRUE;
      }
    }
  }

  $update->fields($fields);
  if (!empty($have_special)) {
    $update->condition($special_conditions);
  }

  foreach ($where as $key => $val) {
    if (is_array($val)) {
      $update->condition($key, $val, 'IN');
    }
    else {
      $update->condition($key, $val);
    }
  }

  if ($update->execute()) {   // There were affected rows
    if ($revision) {
      if (!isset($where['mmtid'])) {
        unset($fields['sort_idx_dirty']);
        if ($tree = mm_content_get($fields, array(), 1)) {
          $where['mmtid'] = $tree[0]->mmtid;
        }
      }

      if (isset($where['mmtid'])) {
        mm_content_write_revision($where['mmtid']);
      }
    }

    if (!empty($special)) {
      if (empty($parent)) {
        $parent = NULL;
      }
      $child = !isset($where['mmtid']) ? NULL : $where['mmtid'];
      if (!is_null($parent) || !is_null($child)) {
        mm_content_update_sort_queue($parent, $child);
      }
    }

    if (isset($parameters['alias'])) {
      mm_content_clear_routing_cache_tagged(isset($where['mmtid']) ? $where['mmtid'] : []);
    }

    if (isset($where['mmtid'])) {
      if (!is_null($parent)) {
        $parameters['parent'] = $parent;
      }
      mm_content_notify_change('update_page_quick', $where['mmtid'], NULL, $parameters);
    }
  }
}

/**
 * Makes a copy of an mm_tree entry to the mm_tree_revision table
 *
 * @param int $mmtid
 *   The tree ID of the entry being copied
 */
function mm_content_write_revision($mmtid) {
  $is_default_revision = TRUE;
  $has_metadata_key = FALSE;
  if ($mm_tree = MMTree::load($mmtid)) {
    $has_metadata_key = $mm_tree->getEntityType()->getRevisionMetadataKey('revision_default');
    $is_default_revision = $mm_tree->isDefaultRevision();
  }
  $schema_rev = mm_get_table_columns('mm_tree_revision');
  $db = Database::getConnection();
  $select = $db->select('mm_tree', 't');
  foreach ($schema_rev as $field) {
    if (!in_array($field, array('vid', 'muid', 'mtime', 'revision_default'))) {
      $select->addField('t', $field);
    }
  }
  $select->addExpression(':muid', 'muid', array(':muid' => \Drupal::currentUser()->id()));
  $select->addExpression(':mtime', 'mtime', array(':mtime' => mm_request_time()));
  if ($has_metadata_key) {
    $select->addExpression(':revision_default', 'revision_default', array(':revision_default' => $is_default_revision));
  }
  $select->condition('t.mmtid', $mmtid);
  $vid = $db->insert('mm_tree_revision')
    ->from($select)
    ->execute();
  if ($vid) {
    $db->update('mm_tree')
      ->fields(array('vid' => $vid))
      ->condition('mmtid', $mmtid)
      ->execute();
    mm_content_invalidate_mm_tree_cache($mmtid);
  }
}

/**
 * Invalidate Drupal's cache for anything tagged with mm_tree:mmtid.
 *
 * @param int|MMTree $mm_tree_or_mmtid
 *   MMTree entity (preferred) or MMTID
 */
function mm_content_invalidate_mm_tree_cache($mm_tree_or_mmtid) {
  if (!is_object($mm_tree_or_mmtid)) {
    $mm_tree_or_mmtid = MMTree::load($mm_tree_or_mmtid);
    if (!$mm_tree_or_mmtid) {
      return;
    }
  }
  Cache::invalidateTags($mm_tree_or_mmtid->getCacheTagsToInvalidate());
}

/**
 * Based on an entry's permissions, return appropriate default values for 'who
 * can edit or delete this content' on nodes added to it.
 *
 * @param int $mmtid
 *   The tree ID of the entry nodes are being added to
 * @param array $grouplist
 *   On return, contains an array of long group names, indexed by the mmtid of
 *   the group
 * @param array $userlist
 *   On return, contains an array of long user names, indexed by uid
 * @param int $max
 *   The maximum number of users to copy in ad-hoc groups
 */
function mm_content_get_default_node_perms($mmtid, &$grouplist, &$userlist, $max) {
  // Find groups
  $grouplist = array();
  $db = Database::getConnection();
  $select = $db->select('mm_tree', 't');
  $select->join('mm_tree_access', 'a', 'a.mmtid = t.mmtid');
  $select->leftJoin('mm_tree', 't2', 'a.gid = t2.mmtid');
  $or = new Condition('OR');
  $select->fields('t2', array('mmtid', 'name'))
    ->distinct()
    ->condition('t2.mmtid', 0, '>=')
    ->condition($or
      ->condition('a.mode', Constants::MM_PERMS_APPLY)
      ->condition('a.mode', Constants::MM_PERMS_WRITE)
    )
    ->condition('a.mmtid', $mmtid)
    ->orderBy('t2.name');
  $result = $select->execute();
  foreach ($result as $r) {
    $grouplist[$r->mmtid] = $r->name;
  }

  // Find individual users
  $userlist = array();
  $or = new Condition('OR');
  $result = $db->select('mm_tree_access', 'a')
    ->fields('a', array('gid'))
    ->distinct()
    ->condition('a.gid', 0, '<')
    ->condition($or
      ->condition('a.mode', Constants::MM_PERMS_APPLY)
      ->condition('a.mode', Constants::MM_PERMS_WRITE)
    )
    ->condition('a.mmtid', $mmtid)
    ->execute();
  foreach ($result as $r) {
    $users = mm_content_get_users_in_group($r->gid, NULL, TRUE, $max);

    if (!is_null($users))
      $userlist += $users;
  }
}

/**
 * Set a group's membership list or virtual group attributes
 *
 * @param int $mmtid
 *   MM tree ID of the group
 * @param int $vgid
 *   Virtual group ID of the group, if known. If this value is NULL and the
 *   group is determined to be a virtual group, its vgid is retrieved
 * @param string $qfield
 *   For a virtual group, the "column to select" portion of the query; ignored
 *   for non-virtual groups
 * @param string $qfrom
 *   For a virtual group, the "FROM clause" portion of the query; ignored for
 *   non-virtual groups
 * @param array|string $members
 *   For non-virtual groups, an array of the uids of the group's members; if an
 *   empty string (''), do not change any existing users
 * @param string $large_group_form_token
 *   If large group management is used, contains the token associated with the
 *   input form.
 * @param Connection $database
 *   (optional) The database connection to use.
 */
function mm_content_set_group_members($mmtid, $vgid, $qfield, $qfrom, $members, $large_group_form_token = '', Connection $database = NULL) {
  $database = $database ?: Database::getConnection();

  $vgroup = mm_content_is_vgroup($mmtid);
  if ($vgroup && empty($vgid)) {
    $select = $database->select('mm_group', 'g');
    $select->join('mm_vgroup_query', 'v', 'g.vgid = v.vgid');
    $select->fields('v', array('vgid'))
      ->condition('g.gid', $mmtid);
    $vgid = $select->execute()->fetchField();
  }

  if ($vgroup && !empty($qfield) && !empty($vgid)) {
    $database->update('mm_vgroup_query')
      ->fields(array(
        'field' => $qfield,
        'qfrom' => $qfrom,
        'dirty' => Constants::MM_VGROUP_DIRTY_NEXT_CRON,
      ))
      ->condition('vgid', $vgid)
      ->execute();
  }
  elseif ($vgroup || is_array($members) || !empty($large_group_form_token)) {
    // DELETE FROM {mm_vgroup_query} WHERE
    //   (SELECT 1 FROM {mm_group} g WHERE g.vgid = {mm_vgroup_query}.vgid
    //     AND g.gid = :mmtid)
    $mm_group = $database->select('mm_group', 'g');
    $mm_group->addExpression(1);
    $mm_group->where('g.vgid = {mm_vgroup_query}.vgid')
      ->condition('g.gid', $mmtid);
    $database->delete('mm_vgroup_query')
      ->condition($mm_group)
      ->execute();
    $database->delete('mm_group')
      ->condition('gid', $mmtid)
      ->execute();

    if (!empty($large_group_form_token)) { // Copy from the temp table and then remove temp records
      $select = $database->select('mm_group_temp', 'g');
      $select->addExpression($mmtid, 'gid');
      $select->addField('g', 'uid');
      $select->addExpression('0', 'vgid');
      $select->condition('sessionid', session_id());
      $select->condition('token', $large_group_form_token);
      $database->insert('mm_group')
        ->from($select)
        ->execute();
      $database->delete('mm_group_temp')
        ->condition('sessionid', session_id())
        ->condition('token', $large_group_form_token)
        ->execute();
    }

    if ($vgroup) {
      if (!empty($qfield)) {
        $vgid = $database->insert('mm_vgroup_query')
          ->fields(array(
            'field' => $qfield,
            'qfrom' => $qfrom,
            'dirty' => Constants::MM_VGROUP_DIRTY_NEXT_CRON,
          ))
          ->execute();
        $database->insert('mm_group')
          ->fields(array(
            'gid' => $mmtid,
            'uid' => 0,
            'vgid' => $vgid,
          ))
          ->execute();
      }
    }
    elseif (is_array($members)) {
      foreach ($members as $uid) {
        $database->insert('mm_group')
          ->fields(array(
            'gid' => $mmtid,
            'uid' => $uid,
            'vgid' => 0,
          ))
          ->execute();
      }
    }
  }
}

/**
 * Given a node, return TRUE if the node is in a recycle bin.
 *
 * @param NodeInterface $node
 *   The node to test
 * @param int|string $mmtid
 *   Either the MM Tree ID of the page (recycle bin) to test against, or one of
 *   these constants:
 *   - MM_NODE_RECYCLED_MMTID_CURR: Recycled on the current page (bin)
 *   - MM_NODE_RECYCLED_MMTID_EXCL: Recycled on all pages where it is shown
 * @return bool
 *   TRUE if the node is in a recycle bin
 */
function mm_content_node_is_recycled(NodeInterface $node, $mmtid = Constants::MM_NODE_RECYCLED_MMTID_EXCL) {
  if (!isset($mmtid) || !isset($node->recycle_date)) {
    return FALSE;
  }

  if ($mmtid == Constants::MM_NODE_RECYCLED_MMTID_CURR) {
    mm_parse_args($term_ids, $oarg_list, $mmtid);
    if (is_null($mmtid)) {
      return FALSE;
    }
  }

  $db = Database::getConnection();
  if ($mmtid == Constants::MM_NODE_RECYCLED_MMTID_EXCL) {
    return (bool) $db->query('SELECT COUNT(*) = 0 FROM {mm_node2tree} n2 LEFT JOIN {mm_recycle} r ON n2.nid = r.id AND `type` = :type AND (r.from_mmtid = n2.mmtid OR r.bin_mmtid = n2.mmtid) WHERE n2.nid = :nid AND r.id IS NULL',
      array(
        ':type' => 'node',
        ':nid' => $node->id(),
      ))->fetchField();
  }

  return (bool) $db->query('SELECT COUNT(*) FROM {mm_recycle} WHERE `type` = :type AND id = :nid AND (bin_mmtid = :mmtid OR from_mmtid = :mmtid)',
    array(
      ':type' => 'node',
      ':nid' => $node->id(),
      ':mmtid' => $mmtid,
    ))->fetchField();
}

/**
 * Given a set of tree IDs, return a list of the node IDs assigned to them.
 *
 * @param int|array $mmtids
 *   An array of tree IDs, or a single ID
 * @param int|string $limit
 *   Optional limit to the number of results
 * @param bool $unique
 *   If set, return only those nodes that are assigned to entries from the list,
 *   and not to other entries.
 * @return array
 *   An array of node IDs
 */
function mm_content_get_nids_by_mmtid($mmtids, $limit = '', $unique = FALSE) {
  if (!is_array($mmtids)) {
    $mmtids = array($mmtids);
  }

  if (count($mmtids) == 1) {
    $in = '= :one';
    $args = array(':one' => $mmtids[0]);
  }
  else {
    $in = 'IN(:list[])';
    $args = array(':list[]' => $mmtids);
  }

  if ($unique) {
    $query = 'SELECT t1.nid FROM {mm_node2tree} t1 ' .
        "INNER JOIN {mm_node2tree} t2 ON t1.nid = t2.nid AND t2.mmtid $in " .
        "GROUP BY t1.nid HAVING SUM(IF(t1.mmtid $in, 1, 0)) = COUNT(*)";
  }
  else {
    $query = "SELECT nid FROM {mm_node2tree} WHERE mmtid $in";
  }

  $db = Database::getConnection();
  if ($limit) {
    return $db->queryRange($query, 0, $limit, $args)->fetchCol();
  }
  return $db->query($query, $args)->fetchCol();
}

/**
 * Given a tree ID, return a SQL query handle for nodes that appear on it. Only
 * nodes the user has permission to view are returned, and they are sorted
 * according to all of the various possible criteria.
 *
 * @param int $mmtid
 *   The tree ID of the entry
 * @param int $per_page
 *   Nodes per page in the pager (optional)
 * @param int $element
 *   Pager element number (optional)
 * @param string $add_select
 *   Text to add to the SELECT part of the outer query (optional)
 * @param string $add_join
 *   Additional LEFT JOINs for the outer query (optional)
 * @param string $add_inner_where
 *   Text to add to the WHERE part of the inner query (optional)
 * @param string $add_outer_where
 *   Text to add to the WHERE part of the outer query (optional)
 * @param string $add_groupby
 *   Text to add to the GROUP BY part of the outer query (optional)
 * @param string $add_orderby
 *   Text to add to the ORDER BY part of the outer query (optional)
 * @return StatementInterface
 *   An SQL query handle
 */
function mm_content_get_accessible_nodes_by_mmtid($mmtid, $per_page = 0, $element = 0, $add_select = '', $add_join = '', $add_inner_where = '', $add_outer_where = '', $add_groupby = '', $add_orderby = '') {
  if ($mmtid < 0) {
    return NULL;
  }
  $q = mm_content_get_accessible_nodes_by_mmtid_query($mmtid, $count_sql, $add_select, $add_join, $add_inner_where, $add_outer_where, $add_groupby, $add_orderby);

  $db = Database::getConnection();
  if ($per_page == 0 && Constants::MM_MAX_NUMBER_OF_NODES_PER_PAGE != 0) {
    return $db->queryRange($q, 0, Constants::MM_MAX_NUMBER_OF_NODES_PER_PAGE);
  }

  if ($per_page > 0) {
    $total = $db->query($count_sql)->fetchField();
    $page = pager_default_initialize($total, $per_page, $element);
    return $db->queryRange($q, $per_page * $page, $per_page);
  }

  if ($per_page == -2) {
    $start_value = \Drupal::request()->query->getInt('page', 0) * Constants::MM_LAZY_LOAD_NUMBER_OF_NODES;
    return $db->queryRange($q, $start_value, Constants::MM_LAZY_LOAD_NUMBER_OF_NODES);
  }

  return $db->query($q);
}

/**
 * Given a tree ID, return a SQL query for nodes that appear on it. Only nodes
 * that are owned by the user or are currently published are returned, and they
 * are sorted according to all of the various possible criteria.
 *
 * @param int $mmtid
 *   The tree ID (or array of tree IDs) of the entry
 * @param string &$count_sql
 *   The SQL query for querying the count of matches
 * @param string $add_select
 *   Text to add to the SELECT part of the outer query (optional)
 * @param string $add_join
 *   Additional LEFT JOINs for the outer query (optional)
 * @param string $add_inner_where
 *   Text to add to the WHERE part of the inner query (optional)
 * @param string $add_outer_where
 *   Text to add to the WHERE part of the outer query (optional)
 * @param string $add_groupby
 *   Text to add to the GROUP BY part of the outer query (optional)
 * @param string $add_orderby
 *   Text to add to the ORDER BY part of the outer query (optional)
 * @return string
 *   The SQL query
 */
function mm_content_get_accessible_nodes_by_mmtid_query($mmtid, &$count_sql, $add_select = '', $add_join = '', $add_inner_where = '', $add_outer_where = '', $add_groupby = '', $add_orderby = '') {
  $mmtid = is_array($mmtid) ? 'IN(' . join(',', $mmtid) . ')' : "= $mmtid";
  // This includes the hack "n.changed AS created" to allow node_feed() to show
  // the changed date instead of the post date. It is called in _mm_render_pages().
  // Woe unto he who attempts to grok this.
  $now = mm_request_time();
  $scheduled = "IFNULL((s.publish_on = 0 OR s.publish_on <= $now) AND (s.unpublish_on = 0 OR $now < s.unpublish_on), 1)";
  $inner =
    'FROM {mm_tree} tr ' .
      'INNER JOIN {mm_node2tree} t ON t.mmtid = tr.mmtid ' .
      'INNER JOIN {node} n ON n.nid = t.nid ' .
      'INNER JOIN {node_field_data} nfd ON nfd.nid = t.nid ' .
      'LEFT JOIN {mm_node_schedule} s ON s.nid = n.nid ' .
      'LEFT JOIN {mm_node_reorder} r ON r.nid = n.nid AND r.mmtid = tr.mmtid ';
  $where = " WHERE tr.mmtid $mmtid";
  // skip some tests for users with 'bypass node access' permission
  if (!\Drupal::currentUser()->hasPermission('bypass node access')) {
    $current_uid = \Drupal::currentUser()->id();
    $node_writable = $current_uid > 0 ? " OR (nfd.uid = $current_uid OR gw.uid = $current_uid OR vw.uid = $current_uid)" : '';
    $inner .=
      'LEFT JOIN {mm_node_write} nw ON nw.nid = n.nid ' .
      'LEFT JOIN {mm_group} gw ON gw.gid = nw.gid ' .
      'LEFT JOIN {mm_virtual_group} vw ON vw.vgid = gw.vgid';
    $where .= " AND (nfd.status = 1 AND $scheduled$node_writable)$add_inner_where ";
  }
  elseif ($add_inner_where) {
    $where .= $add_inner_where;
  }
  $count_inner = $inner . $add_join . $where;
  $inner .= $where;
  if ($add_outer_where) {
    $count_inner .= $add_outer_where;
    $add_outer_where = ' WHERE' . preg_replace('/^\s*(\S+)/', '', $add_outer_where);    // Remove AND, OR, etc.
  }
  $count_sql = 'SELECT COUNT(DISTINCT n.nid) ' . $count_inner;

  return
    'SELECT n.nid, ' .
      'IF(MAX(n.set_change_date) = 1 AND MAX(n.publish_on) > 0, MAX(n.publish_on), MAX(n.changed)) AS created, ' .                                               // creation date
      'MAX(n.sticky) AND (MAX(n.uid) = 1 OR MAX(n.owns_it) OR ' .
        'COUNT(v.uid = n.uid OR g.vgid = 0 AND g.uid = n.uid)) AS stuck, ' . // node is sticky
      "MAX(n.scheduled) AS scheduled, MAX(n.status) AS status, MAX(n.title) AS title$add_select " .
      'FROM ' .
        '(SELECT DISTINCT n.*, nfd.changed, nfd.uid, nfd.sticky, nfd.status, nfd.title, t.mmtid, r.weight, r.region, s.set_change_date, s.publish_on, ' .
          "$scheduled AS scheduled, " .
          '(nfd.uid = tr.uid) AS owns_it ' .                            // entry is owned by node's owner
          $inner .
        ') AS n ' .
      "$add_join " .
      "LEFT JOIN {mm_tree_access} a ON a.mode = '" . Constants::MM_PERMS_WRITE . "' AND a.mmtid = n.mmtid " .
      'LEFT JOIN {mm_group} g ON a.gid = g.gid ' .
      'LEFT JOIN {mm_virtual_group} v ON g.vgid = v.vgid ' .
        'AND (v.uid = n.uid OR g.vgid = 0 AND g.uid = n.uid) ' .
      $add_outer_where .
      "GROUP BY n.nid$add_groupby " .
      "ORDER BY stuck DESC, MAX(n.weight), created DESC, title$add_orderby";
}

/**
 * Return whether or not the user has some type of access on a given Drupal
 * node. This takes into account both the permissions on the node itself (for
 * writing) and the permissions of the tree entry it's in (for everything else.)
 *
 * @param NodeInterface $node
 *   The node object on which the operation is to be performed.
 * @param string $op
 *   The operation to be performed on the node. Possible values are:
 *   - "view"
 *   - "update"
 *   - "delete"
 * @param AccountInterface $account
 *   The user object on which the operation is to be performed. (optional)
 * @return bool
 *   TRUE if the operation may be performed.
 */
function mm_content_node_access(NodeInterface $node, $op, AccountInterface $account = NULL) {
  static $recursive;

  switch ($op) {
    case 'view':
      foreach (mm_module_implements('mm_node_access') as $module) {
        $out = call_user_func_array($module . '_mm_node_access', array($op, $node, $account));
        if (isset($out)) {
          return $out;
        }
      }

      return mm_content_user_can_node($node, Constants::MM_PERMS_READ, $account);

    case 'update':
    case 'delete':
      if (!$account) {
        $account = \Drupal::currentUser();
      }

      if (!$account->isAuthenticated()) {
        return FALSE;
      }

      if ($account->id() == 1) {
        // admin user
        return TRUE;
      }

      if ($account->hasPermission('bypass node access')) {
        return TRUE;
      }

      foreach (mm_module_implements('mm_node_access') as $module) {
        $out = call_user_func_array($module . '_mm_node_access', array($op, $node, $account));
        if (isset($out)) {
          return $out;
        }
      }

      if ($node->getOwnerId() === $account->id()) {
        // regular owner
        return TRUE;
      }

      // being called recursively from $test_node->access() call, below
      if ($recursive) {
        return;
      }

      if (mm_content_user_can_update_node($node, $account)) {
        $test_node = isset($node->type) ? clone($node) : Node::load($node->id());
        $test_node->setOwnerId($account->id());
        $recursive = TRUE;
        $ret = $test_node->access($op, $account);
        $recursive = FALSE;
        return $ret;
      }
  }
  return FALSE;
}

/**
 * Return whether or not the user can update (write to) a node, based solely on
 * the MM permissions of that node. Page-level permissions are not considered.
 *
 * @param NodeInterface $node
 *   The node object being queried.
 * @param AccountInterface $account
 *   The user to test. (optional)
 * @return bool
 *   TRUE if the user can update.
 */
function mm_content_user_can_update_node(NodeInterface $node, AccountInterface $account = NULL) {
  if (!$account) {
    $account = \Drupal::currentUser();
  }

  $select = Database::getConnection()->select('mm_node_write', 'nw');
  $select->leftJoin('mm_group', 'g', 'nw.gid = g.gid');
  $select->leftJoin('mm_virtual_group', 'v', 'v.vgid = g.vgid');
  $select->fields('nw', array('gid'))
    ->distinct()
    ->condition('nw.nid', $node->id());

  $or = new Condition('OR');
  // everyone, or this user is in a matching group
  if ($account->isAuthenticated()) {
    $select->condition($or
        ->condition('nw.gid', 0)
        ->condition('g.uid', $account->id())
        ->condition('v.uid', $account->id())
    );
  }
  else {
    $select->condition($or
        ->condition('nw.gid', 0)
        ->condition('g.uid', 0)
        ->condition('v.uid', 0)
    );
  }

  return $select->countQuery()->execute()->fetchField() > 0;
}

/**
 * Flag a virtual group as needing its data regenerated. The actual update of
 * mm_virtual_group happens during monster_menus_cron().
 *
 * @param int $mmtid
 *   Tree/Group ID (not virtual group ID!) of the virtual group to be updated.
 *   Omit or set to NULL to mark all groups for update.
 */
function mm_content_update_vgroup_view($mmtid = NULL) {
  $db = Database::getConnection();
  if (!isset($mmtid)) {
    $db->update('mm_vgroup_query')
      ->fields(array('dirty' => Constants::MM_VGROUP_DIRTY_NEXT_CRON))
      ->condition('dirty', Constants::MM_VGROUP_DIRTY_NOT)
      ->execute();
  }
  else {
    // UPDATE {mm_vgroup_query} q INNER JOIN {mm_group} g ON g.vgid = q.vgid
    // SET q.dirty = <MM_VGROUP_DIRTY_NEXT_CRON>
    // WHERE g.gid = $mmtid AND dirty = <MM_VGROUP_DIRTY_NOT>
    $group = $db->select('mm_group', 'g');
    $group->condition('g.gid', $mmtid)
      ->where('g.vgid = {mm_vgroup_query}.vgid');
    $group
      ->addExpression('COUNT(*)');
    $db->update('mm_vgroup_query')
      ->condition($group, '0', '<>')
      ->condition('dirty', Constants::MM_VGROUP_DIRTY_NOT)
      ->fields(array('dirty' => Constants::MM_VGROUP_DIRTY_NEXT_CRON))
      ->execute();
  }
}

/**
 * Reset the sort order of nodes on a page without affecting their region
 * assignments.
 *
 * @param int $mmtid
 *   The Tree ID of the containing page.
 * @param int $nid
 *   Optional ID of a particular node to alter. If unset, all nodes are altered.
 */
function mm_content_reset_custom_node_order($mmtid, $nid = NULL) {
  // Remove entries in the "content" region.
  $db = Database::getConnection();
  $q = $db->delete('mm_node_reorder')
    ->condition('mmtid', $mmtid)
    ->condition('region', NULL);
  if (isset($nid)) {
    $q->condition('nid', $nid);
  }
  $q->execute();

  // Reset order to 0 in all other places.
  $q = $db->update('mm_node_reorder')
    ->condition('mmtid', $mmtid)
    ->fields(array('weight' => 0));
  if (isset($nid)) {
    $q->condition('nid', $nid);
  }
  $q->execute();
}

function _mm_content_active_regions() {
  $out = array();
  foreach (\Drupal::service('theme_handler')->listInfo() as $data) {
    if ($data->status) {
      foreach (array_keys($data->info['regions']) as $region) {
        if (!isset($data->info['regions_hidden']) || !in_array($region, $data->info['regions_hidden'])) {
          $out[] = $region;
        }
      }
    }
  }
  return array_unique($out);
}

/**
 * Get a list of allowed content types in one or all regions.
 *
 * @param string $region
 *   If set, return just the types for this one region. Otherwise, return an
 *   array indexed by region.
 * @return array
 *   An array, as described above.
 */
function mm_content_get_perms_for_region($region = NULL) {
  if (is_null($region)) {
    $out = array();
    foreach (_mm_content_active_regions() as $region) {
      $out[$region] = mm_content_get_perms_for_region($region);
    }
    return $out;
  }

  $list = mm_get_setting('nodes.allowed_region_perms');
  if (isset($list['users'][$region])) {
    return [
      'everyone' => $list['everyone'][$region],
      'users' => $list['users'][$region],
      'groups' => $list['groups'][$region],
    ];
  }
  return array('everyone' => $region == Constants::MM_UI_REGION_CONTENT, 'users' => array(), 'groups' => array());
}

/**
 * Get a list of allowed content types in one or all regions.
 *
 * @param string $region
 *   If set, return just the types for this one region. Otherwise, return an
 *   array indexed by region.
 * @return array|string
 *   An array, as described above.
 */
function mm_content_get_allowed_types_for_region($region = NULL) {
  if (is_null($region)) {
    $out = array();
    foreach (_mm_content_active_regions() as $region) {
      $out[$region] = mm_content_get_allowed_types_for_region($region);
    }
    return $out;
  }

  $types = mm_get_setting('nodes.allowed_region_node_types');
  if (isset($types[$region])) {
    return $types[$region];
  }
  return 'all';
}

/**
 * Get a list of regions into which a particular user can place nodes.
 *
 * @param AccountInterface $account
 *   The user object to test, or NULL to use the current user.
 * @param string $type
 *   If set, the list is further limited to only those regions that are allowed
 *   for a specific content type.
 * @return array
 *   An array containing the names of all allowed regions.
 */
function mm_content_get_allowed_regions_for_user(AccountInterface $account = NULL, $type = NULL) {
  static $allowed = array();

  if (!isset($account)) {
    $account = \Drupal::currentUser();
  }
  $uid = $account->id();
  $bypass = $uid == 1 || $account->hasPermission('administer all menus');
  if (!isset($allowed[$uid])) {
    foreach (_mm_content_active_regions() as $region) {
      $ok = FALSE;
      if ($uid) {
        if ($bypass) {
          $ok = TRUE;
        }
        else {
          $perms = mm_content_get_perms_for_region($region);
          if (!empty($perms['everyone']) || isset($perms['users']) && in_array($uid, $perms['users'])) {
            $ok = TRUE;
          }
          else {
            if (!isset($user_groups)) {
              $user_groups = mm_content_get_uids_in_group(NULL, $uid, TRUE, TRUE, FALSE);
            }
            if (isset($perms['groups']) && array_intersect($perms['groups'], $user_groups)) {
              $ok = TRUE;
            }
          }
        }
      }

      if ($ok) {
        $allowed[$uid][] = $region;
      }
    }
    // Allow third-party modules to alter the allowed types for this user.
    \Drupal::moduleHandler()->alter('mm_allowed_regions_for_user', $allowed[$uid], $account, $type);
  }

  if ($type && !$bypass) {
    $list = $allowed[$uid];
    foreach ($list as $i => $region) {
      $types_for_region = mm_content_get_allowed_types_for_region($region);
      if ($types_for_region !== 'all' && !in_array($type, $types_for_region)) {
        unset($list[$i]);
      }
    }
    return $list;
  }

  return $allowed[$uid];
}

/**
 * Figure out if a given node is assigned to one or more hidden tree entries
 *
 * @param NodeInterface|int $nid
 *   The node object or node number being queried
 * @param bool $all_pages
 *   If TRUE, return TRUE when all pages on which the node appears are hidden.
 *   Otherwise, return TRUE if any page is hidden.
 * @return bool
 *   TRUE if the node is assigned to one or more hidden tree entries
 */
function mm_content_is_hidden_node($nid, $all_pages = TRUE) {
  if (is_object($nid)) {
    $nid = $nid->id();
  }

  $db = Database::getConnection();
  if ($all_pages) {
    $inner = $db->select('mm_node2tree', 'n');
    $inner->leftJoin('mm_tree_parents', 'p', 'p.mmtid = n.mmtid');
    $inner->leftJoin('mm_tree', 't', 't.mmtid = p.parent OR t.mmtid = n.mmtid');
    $inner->addExpression('SUM(t.hidden) > 0', 'hidden');
    $inner->condition('n.nid', $nid);
    $inner->groupBy('p.mmtid');
    $select = $db->select($inner, 'x');
    $select->addExpression('COUNT(*) = SUM(x.hidden)', 'is_hidden');
    return $select->execute()->fetchField() > 0;
  }

  $select = $db->select('mm_node2tree', 'n');
  $select->leftJoin('mm_tree_parents', 'p', 'p.mmtid = n.mmtid');
  $select->leftJoin('mm_tree', 't', 't.mmtid = p.parent OR t.mmtid = n.mmtid');
  return $select->condition('n.nid', $nid)
    ->condition('t.hidden', 1)
    ->countQuery()->execute()->fetchField() > 0;
}

/**
 * Delete a recycling bin tree node, if it's empty
 *
 * @param int $bin
 *   Tree ID of the bin to possibly delete
 * @return string|bool
 *   An error message, if an error occurs; otherwise, TRUE if the bin was
 *   deleted.
 */
function mm_content_delete_bin($bin) {
  if (($tree = mm_content_get($bin)) && $tree->name == Constants::MM_ENTRY_NAME_RECYCLE) {
    // It's definitely a bin.
    $db = Database::getConnection();
    if (!$db->query('SELECT COUNT(*) FROM {mm_node2tree} WHERE mmtid = :mmtid', array(':mmtid' => $bin))->fetchField()) {
      // No nodes in the bin itself.
      if ($db->query('SELECT COUNT(*) FROM {mm_tree} WHERE parent = :mmtid', array(':mmtid' => $bin))->fetchField() == 0) {
        // It's empty.
        $err = mm_content_delete($bin, FALSE);
        return $err ? $err : TRUE;
      }
    }
  }
  return FALSE;
}

/**
 * Move content to the recycle bin, creating the bin if needed
 *
 * @param int|array $mmtids
 *   Array, or a single tree ID to move
 * @param int|array $nids
 *   Array, or a single node ID to move. Either an ordered array or an
 *   associative array can be used. If an ordered array is used, then it
 *   contains a list of node IDs which will be moved into a recycle bin for each
 *   page to which they are assigned. If an associative array is used, then each
 *   key must be the node ID and each value must be an array of Tree IDs from
 *   which the node should be removed.
 * @return string|int|void
 *   Either an error message or the MM Tree ID of the bin
 */
function mm_content_move_to_bin($mmtids = NULL, $nids = NULL) {
  $bin = NULL;
  $db = Database::getConnection();

  if (isset($mmtids)) {    // recycle one or more entries
    if (!is_array($mmtids)) {
      $mmtids = array($mmtids);
    }

    $txn = $db->startTransaction();
    foreach ($mmtids as $mmtid) {
      $bin_parent = mm_content_get_parent($mmtid);
      if (!$bin_parent) {
        return;
      }

      $bin = _mm_content_make_recycle($bin_parent);
      if (!is_numeric($bin)) {
        return $bin;
      }
      $bin = intval($bin);

      if (!($tree = mm_content_get($mmtid))) {
        mm_content_delete_bin($bin);
        return 'error';
      }

      $n = 0;
      $name = $tree->name;
      $alias = $tree->alias;

      while ($db->query("SELECT COUNT(*) FROM {mm_tree} WHERE (name = :name OR alias <> '' AND alias = :alias) AND parent = :parent", array(':name' => $name, ':alias' => $alias, ':parent' => $bin))->fetchField()) {
        $n++;
        $name = $tree->name . " ($n)";
        $alias = empty($tree->alias) ? '' : $tree->alias . "-$n";
      }
      if ($n) {
        mm_content_update_quick(array('name' => $name, 'alias' => $alias), array('mmtid' => $mmtid), $tree->parent);
      }

      // Make sure the sort index is up to date, then force a re-read of the
      // source tree entry.
      mm_content_update_sort_queue();
      mm_content_clear_caches($mmtid);

      $err = mm_content_move($mmtid, $bin, 'recycle');
      if ($err) {
        mm_content_delete_bin($bin);
        return $err;
      }

      $db->insert('mm_recycle')
        ->fields(array(
          'type' => 'cat',
          'id' => $mmtid,
          'bin_mmtid' => $bin,
          'recycle_date' => mm_request_time(),
        ))
        ->execute();
    }
  }
  elseif (isset($nids)) {    // recycle one or more nodes
    if (!is_array($nids)) {
      $nids = array($nids);
    }
    else if (!$nids) {
      return;
    }

    // Convert to an associative array, if needed.
    $assoc = array();
    $nid_list = isset($nids[0]) ? $nids : array_keys($nids);
    foreach ($nid_list as $nid) {
      $from_mmtids = mm_content_get_by_nid($nid);
      // If the node is not on any page, create the bin at the root.
      if (!$from_mmtids) {
        $from_mmtids = array(0);
      }
      else {
        // Exclude recycle bins
        foreach ($from_mmtids as $key => $from_mmtid) {
          if (mm_content_is_recycle_bin($from_mmtid)) {
            unset($from_mmtids[$key]);
          }
        }
      }
      // If it's already associative, make sure the supplied mmtid list is
      // valid.
      if (!isset($nids[0])) {
        $from_mmtids = array_intersect($from_mmtids, $nids[$nid]);
      }
      // Convert to associative nid => [array of mmtids] for each nid
      if ($from_mmtids) {
        $assoc[$nid] = $from_mmtids;
      }
    }

    $txn = $db->startTransaction();
    foreach ($assoc as $nid => $from_mmtids) {
      foreach ($from_mmtids as $mmtid) {
        $bin = _mm_content_make_recycle($mmtid);
        if (!is_numeric($bin)) {
          return $bin;
        }
        $bin = intval($bin);

        // Remove from old page.
        $db->delete('mm_node2tree')
          ->condition('nid', $nid)
          ->condition('mmtid', $mmtid)
          ->execute();
        // Clear any custom reordering.
        $db->delete('mm_node_reorder')
          ->condition('nid', $nid)
          ->condition('mmtid', $mmtid)
          ->execute();
        // Store recovery state in mm_recycle.
        $db->merge('mm_recycle')
          ->keys(array(
            'type' => 'node',
            'id' => $nid,
            'from_mmtid' => $mmtid))
          ->fields(array(
            'bin_mmtid' => $bin,
            'recycle_date' => mm_request_time()))
          ->execute();
        // Add to bin.
        $db->insert('mm_node2tree')
          ->fields(array(
            'nid' => $nid,
            'mmtid' => $bin))
          ->execute();
        \Drupal::logger('mm')->notice('Recycled node=@nid from mmtid=@mmtid to bin=@bin', array('@nid' => $nid, '@mmtid' => $mmtid, '@bin' => $bin));
      }
    }
    // Clear the cache used by mm_content_get_by_nid.
    mm_content_get_by_nid(NULL, TRUE);
    mm_content_clear_node_cache($nid_list);
    // Commit.
    unset($txn);
  }

  return $bin;
}

/**
 * Move content out of the recycle bin
 *
 * @param array|int $mmtids
 *   Array, or a single tree ID to move out
 * @param array|NodeInterface $nodes
 *   Array, or a single node object to move out.
 * @param int $node_bin_mmtid
 *   If supplied, the nodes are restored from only this bin. Otherwise, they are
 *   restored from all bins at once; this is usually not the desired result.
 * @param bool $use_watchdog
 *   If TRUE, log a message to the watchdog upon successful restoration.
 * @return string|void
 *   An error message, if an error occurred
 */
function mm_content_move_from_bin($mmtids, $nodes = NULL, $node_bin_mmtid = NULL, $use_watchdog = TRUE) {
  $db = Database::getConnection();
  if (isset($mmtids)) {
    if (!is_array($mmtids)) {
      $mmtids = array($mmtids);
    }

    $txn = $db->startTransaction();
    foreach ($mmtids as $mmtid) {
      $bin_parent = mm_content_get_parent($bin = mm_content_get_parent($mmtid));
      $error = mm_content_move($mmtid, $bin_parent, 'restore');

      if (is_string($error)) {
        return $error;
      }

      $db->delete('mm_recycle')
        ->condition('type', 'cat')
        ->condition('id', $mmtid)
        ->execute();
      $error = mm_content_delete_bin($bin);
      if (is_string($error)) {
        return $error;
      }
    }
  }
  else if (isset($nodes)) {
    if (!is_array($nodes)) {
      $nodes = array($nodes);
    }

    $txn = $db->startTransaction();
    $clear_nids = $clear_mmtids = [];
    foreach ($nodes as $node) {
      $nid = $clear_nids[] = $node->id();
      if (!empty($node_bin_mmtid)) {
        $bins = array($node_bin_mmtid);
        $camefrom = $db->query("SELECT from_mmtid FROM {mm_recycle} WHERE type = 'node' AND id = :nid AND bin_mmtid = :bin_mmtid",
            array(':nid' => $nid, ':bin_mmtid' => $node_bin_mmtid))->fetchCol();
      }
      else {
        $bins = $node->recycle_bins;
        $camefrom = $node->recycle_from_mmtids;
      }
      $db->delete('mm_recycle')
        ->condition('type', 'node')
        ->condition('id', $nid)
        ->condition('bin_mmtid', $bins, 'IN')
        ->execute();
      foreach ($camefrom as $mmtid) {
        if ($mmtid) {
          $db->insert('mm_node2tree')
            ->fields(array('nid' => $nid, 'mmtid' => $mmtid))
            ->execute();
          if ($use_watchdog) {
            \Drupal::logger('mm')->notice('Restored node=@nid to @mmtid', array('@nid' => $nid, '@mmtid' => $mmtid));
          }
        }
      }

      foreach ($bins as $bin) {
        $db->delete('mm_node2tree')
          ->condition('nid', $nid)
          ->condition('mmtid', $bin)
          ->execute();
        $err = mm_content_delete_bin($bin);
        if (is_string($err)) {
          return $err;
        }
      }

      $clear_mmtids = array_merge($clear_mmtids, $camefrom);
    }

    mm_content_clear_node_cache($clear_nids);
    mm_content_clear_page_cache($clear_mmtids);

    // Clear the cache used by mm_content_get_by_nid.
    mm_content_get_by_nid(NULL, TRUE);
  }
}

/**
 * Remove references to a node from a page. If the page is a recycle bin, it is
 * deleted if it becomes empty.
 *
 * Note that this function does not ensure that the removed nodes will still
 * appear on a page somewhere after the operation is complete. This is the
 * responsibility of the caller.
 *
 * @param array|int $nids
 *   Array, or a single node ID to remove
 * @param array|int $mmtids
 *   Array, or a single tree ID from which the node(s) should be removed
 * @return string|void
 *   An error message, if there is an error
 */
function mm_content_remove_node_from_page($nids, $mmtids) {
  if (!is_array($nids)) {
    $nids = array($nids);
  }
  if (!is_array($mmtids)) {
    $mmtids = array($mmtids);
  }

  $db = Database::getConnection();
  $txn = $db->startTransaction();
  foreach ($mmtids as $mmtid) {
    $db->delete('mm_node2tree')
      ->condition('nid', $nids, 'IN')
      ->condition('mmtid', $mmtid)
      ->execute();
    if (mm_content_is_recycle_bin($mmtid)) {
      $bin = $mmtid;
    }
    else if (mm_content_is_recycled($mmtid)) {
      foreach (array_reverse(mm_content_get_parents($mmtid)) as $bin) {
        if (mm_content_is_recycle_bin($bin)) {
          break;
        }
      }
    }

    if (isset($bin)) {
      $db->delete('mm_recycle')
        ->condition('type', 'node')
        ->condition('id', $nids, 'IN')
        ->condition('bin_mmtid', $bin)
        ->execute();
      if ($bin == $mmtid) {
        $err = mm_content_delete_bin($mmtid);
        if (is_string($err)) {
          return $err;
        }
      }
    }
  }

  // Clear the cache used by mm_content_get_by_nid.
  mm_content_get_by_nid(NULL, TRUE);
}

function mm_content_recycle_enabled() {
  return mm_get_setting('recycle_auto_empty') >= 0;
}

function mm_content_is_node_content_block($mmtid) {
  static $blocks_with_node_contents;

  if (!isset($blocks_with_node_contents)) {
    $blocks_with_node_contents = array_keys(mm_get_mm_blocks(['settings.show_node_contents' => 1]));
  }
  if (!$blocks_with_node_contents) {
    return FALSE;
  }
  $select = Database::getConnection()->select('mm_tree_block', 'tb');
  $select->condition('tb.mmtid', $mmtid);
  $select->condition('tb.bid', $blocks_with_node_contents, 'IN');
  return $select->countQuery()->execute()->fetchField();
}

/**
 * Implementation of hook_views_pre_render()
 *
 * Prevent users from seeing results in views they should not be able to. This
 * is imperfect because it removes data without considering pagination, so
 * result sets have an uneven number of results per page. It also does not
 * consider appearance schedule, so nodes may appear when they should not. This
 * code should be considered mostly as a failsafe, to prevent unwanted data
 * disclosure based on permission.
 *
 * @inheritdoc
 */
function monster_menus_views_pre_render(ViewExecutable $view) {
  $cache = array();
  foreach ($view->result as $index => $result) {
    if (!empty($result->nid)) {
      if (!isset($cache[$result->nid])) {
        $cache[$result->nid] = mm_content_user_can_node($result->_entity, Constants::MM_PERMS_READ);
      }
      if (!$cache[$result->nid]) {
        unset($view->result[$index]);
      }
    }
  }
}

/**
 * Implements hook_mm_tree_flags()
 */
function monster_menus_mm_tree_flags() {
  return array(
    'limit_alias' => array('#type' => 'checkbox', '#description' => t('Prevents non-admin users from changing the item\'s alias')),
    'limit_move' => array('#type' => 'checkbox', '#description' => t('Prevents non-admin users from moving the item')),
    'limit_delete' => array('#type' => 'checkbox', '#description' => t('Prevents non-admin users from deleting the item')),
    'limit_hidden' => array('#type' => 'checkbox', '#description' => t('Prevents non-admin users from changing "Don\'t show this page in the menu"')),
    'limit_location' => array('#type' => 'checkbox', '#description' => t('Prevents non-admin users from changing the item\'s location on screen')),
    'limit_name' => array('#type' => 'checkbox', '#description' => t('Prevents non-admin users from changing the item\'s name')),
    'limit_write' => array('#type' => 'checkbox', '#description' => t('Prevents non-admin users from changing "Delete or change settings"')),
    'no_breadcrumb' => array('#type' => 'checkbox', '#description' => t('Prevents the page breadcrumb from showing at this level'), '#flag_inherit' => TRUE),
    'no_index' => array('#type' => 'checkbox', '#description' => t('Adds a meta tag asking crawlers to not index the page'), '#flag_inherit' => TRUE),
    'user_home' => array('#type' => 'textfield', '#description' => t('If a user homepage, contains the uid'), '#flag_copy' => FALSE),
  );
}

/**
 * Insert or update an entry's flags.
 *
 * @param int $mmtid
 *   The MM tree ID of the entry to change
 * @param string|array $flags
 *   A single string or array of strings in flag => value format to be added to
 *   the entry
 * @param bool $clear_old
 *   If TRUE, remove any existing flags. Otherwise, add the new flags to the
 *   existing set. If the calling function has only just created the entry, use
 *   FALSE to avoid the overhead of trying to delete flags that aren't even set.
 * @param Connection $database
 *   (optional) The database connection to use.
 */
function mm_content_set_flags($mmtid, $flags, $clear_old = TRUE, Connection $database = NULL) {
  $database = $database ?: Database::getConnection();
  if ($clear_old) {
    $database->delete('mm_tree_flags')
      ->condition('mmtid', $mmtid)
      ->execute();
    mm_content_notify_change('clear_flags', $mmtid, NULL, $flags);
  }

  if (!empty($flags)) {
    if (!is_array($flags)) {
      $flags = array($flags => '');
    }

    foreach ($flags as $flag => $data) {
      $database->insert('mm_tree_flags')
        ->fields(array(
          'mmtid' => $mmtid,
          'flag' => $flag,
          'data' => $data,
        ))
        ->execute();
    }

    mm_content_notify_change('insert_flags', $mmtid, NULL, $flags);
  }
}

/**
 * Get the permissions of an item in the MM tree. NOTE: The data returned by
 * this function should never be displayed directly in the UI, since there can
 * be reasons for it to be hidden from the user. The function
 * mm_content_get_users_in_group() takes this into account.
 *
 * @param int $mmtid
 *   The MM tree ID of the entry to query
 * @param bool $users
 *   Return the permissions of individual users
 * @param bool $groups
 *   Return the permission groups
 * @param bool $group_names
 *   When returning groups, set the key to the gid, and the value to the name of
 *   the group. This option should be FALSE (default) if the data is to be
 *   passed to mm_content_set_perms() or mm_content_insert_or_update().
 * @param Connection $database
 *   (optional) The database connection to use.
 * @return array
 *   An array of arrays [MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB,
 *   MM_PERMS_APPLY]['groups', 'users']. All are optional. For 'groups', an
 *   array of gids mapped to their names is returned; for 'users' an array of
 *   uids is returned.
 */
function mm_content_get_perms($mmtid, $users = TRUE, $groups = TRUE, $group_names = FALSE, Connection $database = NULL) {
  $empty = array('groups' => array(), 'users' => array());
  $out = array(
    Constants::MM_PERMS_WRITE => $empty,
    Constants::MM_PERMS_SUB   => $empty,
    Constants::MM_PERMS_APPLY => $empty,
    Constants::MM_PERMS_READ  => $empty,
  );
  $database = $database ?: Database::getConnection();

  if ($users) {
    $select = $database->query(
      'SELECT g.uid, a.mode FROM {mm_tree} t ' .
        'INNER JOIN {mm_tree_access} a ON a.mmtid = t.mmtid ' .
        'INNER JOIN {mm_group} g ON g.gid = a.gid ' .
      'WHERE a.mmtid = :mmtid AND a.gid < 0',
      array(':mmtid' => $mmtid));
    foreach ($select as $row) {
      $out[$row->mode]['users'][] = $row->uid;
    }
  }

  if ($groups) {
    $select = $database->query(
      'SELECT a.gid, t2.name, GROUP_CONCAT(a.mode) AS modes FROM {mm_tree} t ' .
        'INNER JOIN {mm_tree_access} a ON a.mmtid = t.mmtid ' .
        'LEFT JOIN {mm_tree} t2 ON a.gid = t2.mmtid ' .
      'WHERE a.mmtid = :mmtid AND a.gid >= 0 ' .
      'GROUP BY a.gid, t2.name ' .
      'ORDER BY t2.name',
      array(':mmtid' => $mmtid));
    foreach ($select as $row) {
      foreach (explode(',', $row->modes) as $mode) {
        if ($group_names) {
          $out[$mode]['groups'][$row->gid] = $row->name;
        }
        else {
          $out[$mode]['groups'][] = $row->gid;
        }
      }
    }
  }

  return $out;
}

/**
 * Set the permissions of an item in the MM tree
 *
 * @param int $mmtid
 *   The MM tree ID of the entry to change
 * @param array $perms
 *   An array of arrays [MM_PERMS_READ, MM_PERMS_WRITE, MM_PERMS_SUB,
 *   MM_PERMS_APPLY]['groups', 'users']. All are optional. For 'groups', provide
 *   an array of gids; for 'users' an array of uids.
 * @param bool $is_group
 *   TRUE if the item is a group
 * @param bool $clear_old
 *   If TRUE, remove any existing permissions. If the calling function has only
 *   just created the entry, use FALSE to avoid the overhead of trying to delete
 *   permissions that aren't even set.
 * @param Connection $database
 *   (optional) The database connection to use.
 * @throws \Exception
 *   Any exception occurring during the update
*/
function mm_content_set_perms($mmtid, $perms, $is_group = FALSE, $clear_old = TRUE, Connection $database = NULL) {
  foreach (array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ) as $m) {
    if ($is_group && $m == Constants::MM_PERMS_APPLY) {
      continue;
    }

    if (isset($perms[$m]) && isset($perms[$m]['groups'])) {
      foreach ($perms[$m]['groups'] as $gid) {
        if (empty($gid)) {
          \Drupal::logger('mm')->error('Empty gid found when setting permissions for mmtid=@mmtid', array('@mmtid' => $mmtid));
          \Drupal::messenger()->addError(t('Permissions for this page could not be set, due to an error. Please check for groups that seem to have no name and try again.'));
          if (function_exists('Adebug')) Adebug();
          return;
        }
      }
    }
  }

  mm_module_invoke_all('mm_content_set_perms', $mmtid, $perms, $is_group, $clear_old);

  $database = $database ?: Database::getConnection();
  $use_db_query = $database->databaseType() == 'mysql';
  $txn = $database->startTransaction();
  try {
    if ($clear_old) {
      if (!$is_group) {
        _mm_content_clear_access_cache($mmtid);
      }

      // Remove ad-hoc groups (gid<0) first.
      // It's far faster to use db_query(), since DBTNG doesn't allow JOIN.
      if ($use_db_query) {
        $database->query('DELETE g FROM {mm_group} g INNER JOIN {mm_tree_access} a ON a.gid = g.gid WHERE a.mmtid = :mmtid AND a.gid < 0', array(':mmtid' => $mmtid));
      }
      else {
        // DELETE FROM {mm_group} WHERE
        //   (SELECT 1 FROM {mm_tree_access} a WHERE a.gid = {mm_group}.gid
        //     AND a.mmtid = :mmtid AND a.gid < 0)
        $adhoc = $database->select('mm_tree_access', 'a');
        $adhoc->addExpression(1);
        $adhoc->where('a.gid = {mm_group}.gid')
          ->condition('a.mmtid', $mmtid)
          ->condition('a.gid', 0, '<');
        mm_retry_query($database->delete('mm_group')
          ->condition($adhoc));
      }

      // Remove everything from mm_tree_access with this mmtid
      mm_retry_query($database->delete('mm_tree_access')
        ->condition('mmtid', $mmtid));
    }

    foreach (array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ) as $m) {
      if ($is_group && $m == Constants::MM_PERMS_APPLY) {
        continue;
      }

      if (isset($perms[$m]) && isset($perms[$m]['groups'])) {
        foreach ($perms[$m]['groups'] as $gid) {
          if ($gid === 'self') {
            $gid = $mmtid;
          }
          mm_retry_query($database->insert('mm_tree_access')
            ->fields(array(
              'mmtid' => $mmtid,
              'gid' => $gid,
              'mode' => $m,
            )));
        }
      }

      $gid = '';
      if (isset($perms[$m]) && isset($perms[$m]['users'])) {
        foreach ($perms[$m]['users'] as $uid) {
          _mm_content_ad_hoc_group($gid, $uid);
        }
      }

      if ($gid !== '') {
        mm_retry_query($database->insert('mm_tree_access')
          ->fields(array(
            'mmtid' => $mmtid,
            'gid' => $gid,
            'mode' => $m,
          )));
      }
    }
  }
  catch (\Exception $e) {
    $txn->rollback();
    throw $e;
  }
}

/**
 * Calculate the likely deletion time of an item in the recycle bin, based on
 * various settings.
 *
 * @param int $when
 *   The time when the item was placed into the recycle bin, or 0 to calculate
 *   based on $nid or $mmtids
 * @param int $nid
 *   (optional) Node ID of the item to be tested
 * @param int|array $mmtids
 *   (optional) An MM Tree ID, or array of IDs, to be tested
 * @param string $what
 *   Translated string to become part of the result
 * @return string
 *   A text string describing when the content will be removed
 */
function mm_content_get_recycle_autodel_time($when, $nid, $mmtids, $what) {
  $state = \Drupal::state();
  $run_last = $state->get('monster_menus.cron_run_last', 0);
  $run_since = $state->get('monster_menus.cron_run_since', 0);
  $run_count = $state->get('monster_menus.cron_run_count', 0);
  $interval = mm_get_setting('recycle_auto_empty');

  if (!$run_last || !$run_since || !$run_count || $interval <= 0) {
    return '';
  }

  if (!$when) {
    if (!is_array($mmtids)) {
      $mmtids = !$mmtids ? array() : array($mmtids);
    }

    if ($nid) {
      $mmtids = array_merge($mmtids, mm_content_get_by_nid($nid));
    }

    $allparents = array();
    foreach ($mmtids as $t) {
      if (mm_content_user_can($t, Constants::MM_PERMS_IS_RECYCLED)) {
        $allparents = array_merge($allparents, mm_content_get_parents_with_self($t));
      }
    }

    if (!$allparents) {
      return '';
    }

    $select = Database::getConnection()->select('mm_recycle', 'r');
    $select->addExpression('MIN(r.recycle_date)');
    $select->condition('r.type', 'cat')
      ->condition('r.id', $allparents, 'IN')
      ->condition('r.recycle_date', 0, '>');
    $when = $select->execute()->fetchField();
  }
  if (!$when) {
    return '';
  }

  $avg_run = ($run_last - $run_since) / $run_count;
  $fudge = $avg_run / 100;
  $next_run = $run_last + $avg_run;
  if ($when + $interval - $fudge <= $next_run) {
    $which_run = $next_run;
  }
  else {
    $which_run = intval(($when + $interval - $run_since + $avg_run - 1) / $avg_run) * $avg_run + $run_since;
  }

//   debug_add_dump( "run_last=$run_last", "run_since=$run_since", "run_count=$run_count",
//       "interval=$interval", "avg_run=$avg_run", "next_run=$next_run",
//       "time=".REQUEST_TIME, "when=$when", "which_run=$which_run" );
  if (($which_run = $which_run - mm_request_time()) <= 0) {
    $when = t('very soon');
  }
  else {
    $when = t('in @when', array('@when' => \Drupal::service('date.formatter')->formatInterval($which_run)));
  }

  return t('@what will be automatically deleted @when.', array('@what' => $what, '@when' => $when));
}

/**
 * Based on values stored in a node object, set the correct permissions. This
 * function is usually called in hook_nodeapi() during the insert and update
 * phases.
 *
 * @param NodeInterface $node
 *   A node object
 * @throws \Exception
 *   Any exception occurring during the update
 */
function mm_content_set_node_perms(NodeInterface $node) {
  if (!empty($node->mm_skip_perms)) {
    return;
  }

  $everyone = !empty($node->mm_others_w_force) || \Drupal::currentUser()->hasPermission('administer all menus');
  $_mmcucn_cache = &drupal_static('_mmcucn_cache');
  $db = Database::getConnection();
  $txn = $db->startTransaction();
  try {
    $nid = $node->id();
    if (!empty($node->others_w) && $everyone) {
      _mm_ui_delete_node_groups($node, TRUE);

      mm_retry_query($db->insert('mm_node_write')
        ->fields(array('nid' => $nid, 'gid' => 0)));
    }
    else {
      _mm_ui_delete_node_groups($node, $everyone);

      if (isset($node->groups_w) && is_array($node->groups_w)) {
        foreach ($node->groups_w as $gid => $name) {
          if ($gid) {
            mm_retry_query($db->insert('mm_node_write')
              ->fields(array('nid' => $nid, 'gid' => $gid)));
          }
        }
      }

      if (isset($node->users_w) && is_array($node->users_w)) {
        $adhoc_gid = '';
        foreach ($node->users_w as $uid => $name) {
          if (!empty($uid)) {
            _mm_content_ad_hoc_group($adhoc_gid, $uid);
          }
        }

        if ($adhoc_gid != '') {
          mm_retry_query($db->insert('mm_node_write')
            ->fields(array('nid' => $nid, 'gid' => $adhoc_gid)));
        }
      }
    }

    // Remove cached access rights.
    unset($_mmcucn_cache[$nid]);
    mm_content_clear_cache_tagged(['node' => $nid], TRUE);
  }
  catch (\Exception $e) {
    $txn->rollback();
    // Repeat the exception, but with the location below.
    throw new \Exception($e->getMessage());
  }
  unset($txn);    // Commit

  mm_content_notify_change('update_node_perms', NULL, $nid, array($nid => $node));
}

function mm_content_test_showpage_mmtid($mmtid) {
  $path = "mm/$mmtid";
  $options = array();
  mm_module_invoke_all_array('url_outbound_alter', array(&$path, &$options, $path));

  $iter = new \Drupal\monster_menus\GetTreeIterator\ContentTestShowpageIter(explode('/', $path));
  mm_content_get_tree($mmtid, array(Constants::MM_GET_TREE_ITERATOR => $iter));
  return $iter->match;
}

/**
 * Find user homepages that do not seem to have ever been modified and are older
 * than a specified amount. Then, call a user-defined function to do something
 * with them.
 *
 * @param $process_func
 *   A function which will process the homepages that are found. The function
 *   can do things like move them to the recycle bin or just calculate a count.
 *   It is passed one parameter: the tree object of the user page. If the
 *   function returns FALSE all further processing of users will be stopped.
 * @param bool $consider_empty_pages
 *   If TRUE, test the creation/modification times of pages that are empty. If
 *   not, only test pages with associated nodes.
 * @param int $age
 *   The number of seconds from the current time during which a node or page is
 *   considered too new to delete. The default is
 *   MM_UNMODIFIED_HOMEPAGES_MAX_AGE, or 30 days.
 * @param int $threshold
 *   The number of seconds during which a user's pages and their contents must
 *   have been created in order for them to be considered unchanged. This
 *   accounts for the possibility of a homepage taking longer than a second to
 *   initially create. The value must be small enough that it would not be
 *   possible for a user to manually create a homepage and add some content to
 *   it within that time.
 * @return int
 *   A count of the number of homepages that were processed.
 */
function mm_content_find_unmodified_homepages($process_func, $consider_empty_pages = FALSE, $age = Constants::MM_UNMODIFIED_HOMEPAGES_MAX_AGE, $threshold = 30) {
  $iter = new ContentFindUnmodifiedHomepagesIter($process_func, $consider_empty_pages, $age, $threshold);
  mm_content_get_tree(mm_content_users_mmtid(), array(
    Constants::MM_GET_TREE_FILTER_HIDDEN => TRUE,
    Constants::MM_GET_TREE_RETURN_MTIME => TRUE,
    Constants::MM_GET_TREE_VIRTUAL => FALSE,
    Constants::MM_GET_TREE_ITERATOR => $iter,
  ));
  // Process any remaining user.
  $iter->process_user();
  return $iter->count;
}

/**
 * Clear a Drupal cache based on tags.
 *
 * @param array $tags
 *   List of tags to invalidate.
 * @param bool $local
 *   If TRUE, add a prefix to each tag to differentiate it from other tags of
 *   the same name.
 */
function mm_content_clear_cache_tagged(array $tags, $local = FALSE) {
  $chunksize = 100;
  $list = [];
  foreach ($tags as $key => $value) {
    if ($local && $key != 'mm_tree') {
      $key = "_mm_$key";
    }
    if (is_scalar($value)) {
      $list[] = "$key:$value";
    }
    else {
      foreach (array_chunk($value, $chunksize) as $chunk) {
        foreach ($chunk as $value2) {
          $list[] = "$key:$value2";
        }

        if ($list) {
          Cache::invalidateTags($list);
          $list = [];
        }
      }
    }
  }

  if ($list) {
    Cache::invalidateTags($list);
  }
}

/**
 * Clear the menu routing cache.
 *
 * @param array $mmtids
 */
function mm_content_clear_routing_cache_tagged($mmtids = []) {
  // Ideally, we would tag all resolved routes with "mm_tree:MMTID" for the
  // entry and all of its parents, then invalidate based on those tags here.
  // But that would require overriding router.route_provider with a new version
  // of Drupal\Core\Routing::getRouteCollectionForRequest() which adds the
  // correct tags to the route before setting the cache entry. Instead, clear
  // all menu routes.
  Cache::invalidateTags(['route_match']);

  // Notes about what to do here if the above is resolved:
  //  $mmtids = (array) $mmtids;
  //  $tags = [];
  //  foreach ($mmtids as $mmtid) {
  //    $tags[] = "mm_tree:$mmtid";
  //  }
  //  Cache::invalidateTags($tags);
}

/**
 * Clear the cache for one or more nodes.
 *
 * @param int|array $nids
 *   One or more Node IDs
 */
function mm_content_clear_node_cache($nids) {
  mm_content_clear_cache_tagged(['node' => $nids]);
}

/**
 * Clear the cache for one or more MM Tree entries.
 *
 * @param int|array $mmtids
 *   One or more MMTree IDs
 */
function mm_content_clear_page_cache($mmtids) {
  mm_content_clear_cache_tagged(['mm_tree' => $mmtids]);
}

// ****************************************************************************
// * Private functions start here
// ****************************************************************************

/**
 * Create a new ad-hoc group, and add users to it
 *
 * @param int|string &$gid
 *   ID of the group to add to. Before this function is called for the first
 *   time, set the passed parameter to ''. This function creates the group
 *   entry, adds the first user to it, and sets $gid to the ID of the new group.
 *   Later iterations reuse this ID.
 * @param int $uid
 *   User ID to store in the group
 */
function _mm_content_ad_hoc_group(&$gid, $uid) {
  $db = Database::getConnection();
  if ($gid === '') {
    // First time, so get the next gid to use.
    // Start a transaction, so $gid is valid until this function returns
    $txn = $db->startTransaction();
    $gid = $db->query('SELECT LEAST(-1, MIN(gid) - 1) FROM {mm_group}')->fetchField();
    if (empty($gid)) {
      $gid = -1;
    }
  }
  mm_retry_query($db->insert('mm_group')
    ->fields(array('gid' => $gid, 'uid' => $uid)));
}

function _mm_content_virtual_dir($mmtid, $par, $level, $state) {
  $ch = chr(-$mmtid);
  if (!ctype_alpha($ch)) {
    $name = function_exists('t') ? t('(other)') : '(other)';
    $alias = '~';
  }
  else {
    $name = $alias = $ch;
  }

  $perms = mm_content_user_can($par);
  $perms[Constants::MM_PERMS_WRITE] = $perms[Constants::MM_PERMS_APPLY] = FALSE;
  return (object)array(
    'name' => $name,
    'alias' => $alias,
    'mmtid' => $mmtid,
    'parent' => $par,
    'uid' => 1,
    'default_mode' => Constants::MM_PERMS_READ,
    'bid' => '',
    'max_depth' => -1,
    'max_parents' => -1,
    'perms' => $perms,
    'level' => $level,
    'is_group' => FALSE,
    'is_user' => TRUE,
    'is_dot' => FALSE,
    'is_virtual' => TRUE,
    'state' => $state,
  );
}

/**
 * Create a new recycling bin, or return the tree ID of the existing one
 *
 * @param int $mmtid
 *   The tree ID in which to create the recycle bin. If 0, the user's home
 *   directory is used or, if possible, the top level of the tree.
 * @return int|string
 *   Either the tree ID of the recycling bin or an error string. Use
 *   is_numeric() to evaluate.
 */
function _mm_content_make_recycle($mmtid = 0) {
  $user = \Drupal::currentUser();

  if (!$mmtid) {
    if (!empty($user->user_mmtid) && mm_content_user_can($user->user_mmtid, Constants::MM_PERMS_SUB)) {
      $mmtid = $user->user_mmtid;
    }
    elseif (mm_content_user_can(mm_home_mmtid(), Constants::MM_PERMS_SUB)) {
      $mmtid = mm_home_mmtid();
    }
    else {
      return t('Could not create a recycle bin');
    }
  }

  $found = mm_content_get(array('parent' => $mmtid, 'name' => Constants::MM_ENTRY_NAME_RECYCLE));
  if ($found) {
    return $found[0]->mmtid;
  }

  return mm_content_insert_or_update(TRUE, $mmtid, array(
    'name' => Constants::MM_ENTRY_NAME_RECYCLE,
    'alias' => t('-recycle'),
    'uid' => $user->id(),
  ));
}

function _mm_content_comments_readable(NodeInterface $node) {
  if (mm_get_setting('comments.finegrain_readability')) {
    $perm = empty($node->comments_readable) ? Constants::MM_COMMENT_READABILITY_DEFAULT : $node->comments_readable;
    return \Drupal::currentUser()->hasPermission($perm);
  }
  return \Drupal::currentUser()->hasPermission('access comments');
}

function _mm_content_access_cache($cid, $data = NULL, $uid = NULL, $nid = NULL, $mmtid = NULL) {
  if ($cache_time = mm_get_setting('access_cache_time')) {
    $obj = \Drupal::cache('mm_access');
    if (!empty($data)) {
      $add_page_tags = function ($mmtid) use (&$tags) {
        foreach (mm_content_get_parents_with_self($mmtid, FALSE, FALSE) as $page) {
          if ($page > 1) {
            $tags[] = "mm_tree:$page";
          }
        }
      };

      $tags = [];
      if (!is_null($uid)) {
        // Allow this entry to be invalidated by either our own internal tag
        // or the global 'user' tag. This way we can choose to invalidate just
        // our data without also invalidating entries in all other caches.
        $tags[] = "user:$uid";
        $tags[] = "_mm_user:$uid";
      }
      if (!empty($nid)) {
        // Allow this entry to be invalidated by either our own internal tag
        // or the global 'node' tag.
        $tags[] = "node:$nid";
        $tags[] = "_mm_node:$nid";
        // Add tags for all pages on which this node appears.
        foreach (mm_content_get_by_nid($nid) as $node_mmtid) {
          $add_page_tags($node_mmtid);
        }
      }
      else if (!empty($mmtid)) {
        $add_page_tags($mmtid);
      }
      $expire = mm_request_time() + $cache_time;
      return $obj->set($cid, $data, $expire, $tags);
    }
    elseif ($cache = $obj->get($cid)) {
      if ($cache->expire > mm_request_time()) {
        return $cache->data;
      }
    }
  }
  return FALSE;
}

function _mm_content_clear_access_cache($mmtid = NULL) {
  static $list = array();
  // Make a list of pages whose permissions or location in the tree have
  // changed, then remove entries from cache.mm_access for all nodes appearing
  // on these pages and their children during exit.
  if (!empty($mmtid)) {
    foreach ((array) $mmtid as $m) {
      $list[$m] = TRUE;
    }
  }
  elseif ($list) {
    // Get the list of pages whose permissions or location in the tree have
    // changed and remove entries from cache.mm_access for all nodes appearing
    // on these pages and their children.
    mm_content_clear_page_cache(array_keys($list));
    $list = array();
  }
}
