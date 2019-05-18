<?php
/**
 * Functions to import/export a section of the MM tree.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\GetTreeIterator\MMExportIter;
use Drupal\monster_menus\MMImportExportException;
use Drupal\node\NodeInterface;

function _mm_export_prepend_groups_mmtid(&$array) {
  // Prepend the correct groups tree root to any permission groups.
  static $groups_root;
  if (!isset($groups_root)) {
    $groups_root = mm_content_get(mm_content_groups_mmtid());
  }

  foreach (array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ) as $m) {
    if (isset($array['perms'][$m]['groups']) && is_array($array['perms'][$m]['groups'])) {
      foreach ($array['perms'][$m]['groups'] as &$g) {
        if (is_array($g)) {
          foreach ($g as $key => $gid) {
            $g[$key] = &$array['pool'][$gid];
          }
          array_unshift($g, $groups_root);
        }
      }
    }
  }
  // Keep mm_content_insert_or_update() happy.
  unset($array['pool']);
}

/**
 * Export a section of the MM tree.
 *
 * @param int $mmtid
 *   Starting location.
 * @param bool $include_nodes
 *   If TRUE, include node contents.
 * @throws \Drupal\monster_menus\MMImportExportException
 *   Any error condition.
 * @return string
 *   PHP source code for the exported data.
 */
function mm_export($mmtid, $include_nodes = FALSE) {
  if ($include_nodes && !mm_module_exists('node_export')) {
    throw new MMImportExportException('The node_export module is required to export nodes.');
  }

  $params = array(
    Constants::MM_GET_TREE_RETURN_BLOCK =>      TRUE,
    Constants::MM_GET_TREE_RETURN_FLAGS =>      TRUE,
    Constants::MM_GET_TREE_RETURN_NODE_COUNT => TRUE,
    Constants::MM_GET_TREE_RETURN_PERMS =>      TRUE,
    Constants::MM_GET_TREE_ITERATOR =>          new MMExportIter($include_nodes),
  );
  mm_content_get_tree($mmtid, $params);

  return $params[Constants::MM_GET_TREE_ITERATOR]->dump();
}

/**
 * Import a section of the MM tree.
 *
 * @param string $code
 *   The code block, from mm_export().
 * @param int $start_mmtid
 *   The MM Tree ID of the location at which to import. The imported section is
 *   added (or updated) as a child of this location.
 * @param string $mode
 *   One of the constants:
 *   - MM_IMPORT_ADD: Add-only: Don't change existing nodes or pages, just add
 *       anything new.
 *   - MM_IMPORT_UPDATE: Update: Overwrite existing nodes and pages, if
 *       different; does not modify any pre-existing nodes/pages not part of the
 *       import.
 *   - MM_IMPORT_DELETE: Delete first: Move any existing nodes and pages to a
 *       recycle bin before importing.
 * @param bool $test
 *   If TRUE, go through the motions, but do not make any changes.
 * @param bool $include_nodes
 *   If TRUE, insert or update any nodes contained in the $code.
 * @param array []|string &$stats
 *   (optional) Array with which to populate statistics:
 *   - nodes:
 *     An array indexed by nid, containing sub-arrays with the elements
 *     "message" and "vars", which describe the nodes that were acted upon.
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
 *   A count of the number of pages/nodes acted upon can be derived using the
 *   count() function.
 * @throws MMImportExportException
 *   Any error condition.
 */
