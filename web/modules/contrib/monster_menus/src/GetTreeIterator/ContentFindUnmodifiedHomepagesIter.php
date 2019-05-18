<?php
namespace Drupal\monster_menus\GetTreeIterator;

use Drupal\Core\Database\Database;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\GetTreeIterator;
use Drupal\Core\Database\Connection;

class ContentFindUnmodifiedHomepagesIter extends GetTreeIterator {

  /**
   * Database Service Object.
   *
   * @var Connection
   */
  protected $database;

  private $process_func, $consider_empty_pages, $age, $threshold, $skip_entries, $container_entries;
  private $user, $oldest, $newest, $extra_container = FALSE, $skip_user = FALSE, $stop = FALSE;

  /**
   * Number of users processed
   *
   * @var int
   */
  public $count = 0;

  /**
   * Constructs a ContentFindUnmodifiedHomepagesIter object.
   *
   * @param $process_func
   *   A function which will process the homepages that are found. The function
   *   can do things like move them to the recycle bin or just calculate a
   *   count. It is passed one parameter: the tree object of the user page. If
   *   the function returns FALSE all further processing of users will be
   *   stopped.
   * @param bool $consider_empty_pages
   *   If TRUE, test the creation/modification times of pages that are empty. If
   *   not, only test pages with associated nodes.
   * @param int $age
   *   The number of seconds from the current time during which a node or page
   *   is considered too new to delete.
   * @param int $threshold
   *   The number of seconds during which a user's pages and their contents must
   *   have been created in order for them to be considered unchanged. This
   *   accounts for the possibility of a homepage taking longer than a second to
   *   initially create. The value must be small enough that it would not be
   *   possible for a user to manually create a homepage and add some content to
   *   it within that time.
   */
  public function __construct(callable $process_func, $consider_empty_pages, $age, $threshold) {
    $this->process_func = $process_func;
    $this->consider_empty_pages = $consider_empty_pages;
    $this->age = $age;
    $this->threshold = $threshold;
    $this->skip_entries = array(Constants::MM_ENTRY_NAME_RECYCLE, Constants::MM_ENTRY_NAME_DEFAULT_USER);
    $this->container_entries = array(Constants::MM_ENTRY_NAME_DISABLED_USER);
    mm_module_invoke_all_array('mm_find_unmodified_homepages_alter', array(&$this->skip_entries, &$this->container_entries));
    $this->database = Database::getConnection();
  }

  /**
   * Process the current user with $this->process_func.
   */
  public function process_user() {
    if (!empty($this->user) && !$this->stop && !$this->skip_user &&
        $this->newest - $this->oldest <= $this->threshold &&
        $this->newest <= mm_request_time() - $this->age) {
        $this->count++;
      if (call_user_func($this->process_func, $this->user) === FALSE) {
        $this->stop = TRUE;
      }
    }
    $this->user = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function iterate($item) {
    if ($item->level) {
      if ($item->level == 1 || $this->extra_container && $item->level == 2) {
        if ($item->level == 1) {
          // By default, assume we've found a non-disabled top-level child.
          $this->extra_container = FALSE;

          if (in_array($item->name, $this->skip_entries)) {
            // Top-level recycle bin or .Default user: skip
            return -1;
          }

          if (in_array($item->name, $this->container_entries)) {
            // It's MM_ENTRY_NAME_DISABLED_USER or the like, so treat
            // children like top-level users.
            $this->extra_container = TRUE;
            return 1;   // Continue with children.
          }
        }
        // Reached a new user: process any previous and reset stats.
        $this->process_user();
        $this->oldest = mm_request_time() + 1;
        $this->newest = 0;
        $this->skip_user = FALSE;
        $this->user = $item;
      }

      if ($this->skip_user || in_array($item->name, $this->skip_entries)) {
        // Skip because either this user was previously marked for skipping
        // or the new item is a recycle bin or the like.
        $this->skip_user = TRUE;
        return -1;
      }

      // Avoid calling mm_content_get_nids_by_mmtid() unless needed.
      $nids = $this->consider_empty_pages ? NULL : mm_content_get_nids_by_mmtid($item->mmtid);

      if ($this->consider_empty_pages || $nids) {
        // Old installations of MM can have empty ctime or mtime entries.
        $ctime = $item->ctime ? $item->ctime : 0;
        $mtime = $item->mtime ? $item->mtime : 0;
        $this->oldest = min($ctime, $mtime, $this->oldest);
        $this->newest = max($ctime, $mtime, $this->newest);
        if ($this->newest - $this->oldest > $this->threshold) {
          $this->skip_user = TRUE;
        }
      }

      if (!$this->skip_user) {
        // Avoid calling mm_content_get_nids_by_mmtid() unless needed.
        if (is_null($nids)) {
          $nids = mm_content_get_nids_by_mmtid($item->mmtid);
        }

        if ($nids) {
          // Given all nodes on this page, find out how many are unchanged
          // and get the min/max of their creation dates.
          $stats = $this->database->query('SELECT COUNT(*) AS unchanged, MIN(created) AS min_date, MAX(created) AS max_date FROM {node} WHERE created = changed AND nid IN(:nids[])', array(':nids[]' => $nids))->fetchObject();
          if ($stats->unchanged != count($nids)) {
            // One or more nodes changed since creation.
            $this->skip_user = TRUE;
          }
          else {
            // Compare oldest/newest to running stats for this user.
            $this->oldest = min($stats->min_date, $this->oldest);
            $this->newest = max($stats->max_date, $this->newest);
            if ($this->newest - $this->oldest > $this->threshold) {
              $this->skip_user = TRUE;
            }
          }
        }
      }
    }

    if ($this->stop) {
      return 0;   // Stop everything.
    }
    if ($this->skip_user) {
      return -1;  // Skip this one and its children.
    }

    return 1;   // Continue.
  }

}
