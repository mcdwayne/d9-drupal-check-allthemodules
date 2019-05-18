<?php
namespace Drupal\monster_menus\GetTreeIterator;

use Drupal\Core\Database\Database;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\GetTreeIterator;

class ContentMoveIter extends GetTreeIterator{

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public $parents, $pindex, $error, $recycle_mode, $bin, $delete_bins, $time, $first, $src_sort_idx;
  private $moved_node;

  /**
   * Constructs a ContentMoveIter object.
   *
   * @param $parents
   *
   * @param $recycle_mode
   *
   * @param $bin
   *
   * @param $src_sort_idx
   */
  public function __construct($parents, $recycle_mode, $bin, $src_sort_idx) {
    $this->parents = $parents;
    $this->pindex = count($parents);
    $this->recycle_mode = $recycle_mode;
    $this->time = mm_request_time();
    $this->bin = $bin;
    $this->delete_bins = array();
    $this->src_sort_idx = $src_sort_idx;
    $this->first = TRUE;
    $this->moved_node = array();
    $this->database = Database::getConnection();
  }

  /**
   * @inheritdoc
   */
  public function iterate($item) {
    if (isset($this->error)) {
      // was set in a previous invocation
      return 0;
    }

    $this->parents = array_slice($this->parents, 0, $this->pindex + $item->level);
    if ($this->first) {
      $parent = $this->parents[count($this->parents) - 1];
      $parent_idx = _mm_content_get_next_sort($parent);
      mm_content_update_parents($item->mmtid, $this->parents);
      mm_content_write_revision($item->mmtid);

      // Temporarily move the sort_idx into the right part of the tree, using
      // an index which is larger than it needs to be, then re-sort later.
      $like = str_replace(array('\\', '_', '%'), array('\\\\', '\_', '\%'), $this->src_sort_idx) . '%';
      $txn = $this->database->startTransaction();  // Lock DB.
      $this->database->update('mm_tree')
        ->fields(array('sort_idx_dirty' => 1, 'weight' => 0))
        ->condition('mmtid', $item->mmtid)
        ->execute();
      $this->database->update('mm_tree')
        ->expression('sort_idx', 'CONCAT(:parent_idx, SUBSTRING(sort_idx, :length))', array(':parent_idx' => $parent_idx, ':length' => strlen($this->src_sort_idx) + 1))
        ->condition('sort_idx', $like, 'LIKE')
        ->execute();
      unset($txn);              // Free lock.
      mm_content_update_sort_queue($parent);
      mm_content_notify_change('move_page', $item->mmtid, NULL, array('old_parent' => $item->parent, 'new_parent' => $parent));
      mm_content_clear_routing_cache_tagged($item->mmtid);
      $this->first = FALSE;
    }
    else {
      mm_content_update_parents($item->mmtid, $this->parents);
    }

    $this->parents[] = $item->mmtid;
    mm_content_clear_caches($item->mmtid);

    if ($this->recycle_mode == 'recycle' && !$item->perms[Constants::MM_PERMS_IS_RECYCLED]) {
      foreach (mm_content_get_nids_by_mmtid($item->mmtid) as $nid) {
        if (!in_array($nid, $this->moved_node)) {
          $this->moved_node[] = $nid;
          foreach (mm_content_get_by_nid($nid) as $mmtid) {
            if (in_array($this->bin, mm_content_get_parents_with_self($mmtid))) {
              $this->database->merge('mm_recycle')
                ->keys(array(
                  'type' => 'node',
                  'id' => $nid,
                  'from_mmtid' => $mmtid
                ))
                ->fields(array(
                  'bin_mmtid' => $this->bin,
                  'recycle_date' => $this->time
                ))
                ->execute();
              \Drupal::logger('mm')->notice('Recycled node=@nid from mmtid=@mmtid', array('@nid' => $nid, '@mmtid' => $mmtid));
            }
          }
        }
      }
    }
    elseif ($this->recycle_mode == 'restore') {
      $dels = array();
      $txn = $this->database->startTransaction();    // Lock.
      $result = $this->database->select('mm_recycle', 'r')
        ->fields('r', array('id', 'from_mmtid'))
        ->condition('r.bin_mmtid', $this->bin)
        ->condition('r.type', 'node')
        ->execute();
      foreach ($result as $r) {
        $this->database->update('mm_node2tree')
          ->fields(array('mmtid' => $r->from_mmtid))
          ->condition('mmtid', $this->bin)
          ->condition('nid', $r->id)
          ->execute();
        mm_content_clear_caches(array($r->from_mmtid, $this->bin));
        $this->delete_bins[$this->bin] = 1;
        $dels[] = $r->id;
      }
      unset($txn);    // Unlock.
      // Clear the cache used by mm_content_get_by_nid.
      mm_content_get_by_nid(NULL, TRUE);

      if ($dels) {
        $this->database->delete('mm_recycle')
          ->condition('type', 'node')
          ->condition('id', $dels, 'IN')
          ->execute();
        \Drupal::logger('mm')->notice('Restored nodes to mmtid=@mmtid', array('@mmtid' => $item->mmtid));
      }
    }

    return 1;
  }

}
