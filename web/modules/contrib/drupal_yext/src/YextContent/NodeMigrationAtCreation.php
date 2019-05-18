<?php

namespace Drupal\drupal_yext\YextContent;

/**
 * Migrator of data which just arrived from Yext.
 *
 * Contrary to NodeMigrationOnSave, this will only migrate if the last update
 * date and time are different. This is because we are assuming that
 * everything was migrated properly last time around.
 *
 * (The NodeMigrationOnSave is also used in hook_presave(), meaning that if
 * new field mapping was added after an initial migration, we want to
 * re-migrate new fields even if the update times are identical on the
 * source -- which in the case of NodeMigrationOnSave comes from its Yext Raw
 * data field -- and destination, which generally will be the case unless
 * someone manually changed the update time).
 */
class NodeMigrationAtCreation extends NodeMigrationOnSave {

  /**
   * {@inheritdoc}
   */
  public function migrate() : bool {
    $to = $this->to;
    $from = $this->from;
    if ($to->getYextLastUpdate() != $from->getYextLastUpdate()) {
      $to->setYextRawData($from->getYextRawData());
      $to->save();
      return TRUE;
    }
    return FALSE;
  }

}