function mm_import($code, $start_mmtid, $mode = Constants::MM_IMPORT_ADD, $test = FALSE, $include_nodes = FALSE, &$stats = 'undef') {
  /**
   * @var NodeInterface $nodes[]
   * @var array $page_nodes
   * @var array $pool
   * @var array $pages
   */
  eval($code);
  if (!isset($version) || !is_array($nodes) || !is_array($page_nodes) || !is_array($pool) | !is_array($pages)) {
    throw new MMImportExportException('mm_import: The code does not include all necessary variables.');
  }
  if (version_compare($version, Constants::MM_IMPORT_VERSION, '!=')) {
    throw new MMImportExportException('mm_import: Incompatible version.');
  }
  if ($include_nodes && !mm_module_exists('node_export')) {
    throw new MMImportExportException('mm_import: The node_export module is required to import nodes.');
  }
  if ($mode == Constants::MM_IMPORT_DELETE && $test) {
    throw new MMImportExportException('mm_import: The test option cannot be used with "delete first" mode.');
  }

  $add_only = $mode == Constants::MM_IMPORT_ADD;
  foreach ($pages as $index => $page) {
    if (!$index) {
      $start = mm_content_get($start_mmtid);
      if ($mode == Constants::MM_IMPORT_DELETE) {
        $existing = mm_content_get(array('parent' => $start_mmtid, 'alias' => $page[0]->alias));
        if ($existing) {
          mm_content_move_to_bin($existing[0]->mmtid);
          mm_content_update_sort_queue();
        }
      }
      array_unshift($page, $start);
    }
    // @FIXME
    mm_create_path($page, FALSE, $test, $add_only, $stats);
  }

  if ($include_nodes && $page_nodes) {
    if (is_array($stats) && isset($stats['errors']) && $stats['errors']) {
      _mm_report_error('Nodes were not imported, due to errors when processing pages/groups.', array(), $stats);
    }
    else {
      $groups_root = mm_content_get(mm_content_groups_mmtid());
      foreach ($page_nodes as $mmtid => $nodes2) {
        $new_mmtid = $pool[$mmtid]->mmtid;
        foreach ($nodes2 as $node) {
          if (!isset($node->mm_catlist)) {
            // Create any necessary groups.
            $new_groups_w = array();
            foreach ($node->groups_w as $g) {
              $path = array($groups_root);
              foreach ($g as $gid) {
                $path[] = &$pool[$gid];
              }
              mm_create_path($path, FALSE, $test, $add_only, $stats);
              $new_groups_w[$path[count($path) - 1]->mmtid] = '';
            }
            $node->groups_w = $new_groups_w;

            $node->mm_catlist = $node->_mm_import_catlist = array();
          }
          $node->mm_catlist[$new_mmtid] = '';         // Used by MM
          $node->_mm_import_catlist[] = $new_mmtid;   // Internal use
        }
      }

      if (is_array($stats)) {
        $stats['nodes'] = array();
      }
      foreach ($nodes as $node) {
        $code_string = \Drupal\Component\Utility\Variable::export(array($node));
        // Never let node_export_import() save here, just test.
        $result = node_export_import($code_string, 't', FALSE);
        if (is_array($stats) && !$result['success']) {
          foreach ($result['output'] as $message) {
            $stats['errors'][] = array('message' => $message, 'vars' => array());
          }
        }
        if ($result['success']) {
          $imported_node = $result['nodes'][0];
          if ($mode == Constants::MM_IMPORT_DELETE) {
            $imported_node->is_new = $is_new = TRUE;
            $imported_node->uuid = \Drupal::service('uuid')->generate();
          }
          else {
            $exists = Database::getConnection()->query('SELECT n2.nid FROM {mm_node2tree} n2 INNER JOIN {node} n ON n.nid = n2.nid WHERE n2.mmtid IN(:mmtid[]) AND n.type = :type AND (n.uuid = :uuid OR n.nid = :nid) ORDER BY n.uuid = :uuid DESC LIMIT 1', array(
              ':mmtid[]' => $node->_mm_import_catlist,
              ':type' => $node->getType(),
              ':uuid' => $node->uuid(),
              ':nid' => $node->id(),
            ))->fetchField();

            if ($add_only && $exists) {
              // Don't save or update.
              continue;
            }

            if ($exists) {
              // Update with a new revision.
              // FIXME: probably use setNewRevision() here
              $imported_node->nid = $exists;
              $imported_node->uuid = $node->uuid;
              $imported_node->is_new = $is_new = FALSE;
              $imported_node->revision = 1;
            }
            else {
              $imported_node->is_new = $is_new = TRUE;
              $imported_node->uuid = \Drupal::service('uuid')->generate();
            }
          }

          if (!$test) {
            node_export_save($imported_node);
          }
          if (is_array($stats)) {
            if ($test) {
              $msg = $is_new ? 'Would have created @link' : 'Would have updated @link';
            }
            else {
              $msg = $is_new ? 'Created @link' : 'Updated @link';
            }
            $stats['nodes'][] = array(
              'message' => $msg,
              'vars' => array('@link' => Link::fromTextAndUrl(mm_ui_fix_node_title($imported_node->label()), Url::fromRoute('entity.node.canonical', ['node' => $imported_node->id()]))->toString())
            );
          }
        }
      }
    }
  }
}
