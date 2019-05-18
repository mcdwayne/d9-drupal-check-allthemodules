<?php

/**
 * @file
 * Service to validate and repair the MM tree.
 */

namespace Drupal\monster_menus;

use Drupal\Core\Database\Connection;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drush\Drush;
use Drush\Log\LogLevel;

class ValidateSortIndex {

  use StringTranslationTrait;

  /**
   * Send output to the current web page (do not use in a batch script). This
   * is the default method, and is used by the admin/mm/sort menu entry.
   */
  const OUTPUT_MODE_MESSAGE = 'message';
  /**
   * The \Drupal::logger() function (suitable for cron)
   */
  const OUTPUT_MODE_WATCHDOG = 'watchdog';
  /**
   * Print the messages to standard i/o.
   */
  const OUTPUT_MODE_PRINT = 'print';
  /**
   * Use when called by drush.
   */
  const OUTPUT_MODE_DRUSH = 'drush';

  /**
   * Max. number of items to display
   */
  const MM_ADMIN_VALIDATE_SORT_INDEX_MAX = 50;

  /**
   * The number of errors detected.
   *
   * @var int
   */
  private $errors = 0;

  /**
   * The number of unfixable errors.
   *
   * @var int
   */
  private $unfixableErrors = 0;

  /**
   * The current output mode.
   *
   * @var string
   */
  private $outputMode = self::OUTPUT_MODE_MESSAGE;

  /**
   * Holds a list of tree segments to update upon completion.
   *
   * @var array
   */
  private $sortQueueUpdates = [];

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructs a ValidateSortIndex object.
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Set the output mode.
   *
   * @param string $mode
   *   One of the OUTPUT_MODE_* constants.
   * @return ValidateSortIndex
   *   The object, for chaining.
   */
  public function setOutputMode($mode = self::OUTPUT_MODE_MESSAGE) {
    $this->outputMode = $mode;
    return $this;
  }

  /**
   * Send a message to the output queue.
   *
   * @param bool $fix
   *   TRUE if errors are being fixed.
   * @param string $message
   *   Message to log.
   * @param array $subst
   *   Strings substituted into $message.
   * @param bool $unfixable
   *   TRUE if the error cannot be fixed.
   * @return mixed|void
   */
  private function output($fix, $message, $subst, $unfixable = FALSE) {
    if (is_null($message)) {
      if ($this->outputMode == self::OUTPUT_MODE_MESSAGE) {
        if ($this->errors) {
          if ($fix) {
            $fixable = $this->errors - $this->unfixableErrors;
            $out = [];
            if ($fixable) {
              $out[] = \Drupal::translation()->formatPlural($fixable, 'One error was fixed.', '@count errors were fixed.');
            }
            if ($this->unfixableErrors) {
              $out[] = \Drupal::translation()->formatPlural($this->unfixableErrors, 'There was one unfixable error.', 'There were @count unfixable errors.');
            }
            return implode(' ', $out);
          }
          if ($this->errors == $this->unfixableErrors) {
            return $this->t('The listed error(s) cannot be fixed automatically.');
          }
          return array(
            '#type' => 'link',
            '#title' => $this->t('Click here to fix all errors possible'),
            '#url' => Url::fromRoute('monster_menus.admin_validate_sort_index_fix'),
          );
        }
        return $this->t('No errors found.');
      }
      return;
    }

    if ($unfixable) {
      $this->unfixableErrors++;
    }
    if ($this->outputMode == self::OUTPUT_MODE_WATCHDOG || $this->outputMode == self::OUTPUT_MODE_MESSAGE) {
      if ($this->errors++ == self::MM_ADMIN_VALIDATE_SORT_INDEX_MAX) {
        $message = 'Only the first @num messages are shown.';
        $subst = array('@num' => self::MM_ADMIN_VALIDATE_SORT_INDEX_MAX);
      }
      elseif ($this->errors > self::MM_ADMIN_VALIDATE_SORT_INDEX_MAX) {
        return;
      }
    }
    else {
      $this->errors++;
    }

    switch ($this->outputMode) {
      case self::OUTPUT_MODE_WATCHDOG:
        \Drupal::logger('mm')->error($message, $subst);
        break;

      case self::OUTPUT_MODE_MESSAGE:
        if (!$fix) {
          \Drupal::messenger()->addError($this->t($message, $subst));
        }
        break;

      case self::OUTPUT_MODE_PRINT:
        print $this->t($message, $subst) . "\n";
        break;

      case self::OUTPUT_MODE_DRUSH:
        Drush::logger()->log(LogLevel::OK, $this->t($message, $subst));
        break;
    }
  }

