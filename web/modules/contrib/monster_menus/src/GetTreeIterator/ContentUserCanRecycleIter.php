<?php
namespace Drupal\monster_menus\GetTreeIterator;

use Drupal\Core\Session\AccountInterface;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\GetTreeIterator;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class ContentUserCanRecycleIter extends GetTreeIterator {

  /**
   * @var bool
   *   TRUE if the bin is readable by the user.
   */
  public $readable;

  /**
   * @var bool
   *   TRUE if the bin is writable by the user.
   */
  public $writable;

  /**
   * @var bool
   *   TRUE if the bin is emptyable by the user.
   */
  public $emptyable;

  /**
   * @var string
   *   Mode to test. See below.
   */
  private $mode;

  /**
   * @var AccountInterface|NULL
   *   User object to test against.
   */
  private $usr;

  /**
   * ContentUserCanRecycleIter constructor.
   *
   * @param string $mode
   *   If set, return whether or not the user can perform that action
   *   (MM_PERMS_READ (read), MM_PERMS_WRITE (delete)). Otherwise, return an
   *   array containing these elements with either TRUE or FALSE values. There
   *   is also a special mode, MM_PERMS_IS_EMPTYABLE, which returns TRUE if the
   *   user has permission to empty the entire bin (i.e.: has write on
   *   everything in it.)
   * @param AccountInterface|NULL $usr
   *   User object to test against.
   */
  public function __construct($mode, $usr) {
    $this->readable = FALSE;
    $this->writable = FALSE;
    $this->emptyable = TRUE;
    $this->mode = $mode;
    $this->usr = $usr;
  }

  /**
   * @inheritdoc
   */
  public function iterate($item) {
    if ($item->name == Constants::MM_ENTRY_NAME_RECYCLE) { // the bin exists
      // Make sure the entity info for 'node' is available. If this code
      // is called during hook_boot(), it might not be.
      if (\Drupal::entityTypeManager()->getDefinition('node')) {
        $this->writable = TRUE;                // no nodes: default to writable
        if (!empty($item->perms[Constants::MM_PERMS_ADMIN]) || $item->perms[Constants::MM_PERMS_WRITE]) $this->readable = TRUE;
        /** @var NodeInterface $node */
        foreach (Node::loadMultiple(mm_content_get_nids_by_mmtid($item->mmtid)) as $node) {
          $this->writable = FALSE;             // it's not empty, so not writable
          if ($node->id()) {
            if ($node->access('delete', $this->usr)) {
              $this->readable = TRUE;          // the bin is readable
              if ($this->mode != Constants::MM_PERMS_IS_EMPTYABLE) return 0;  // skip everything else
            }
            elseif ($this->mode == Constants::MM_PERMS_IS_EMPTYABLE) {
              $this->emptyable = FALSE;
              return 0;
            }
          }
        }
        return 1;
      }
    }
    $this->writable = FALSE;                 // it's not empty, so not writable

    if ($item->perms[Constants::MM_PERMS_WRITE]) {      // if the user can write to at least one kid
      $this->readable = TRUE;                // the bin is readable
      if ($this->mode != Constants::MM_PERMS_IS_EMPTYABLE) return 0;  // skip everything else
    }
    elseif ($this->mode == Constants::MM_PERMS_IS_EMPTYABLE) {        // looking for emptyable status
      $this->emptyable = FALSE;              // can't write, so not emptyable
      return 0;
    }

    return -1;                               // skip this node and kids; we only care about sibs
  }

}
