<?php

namespace Drupal\Tests\recipe\Kernel\Migrate\recipe713;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests migration of Recipe's ingredient variables to configuration.
 *
 * @group recipe
 */
class MigrateRecipeFieldSettingsTest extends MigrateRecipe713TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ingredient', 'node', 'rdf', 'recipe', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('ingredient');
    $this->installEntitySchema('node');
    $this->installConfig(static::$modules);
    $this->executeMigrations(['recipe713_ingredient_field_instance']);
  }

  /**
   * Tests migration of ingredient field instance variables.
   */
  public function testMigration() {
    $field_instance = FieldConfig::load('node.recipe.recipe_ingredient');
    $this->assertSame($field_instance->getSetting('default_unit'), 'cup');
  }

}
