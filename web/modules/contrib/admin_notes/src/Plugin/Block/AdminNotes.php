<?php

namespace Drupal\admin_notes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Admin Notes' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "admin_notes",
 *   admin_label = @Translation("Admin Notes")
 * )
 */
class AdminNotes extends BlockBase {

  /**
   *
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access admin notes');
  }

  /**
   *
   */
  public function build() {
    $build = array();
    $current_path = \Drupal::service('path.current')->getPath();
    $is_front_page = \Drupal::service('path.matcher')->isFrontPage();
    $current_path = $is_front_page ? '/' : $current_path;
    $note = db_query("SELECT note FROM {admin_notes} WHERE path=:path", array(':path' => $current_path))->fetchField();
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\admin_notes\Form\AdminNotesForm', $note);
    return $build;
  }

}