  /**
   * Queue a portion of the tree for a sort index update upon exit.
   *
   * @param int $mmtid
   *   The Tree ID of the portion to update.
   * @param bool $all
   *   If TRUE, update all entries, not just the dirty ones.
   * @param array $parent_mmtids
   *   A list of the parent Tree IDs.
   */
  private function updateSortQueue($mmtid, $all, $parent_mmtids) {
    if ($mmtid) {
      // Make sure a particular entry doesn't already have parents that are
      // going to be updated anyway. This only compares known parents, so
      // store everything and do another pass at the end.
      $parent = $mmtid;
      while (isset($parent_mmtids[$parent]) && !isset($this->sortQueueUpdates[$parent])) {
        $parent = $parent_mmtids[$parent];
      }
      if (!isset($this->sortQueueUpdates[$parent])) {
        $this->sortQueueUpdates[$mmtid] = isset($this->sortQueueUpdates[$mmtid]) ? $this->sortQueueUpdates[$mmtid] | $all : $all;
      }
    }
    else {
      // Repeat the parent test for all entries flagged to be updated.
      foreach ($this->sortQueueUpdates as $mmtid => $all) {
        $parent = $mmtid;
        while (isset($parent_mmtids[$parent]) && ($parent == $mmtid || !isset($this->sortQueueUpdates[$parent]))) {
          $parent = $parent_mmtids[$parent];
        }
        if ($mmtid == 1 || !isset($this->sortQueueUpdates[$parent])) {
          mm_content_update_sort_queue($mmtid, NULL, $all);
        }
      }
    }
  }

  /**
   * Return a link to a particular tree entry.
   *
   * @param $mmtid
   *   The Tree ID of the entry.
   * @return GeneratedLink|string|int
   *   Various output, depending on the ::outputMode.
   */
  private function mmtidLink($mmtid) {
    if (strchr($mmtid, ',') !== FALSE) {
      $out = array();
      foreach (explode(',', $mmtid) as $m) {
        $out[] = $this->mmtidLink($m);
      }
      return implode(', ', $out);
    }
    if ($this->outputMode == self::OUTPUT_MODE_PRINT || $this->outputMode == self::OUTPUT_MODE_DRUSH) {
      return $mmtid;
    }
    return Link::fromTextAndUrl($mmtid, mm_content_get_mmtid_url($mmtid))->toString();
  }

  private function moveToLostAndFound($mmtid, &$lost_found) {
    if (self::createLostAndFound($lost_found, $error_message, $error_strings)) {
      $this->output(FALSE, $error_message, $error_strings);
      return TRUE;
    }

    $this->database->update('mm_tree')
      ->condition('mmtid', $mmtid)
      ->fields(array('parent' => $lost_found, 'alias' => "RECOVER-$mmtid", 'sort_idx_dirty' => 1))
      ->execute();
    mm_content_clear_caches($mmtid);
    mm_content_update_parents($mmtid);
    mm_content_update_sort_queue($lost_found);
    return FALSE;
  }

