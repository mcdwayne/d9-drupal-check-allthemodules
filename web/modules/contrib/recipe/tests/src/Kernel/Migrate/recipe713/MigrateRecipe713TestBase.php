<?php

namespace Drupal\Tests\recipe\Kernel\Migrate\recipe713;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Provides a base class for Recipe migrations from Recipe 7.x-1.3.
 */
abstract class MigrateRecipe713TestBase extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/recipe713.php';
  }

}
