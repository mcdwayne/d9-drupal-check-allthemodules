<?php

namespace Drupal\Tests\ingredient\Kernel\Migrate\recipe713;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Provides a base class for Ingredient migrations from Recipe 7.x-1.3.
 */
abstract class MigrateIngredient713TestBase extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/ingredient713.php';
  }

}
