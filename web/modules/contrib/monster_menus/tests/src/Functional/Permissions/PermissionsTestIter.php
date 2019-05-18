<?php

namespace Drupal\Tests\monster_menus\Functional\Permissions;

use Drupal\monster_menus\Constants;
use Drupal\monster_menus\GetTreeIterator;

class PermissionsTestIter extends GetTreeIterator {
  private $path, $stats, $pindex;
  /** @var PermissionsTest */
  private $test;

  public function __construct($path, $stats, $test) {
    $this->path = $path;
    $this->stats = $stats;
    $this->pindex = count($path) - 1;
    $this->test = $test;
  }

  /**
   * {@inheritdoc}
   */
  public function iterate($item) {
    $this->path = array_slice($this->path, 0, $this->pindex + $item->level);
    $this->path[] = $item->name;
    $print_path = implode('/', $this->path);

    if (isset($this->test->baseline[$this->stats['label']][$print_path])) {
      foreach (array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ, Constants::MM_PERMS_IS_USER, Constants::MM_PERMS_IS_GROUP, Constants::MM_PERMS_IS_RECYCLE_BIN, Constants::MM_PERMS_IS_RECYCLED) as $mode) {
        if ($mode != Constants::MM_PERMS_APPLY || $this->path[0] != Constants::MM_ENTRY_NAME_GROUPS) {
          $this->stats['count']++;
          if (!isset($this->test->baseline[$this->stats['label']][$print_path]['page'][$mode])) {
            $this->stats['fail'][] = $this->test->failed("Undefined baseline entry ['" . $this->stats['label'] . "']['$print_path']['page'][$mode]. Re-run this test using PermissionsTest::wantBaseline=TRUE and use the output to rewrite " . PermissionsTest::baselineFilename . '.');
          }
          else {
            $old = $this->test->baseline[$this->stats['label']][$print_path]['page'][$mode];
            if ($item->perms[$mode] !== $old) {
              $this->stats['fail'][] = $this->test->failed("($item->mmtid) $print_path: $mode", $old);
            }
            else {
              $this->test->assertTrue(TRUE, "($item->mmtid) $print_path: $mode");
            }
          }
        }
      }
    }

    return 1;   // continue
  }

}
