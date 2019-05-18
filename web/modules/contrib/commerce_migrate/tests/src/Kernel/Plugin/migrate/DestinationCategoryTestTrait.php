<?php

namespace Drupal\Tests\commerce_migrate\Kernel\Plugin\migrate;

use Drupal\ban\Plugin\migrate\destination\BlockedIP;
use Drupal\color\Plugin\migrate\destination\Color;
use Drupal\migrate\Plugin\migrate\destination\ComponentEntityDisplayBase;
use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\path\Plugin\migrate\destination\UrlAlias;
use Drupal\shortcut\Plugin\migrate\destination\ShortcutSetUsers;
use Drupal\statistics\Plugin\migrate\destination\NodeCounter;
use Drupal\system\Plugin\migrate\destination\d7\ThemeSettings;
use Drupal\user\Plugin\migrate\destination\UserData;

/**
 * Class DestinationCategoryTestTrait.
 */
trait DestinationCategoryTestTrait {

  /**
   * Asserts that all migrations are tagged as either Configuration or Content.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
   *   The migrations.
   */
  protected function assertCategories(array $migrations) {
    foreach ($migrations as $id => $migration) {
      $object_classes = class_parents($migration->getDestinationPlugin());
      $object_classes[] = get_class($migration->getDestinationPlugin());

      // Ensure that the destination plugin is an instance of at least one of
      // the expected classes.
      if (in_array('Configuration', $migration->getMigrationTags(), TRUE)) {
        $this->assertNotEmpty(array_intersect($object_classes, $this->getConfigurationClasses()), "The migration $id is tagged as Configuration.");
      }
      elseif (in_array('Content', $migration->getMigrationTags(), TRUE)) {
        $this->assertNotEmpty(array_intersect($object_classes, $this->getContentClasses()), "The migration $id is tagged as Content.");
      }
      else {
        $this->fail("The migration $id is not tagged as either 'Content' or 'Configuration'.");
      }
    }
  }

  /**
   * Get configuration classes.
   *
   * Configuration migrations should have a destination plugin that is an
   * instance of one of the following classes.
   *
   * @return array
   *   The configuration class names.
   */
  protected function getConfigurationClasses() {
    return [
      Color::class,
      Config::class,
      EntityConfigBase::class,
      ThemeSettings::class,
      ComponentEntityDisplayBase::class,
      ShortcutSetUsers::class,
    ];
  }

  /**
   * Get content classes.
   *
   * Content migrations should have a destination plugin that is an instance
   * of one of the following classes.
   *
   * @return array
   *   The content class names.
   */
  protected function getContentClasses() {
    return [
      EntityContentBase::class,
      UrlAlias::class,
      BlockedIP::class,
      NodeCounter::class,
      UserData::class,
    ];
  }

}
