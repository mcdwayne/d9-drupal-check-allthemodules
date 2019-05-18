<?php

namespace Drupal\Tests\ingredient\Kernel;

use Drupal\ingredient\Entity\Ingredient;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Ingredient entity settings.
 *
 * @group recipe
 */
class IngredientSettingsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ingredient'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('ingredient');
    $this->installConfig('ingredient');
  }

  /**
   * Tests the ingredient name normalization setting.
   */
  public function testIngredientNormalization() {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('ingredient.settings');

    // Verify that ingredient normalization is off by default.
    $this->assertEquals('0', $config->get('ingredient_name_normalize'));

    // Add a new ingredient with capitalized characters in the name.
    $first_ingredient = Ingredient::create(['name' => 'TeSt InGrEdIeNt 1']);
    $first_ingredient->save();
    // Verify that the name did not change on save.
    $this->assertEquals('TeSt InGrEdIeNt 1', $first_ingredient->label());

    // Turn ingredient normalization on.
    $config->set('ingredient_name_normalize', '1')->save();

    // Add a new ingredient with capitalized characters in the name.
    $second_ingredient = Ingredient::create(['name' => 'TeSt InGrEdIeNt 2']);
    $second_ingredient->save();
      // Verify that the name was normalized on save.
    $this->assertEquals('test ingredient 2', $second_ingredient->label());

    // Add a new ingredient with capitalized characters and an &reg; symbol in
    // the name.
    $third_ingredient = Ingredient::create(['name' => 'TeSt InGrEdIeNt 3 ®']);
    $third_ingredient->save();
    // Verify that the name was not normalized on save.
    $this->assertEquals('TeSt InGrEdIeNt 3 ®', $third_ingredient->label());

    // Turn ingredient normalization back off.

    $config->set('ingredient_name_normalize', '0')->save();

    // Add a new ingredient with capitalized characters in the name.
    $fourth_ingredient = Ingredient::create(['name' => 'TeSt InGrEdIeNt 4']);
    $fourth_ingredient->save();
    // Verify that the name did not change on save.
    $this->assertEquals('TeSt InGrEdIeNt 4', $fourth_ingredient->label());
  }

}
