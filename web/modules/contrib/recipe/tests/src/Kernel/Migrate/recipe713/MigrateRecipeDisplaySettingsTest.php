<?php

namespace Drupal\Tests\recipe\Kernel\Migrate\recipe713;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests migration of Recipe's ingredient variables to configuration.
 *
 * @group recipe
 */
class MigrateRecipeDisplaySettingsTest extends MigrateRecipe713TestBase {

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
    $this->executeMigrations(['recipe713_ingredient_field_display']);
  }

  /**
   * Tests migration of ingredient field instance variables.
   */
  public function testMigration() {
    $display = EntityViewDisplay::load('node.recipe.default');
    $field_component = $display->getComponent('recipe_ingredient');
    $this->assertSame($field_component['settings']['fraction_format'], '{%d }%d/%d');
    $this->assertSame($field_component['settings']['unit_display'], 1);
  }

}
