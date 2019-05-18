<?php

namespace Drupal\automatic_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Pending database updates checker.
 */
class PendingDbUpdates implements ReadinessCheckerInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function run() {
    $messages = [];

    if ($this->areDbUpdatesPending()) {
      $messages[] = $this->t('There are pending database updates, therefore updates cannot be applied. Please run update.php.');
    }
    return $messages;
  }

  /**
   * Checks if there are pending database updates.
   *
   * @return bool
   *   TRUE if there are pending updates, otherwise FALSE.
   */
  protected function areDbUpdatesPending() {
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    require_once DRUPAL_ROOT . '/core/includes/update.inc';
    drupal_load_updates();
    return (bool) update_get_update_list();
  }

}