  public static function createLostAndFound(&$mmtid, &$error_message, &$error_strings, $subpage = array()) {
    static $lost_found;

    if (!isset($lost_found)) {
      // Intentionally use MM_HOME_MMTID_DEFAULT instead of mm_home_mmtid(), since
      // there might not be a '.System' page there.
      $system = mm_content_get(array('parent' => Constants::MM_HOME_MMTID_DEFAULT, 'name' => Constants::MM_ENTRY_NAME_SYSTEM));
      if (!$system) {
        $error_message = 'Could not find @system page at the root level';
        $error_strings = array('@system' => Constants::MM_ENTRY_NAME_SYSTEM);
        return TRUE;
      }

      if ($tree = mm_content_get(array('parent' => $system[0]->mmtid, 'name' => Constants::MM_ENTRY_NAME_LOST_FOUND))) {
        $lost_found = $tree[0]->mmtid;
      }
      else {
        try {
          $lost_found = mm_content_insert_or_update(TRUE, $system[0]->mmtid, array(
            'name' => Constants::MM_ENTRY_NAME_LOST_FOUND,
            'alias' => Constants::MM_ENTRY_ALIAS_LOST_FOUND,
            'hidden' => TRUE,
          ));
        }
        catch (\Exception $e) {
        }

        if (empty($lost_found)) {
          $error_message = 'Could not create the @lost page in @system';
          $error_strings = array('@lost' => Constants::MM_ENTRY_NAME_LOST_FOUND, '@system' => Constants::MM_ENTRY_NAME_SYSTEM);
          return TRUE;
        }
      }
    }
    $mmtid = $lost_found;

    if ($lost_found && $subpage) {
      if ($tree = mm_content_get(array('parent' => $lost_found, 'alias' => $subpage['alias']))) {
        $mmtid = $tree[0]->mmtid;
      }
      else {
        try {
          $mmtid = mm_content_insert_or_update(TRUE, $lost_found, $subpage);
        }
        catch (\Exception $e) {
        }

        if (empty($mmtid)) {
          $error_message = 'Could not create the @lost subpage %alias';
          $error_strings = array('@lost' => Constants::MM_ENTRY_NAME_LOST_FOUND, '%alias' => $subpage['alias']);
          return TRUE;
        }
      }
    }

    return !$mmtid;
  }

