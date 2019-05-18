<?php

namespace Drupal\monster_menus\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;
use Drupal\monster_menus\Constants;

/**
 * @MigrateDestination(
 *   id = "entity:mm_tree"
 * )
 */
class MMTree extends EntityContentBase {

  const MM_ENTRY_NAME_STUBS = '.MigrationStubs';
  const MM_ENTRY_ALIAS_STUBS = 'migration-stubs';

  /**
   * {@inheritdoc}
   */
  protected function processStubRow(Row $row) {
    static $stubs_page;

    parent::processStubRow($row);
    // Create a parent page to hold stubs, if not yet present.
    if (!isset($stubs_page)) {
      // Intentionally use MM_HOME_MMTID_DEFAULT instead of mm_home_mmtid(), since
      // there might not be a '.System' page there.
      $system = mm_content_get(array('parent' => Constants::MM_HOME_MMTID_DEFAULT, 'name' => Constants::MM_ENTRY_NAME_SYSTEM));
      if (!$system) {
        throw new \Exception('Could not find ' . Constants::MM_ENTRY_NAME_SYSTEM . ' page at the root level');
      }

      if ($tree = mm_content_get(array('parent' => $system[0]->mmtid, 'name' => static::MM_ENTRY_NAME_STUBS))) {
        $stubs_page = $tree[0]->mmtid;
      }
      else {
        try {
          $stubs_page = mm_content_insert_or_update(TRUE, $system[0]->mmtid, array(
            'name' => static::MM_ENTRY_NAME_STUBS,
            'alias' => static::MM_ENTRY_ALIAS_STUBS,
            'hidden' => TRUE,
          ));
        }
        catch (\Exception $e) {
        }

        if (empty($stubs_page)) {
          throw new \Exception('Could not create the ' . static::MM_ENTRY_NAME_STUBS . ' page in ' . Constants::MM_ENTRY_NAME_SYSTEM);
        }
      }
    }

    $row->setDestinationProperty('name', t('Stub @number', ['@number' => $row->getDestinationProperty('mmtid')])->render());
    $row->setDestinationProperty('uid', 1);
    $row->setDestinationProperty('parent', $stubs_page);
  }

}
