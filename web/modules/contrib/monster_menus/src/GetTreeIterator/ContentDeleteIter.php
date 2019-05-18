<?php
namespace Drupal\monster_menus\GetTreeIterator;

use Drupal\Core\Database\Database;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;
use Drupal\monster_menus\GetTreeIterator;
use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class ContentDeleteIter extends GetTreeIterator {

  /**
   * Database Service Object.
   *
   * @var Connection
   */
  protected $database;

  public $delete_nodes, $allow_non_empty_bin, $count = 0, $err = '';
  private $first, $mmtids = array(), $bins = array(), $is_bin = FALSE, $is_cli;

  /**
   * Constructs a ContentDeleteIter object.
   *
   * @param $delete_nodes
   *   If TRUE, also delete any nodes using these IDs (TRUE)
   * @param $allow_non_empty_bin
   *   If TRUE, allow a non-empty recycle bin at the top level to be deleted
   *   (FALSE)
   */
  public function __construct($delete_nodes, $allow_non_empty_bin) {
    $this->delete_nodes = $delete_nodes;
    $this->allow_non_empty_bin = $allow_non_empty_bin;
    $this->is_cli = !isset($_SERVER['SERVER_SOFTWARE']) && PHP_SAPI === 'cli';
    $this->database = Database::getConnection();
  }

  /**
   * {@inheritdoc}
   */
  public function iterate($item) {
    $this->count++;
    if ($this->is_cli && ($this->count % 10) == 0) {
      print $this->count . "\r";
    }

    if (!$item->level) {
      $this->first = $item;
      if ($item->name == Constants::MM_ENTRY_NAME_RECYCLE) {
        $this->is_bin = TRUE;
      }
    }
    else if ($this->is_bin && $item->level == 1 && !$this->allow_non_empty_bin) {
      $this->err = t('This is a recycle bin that is not empty.');
      return 0;
    }

    if (!$item->perms[Constants::MM_PERMS_WRITE] && $item->name != Constants::MM_ENTRY_NAME_RECYCLE) {
      $msg = $item->mmtid == $this->first->mmtid ?
        'You do not have permission to delete the page %name' :
        'You cannot delete this page because you do not have permission to delete the sub-page %name';
      $this->err = t($msg, array('%name' => $item->name));
      return 0;
    }
    $this->mmtids[] = $item->mmtid;
    if ($item->name == Constants::MM_ENTRY_NAME_RECYCLE) {
      $this->bins[] = $item->mmtid;
    }

    return 1;
  }

  /**
   * Perform the deletion.
   */
  public function delete() {
    if ($this->err) {
      return;
    }

    $all_nids = array();

    // Move the parent page to the end of the list so it gets deleted last.
    array_push($this->mmtids, array_shift($this->mmtids));

    // Start a transaction.
    $txn = $this->database->startTransaction();

    $remain = count($this->mmtids);
    foreach (array_chunk($this->mmtids, 50) as $mmtids) {
      if ($this->is_cli) {
        print $remain . " \r";
        $remain -= count($mmtids);
      }

      $nids = array();
      if ($this->delete_nodes) {
        /** @var NodeInterface $node */
        foreach (Node::loadMultiple(mm_content_get_nids_by_mmtid($mmtids, 0, TRUE)) as $nid => $node) {
          if ($node->access('delete')) {
            $nids[] = $nid;
            $node->delete();
          }
        }
      }
      if ($nids) {
        $all_nids = array_merge($all_nids, $nids);
      }

      MMTree::deleteMultiple($mmtids);

      // Invoke mm_delete hooks.
      \Drupal::moduleHandler()->invokeAll('mm_delete', [$mmtids, $nids]);
    }

    // End transaction.
    unset($txn);

    if ($this->mmtids) {
      mm_content_notify_change('delete_page', $this->mmtids);
    }
    if ($all_nids) {
      mm_content_notify_change('delete_node', NULL, $all_nids);
    }
    // Clear caches for this entry and it children.
    mm_content_clear_caches($this->mmtids);
    mm_content_clear_routing_cache_tagged($this->mmtids);

    if ($this->bins) {
      $this->database->delete('mm_tree_revision')
        ->condition('mmtid', $this->bins, 'IN')
        ->execute();
    }

    mm_content_clear_caches($this->first->parent);   // clear caches for parent

    \Drupal::logger('mm')->notice('Deleted %name (%alias) mmtids = %mmtids', array(
      '%name' => $this->first->name,
      '%alias' => $this->first->alias,
      '%mmtids' => join(',', array_slice($this->mmtids, 0, 10)) . (count($this->mmtids) > 10 ? '...' : ''),
    ));
  }

}