  /**
   * Check all mm_tree.sort_idx entries and optionally fix any that seem to be
   * incorrect.
   *
   * @param bool $fix
   *   (optional) If TRUE, correct errors, when possible.
   * @return string|void
   *   If the ::outputMode is ::OUTPUT_MODE_MESSAGE, a description of the
   *   number of errors, otherwise nothing.
   */
  public function validate($fix = FALSE) {
    // Wait up to 15 sec. to grab the semaphore.
    if (($have_semaphore = mm_content_update_sort_test_semaphore(15)) !== TRUE) {
      $this->output(FALSE, $have_semaphore->render(), array());
      return $this->output(FALSE, NULL, NULL);
    }

    $lost_found = NULL;
    $_mmtbt_cache = &drupal_static('_mmtbt_cache', array());
    $q = $this->database->query('SELECT t.mmtid FROM {mm_tree} t LEFT JOIN {mm_tree_parents} p ON p.mmtid = t.mmtid AND p.parent = :mmtid1 WHERE t.mmtid <> :mmtid2 AND p.mmtid IS NULL', array(':mmtid1' => 1, ':mmtid2' => 1));
    foreach ($q as $r) {
      $msg = 'mmtid=@m is an orphan.';
      $vars = array('@m' => $r->mmtid);
      if ($fix) {
        $parents = mm_content_get_parents($r->mmtid, TRUE, FALSE);
        if ($parents && $parents[0] == 1) {
          mm_content_update_parents($r->mmtid);
          $msg = 'mmtid=@m was an orphan. It has been reattached to its parent.';
          $vars['@m'] = $this->mmtidLink($r->mmtid);
        }
        else if (!$this->moveToLostAndFound($r->mmtid, $lost_found)) {
          $msg = 'mmtid=@m was an orphan. It has been moved to the %lost page at mmtid=@lost';
          $vars['%lost'] = mm_content_expand_name(Constants::MM_ENTRY_NAME_LOST_FOUND);
          $vars['@lost'] = $this->mmtidLink($lost_found);
          $vars['@m'] = $this->mmtidLink($r->mmtid);
        }
      }
      $this->output($fix, $msg, $vars);
      mm_content_update_sort_queue();
    }

    $parents = $parents_index = $parent_mmtids = array();
    $skip_bad = '';
    $sibling = $prev = $parent = $lost_found = NULL;
    $q = $this->database->query('SELECT t.*, (SELECT GROUP_CONCAT(p.parent ORDER BY p.depth) FROM {mm_tree_parents} p WHERE p.mmtid = t.mmtid) AS tree_parents FROM (SELECT :mmtid1 AS mmtid UNION SELECT p.mmtid FROM {mm_tree_parents} p WHERE p.parent = :mmtid2) x INNER JOIN {mm_tree} t ON t.mmtid = x.mmtid ORDER BY sort_idx', array(':mmtid1' => 1, ':mmtid2' => 1));
    foreach ($q as $r) {
      $skip_tests = FALSE;
      if ($r->sort_idx_dirty) {
        $skip_bad = $r->sort_idx;
        $this->output($fix, $fix ? 'mmtid=@m (parent=@par) is marked as dirty. Updating now.' : 'mmtid=@m (parent=@par) is marked as dirty.', array('@m' => $this->mmtidLink($r->mmtid), '@par' => $this->mmtidLink($r->parent)));
        // An update was previously missed, so definitely do it now
        $this->updateSortQueue($r->parent ? $r->parent : 1, !$r->parent, $parent_mmtids);
      }

      if ($r->mmtid == 1) {
        $parent = $prev = $r;
        continue;
      }

      $parent_mmtids[$r->mmtid] = $r->parent;
      $is_bad = FALSE;
      if ($r->parent == $prev->mmtid) {
        // Going deeper in tree
        if (isset($parent)) {
          $parents_index[] = $parent->mmtid;
          $parent->sibling = $sibling;
          $parents[] = $parent;
        }
        $parent = $r->parent == $prev->mmtid ? $prev : $sibling;
        $sibling = NULL;

        // Note: There's no way to fix these next two, they're just warnings.
        $q_temp = $this->database->query("SELECT GROUP_CONCAT(mmtid ORDER BY mmtid SEPARATOR ',') AS mmtids, name FROM {mm_tree} WHERE parent = :parent GROUP BY name HAVING COUNT(*) > 1", array(':parent' => $parent->mmtid));
        foreach ($q_temp as $bad) {
          $this->output($fix, 'Sibling entries share the same name, @name: !mmtids.', array('@name' => $bad->name, '!mmtids' => $this->mmtidLink($bad->mmtids)), TRUE);
        }

        $q_temp = $this->database->query("SELECT GROUP_CONCAT(mmtid ORDER BY mmtid SEPARATOR ',') AS mmtids, alias FROM {mm_tree} WHERE parent = :parent AND alias <> '' GROUP BY alias HAVING COUNT(*) > 1", array(':parent' => $parent->mmtid));
        foreach ($q_temp as $bad) {
          $this->output($fix, 'Sibling entries share the same alias, @alias: !mmtids.', array('@alias' => $bad->alias, '!mmtids' => $this->mmtidLink($bad->mmtids)), TRUE);
        }
      }
      else {
        if (strncmp($skip_bad, $r->sort_idx, strlen($skip_bad))) {
          // We have gotten to a new parent or sibling of the previous bad item.
          $skip_bad = '';
          if ($sibling->parent != $r->parent) {
            $sibling = NULL;
          }
        }

        // See if we are going back up the tree
        if (($which_parent = array_search($r->parent, $parents_index)) !== FALSE) {
          $parent = $parents[$which_parent];
          $parents_index = array_slice($parents_index, 0, $which_parent);
          $parents = array_slice($parents, 0, $which_parent);
          $sibling = $parent->sibling;
          if ($sibling->parent != $r->parent) {
            $sibling = NULL;
          }
        }
        else if ($parent->mmtid != $r->parent) {
          // If the item's parent exists, this means the sort index is off
          if (mm_content_get($r->parent)) {
            // The index is bad, but don't complain here; do it later on
            $is_bad = TRUE;
          }
          else {
            // The parent no longer exists, try to recover it.
            $skip_tests = TRUE;
            if (!$fix) {
              $this->output(FALSE, 'Entry @mmtid has a missing parent :parent.', array('@mmtid' => $this->mmtidLink($r->mmtid), ':parent' => $r->parent));
            }
            else if (!strncmp($r->tree_parents, '1,', 2)) {
              $tree_parents = explode(',', $r->tree_parents);
              $tree_parent = $tree_parents[count($tree_parents) - 1];
              // Recoverable parent.
              $this->database->update('mm_tree')
                ->condition('mmtid', $r->mmtid)
                ->fields(array('parent' => $tree_parent, 'sort_idx_dirty' => 1))
                ->execute();
              mm_content_clear_caches($r->mmtid);
              $this->updateSortQueue($tree_parent, FALSE, $parent_mmtids);
              $this->output(FALSE, 'Corrected parent of @mmtid.', array('@mmtid' => $this->mmtidLink($r->mmtid)));
            }
            else if (!$this->moveToLostAndFound($r->mmtid, $lost_found)) {
              $this->output(FALSE, 'Entry @mmtid had a missing parent, :parent. It has been moved to the %lost page at mmtid=@lost.', array('@mmtid' => $this->mmtidLink($r->mmtid), ':parent' => $r->parent, '%lost' => mm_content_expand_name(Constants::MM_ENTRY_NAME_LOST_FOUND), '@lost' => $this->mmtidLink($lost_found)));
            }
          }
        }
      }

      if (empty($skip_bad) && !$skip_tests) {
        $parents_str = '';
        $fixed_parents = array();
        $m = $r->mmtid;
        while ($m != 1) {
          if (!isset($parent_mmtids[$m])) {
            $temp_mmtid = mm_content_get_parent($m);
            if (!is_numeric($temp_mmtid)) {
              break;
            }
            $parent_mmtids[$m] = $temp_mmtid;
          }
          $m = $parent_mmtids[$m];
          if (in_array($m, $fixed_parents)) {
            // Should never happen, but this avoids a potential endless loop.
            break;
          }
          $fixed_parents[] = $m;
          $parents_str = $m . ($parents_str ? ',' : '') . $parents_str;
        }
        // Clear out the cache used by mm_content_get(), to save memory.
        $_mmtbt_cache = array();
        if ($m != 1) {
          $this->output($fix, 'Entry at mmtid=@m has impossible parents [...:pars]. mm_tree_parents says they should be [:pars2]. This condition cannot be fixed automatically.', array('@m' => $this->mmtidLink($r->mmtid), ':pars' => $parents_str, ':pars2' => $r->tree_parents));
          $is_bad = TRUE;
        }
        elseif ($parents_str != $r->tree_parents) {
          $this->output($fix, 'Entry at mmtid=@m has parents [:pars] which is inconsistent with mm_tree_parents [:pars2].', array('@m' => $this->mmtidLink($r->mmtid), ':pars' => $parents_str, ':pars2' => $r->tree_parents));
          if ($fix) mm_content_update_parents($r->mmtid, array_reverse($fixed_parents));
          // Intentionally don't set $is_bad, since we don't want to skip kids
        }
        elseif ($r->parent != $parent->mmtid) {
          $this->output($fix, 'Entry at mmtid=@m has a parent=@par that is inconsistent with its sort order.', array('@m' => $this->mmtidLink($r->mmtid), '@par' => $this->mmtidLink($r->parent)));
        }
        elseif (strlen($r->sort_idx) - strlen($parent->sort_idx) != Constants::MM_CONTENT_BTOA_CHARS) {
          // The length of this entry's index is not correct, relative to its parent
          if (strlen($r->sort_idx) > strlen($parent->sort_idx)) {
            $msg = 'Sort index at mmtid=@m (parent=@par) is too long.';
            if (empty($skip_bad)) {
              // Skip this item and its siblings because they all need to be fixed.
              $skip_bad = substr($r->sort_idx, 0, strlen($parent->sort_idx) + Constants::MM_CONTENT_BTOA_CHARS);
            }
          }
          else {
            $msg = 'Sort index at mmtid=@m (parent=@par) is too short.';
          }
          $is_bad = TRUE;
          $this->output($fix, $msg, array('@m' => $this->mmtidLink($r->mmtid), '@par' => $this->mmtidLink($r->parent)));
        }
        else {
          if ($r->sort_idx == $prev->sort_idx) {
            // Entry has same index as its predecessor
            $this->output($fix, 'mmtid=@m1 (parent=@par1) and mmtid=@m2 (parent=@par2) have the same sort index.', array('@m1' => $this->mmtidLink($r->mmtid), '@par1' => $this->mmtidLink($r->parent), '@m2' => $this->mmtidLink($prev->mmtid), '@par2' => $this->mmtidLink($prev->parent)));
            $is_bad = TRUE;
          }
          elseif (strncmp($r->sort_idx, $parent->sort_idx, strlen($parent->sort_idx))) {
            // The indices should match, up to the parent's length
            $this->output($fix, 'Sort indices of child and parent do not match at mmtid=@m (parent=@par).', array('@m' => $this->mmtidLink($r->mmtid), '@par' => $this->mmtidLink($r->parent)));
            $is_bad = TRUE;
          }
          elseif (isset($sibling)) {
            if (substr($r->sort_idx, -Constants::MM_CONTENT_BTOA_CHARS) == substr($sibling->sort_idx, -Constants::MM_CONTENT_BTOA_CHARS)) {
              // Entry has same index as its last sibling
              $this->output($fix, 'Siblings mmtid=@m1 and mmtid=@m2 have the same sort index (their parent=@par).', array('@m1' => $this->mmtidLink($r->mmtid), '@m2' => $this->mmtidLink($sibling->mmtid), '@par' => $this->mmtidLink($r->parent)));
              $is_bad = TRUE;
            }
            else {
              $msg = '';
              if ($sibling->name == Constants::MM_ENTRY_NAME_RECYCLE && $r->name != Constants::MM_ENTRY_NAME_RECYCLE) {
                $msg = 'Entry mmtid=@m1 comes after mmtid=@m2, which is a recycle bin (their parent=@par).';
              }
              elseif ($r->name != Constants::MM_ENTRY_NAME_RECYCLE && !$r->hidden) {
                if ($sibling->hidden) {
                  $msg = 'Entry mmtid=@m1 is not hidden, but it comes after mmtid=@m2 which is (their parent=@par).';
                }
                elseif ($r->weight < $sibling->weight) {
                  $msg = 'Entry mmtid=@m1 has a weight that is lower than mmtid=@m2 (their parent=@par).';
                }
                elseif ($r->weight == $sibling->weight && strcasecmp($r->name, $sibling->name) < 0) {
                  // The simple cases are tested above, but strcasecmp() is not
                  // the same as collation-based comparisons in the DB, so upon
                  // failure, ask the DB if the sort is correct
                  $query = $this->database->select('mm_tree', 't1');
                  $query->join('mm_tree', 't2', 't2.parent = t1.parent');
                  $query->condition('t1.mmtid', $r->mmtid)
                    ->condition('t2.mmtid', $sibling->mmtid)
                    ->addExpression('t1.name < t2.name');
                  if ($query->execute()->fetchField()) {
                    $msg = 'Entry mmtid=@m1 comes after mmtid=@m2, even though it has a name that is earlier alphabetically (their parent=@par).';
                  }
                }
              }

              if ($msg) {
                $this->output($fix, $msg, array('@m1' => $this->mmtidLink($r->mmtid), '@m2' => $this->mmtidLink($sibling->mmtid), '@par' => $this->mmtidLink($r->parent)));
                $is_bad = TRUE;
              }
            }
          }
        }
      }

      if ($is_bad) {
        if ($fix) {
          $this->updateSortQueue($r->parent, TRUE, $parent_mmtids);
        }

        if (empty($skip_bad)) {
          $skip_bad = $r->sort_idx;
        }
      }

      $sibling = $prev = $r;
    }

    // Process the list of potential pages needing update one more time, and
    // finally queue the pages for update.
    $this->updateSortQueue(0, NULL, $parent_mmtids);
    // Now process the updates. We can't use $semaphore_time here because we
    // already have the semaphore.
    mm_content_update_sort_queue();
    // And, finally, release the semaphore.
    mm_content_update_sort_test_semaphore(-1);
    return $this->output($fix, NULL, NULL);
  }

}
