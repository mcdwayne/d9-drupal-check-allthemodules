<?php

namespace Drupal\Tests\ingredient\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of the ingredient field.
 *
 * @group recipe
 */
class IngredientFieldTest extends BrowserTestBase {

  use IngredientTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_ui', 'ingredient', 'node'];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a new content type for testing.
    $this->ingredientCreateContentType();

    // Create and log in the admin user.
    $permissions = [
      'create test_bundle content',
      'access content',
      'administer node fields',
      'administer node display',
      'add ingredient',
      'view ingredient',
      'administer site configuration',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests adding data with the ingredient field.
   */
  public function testIngredientField() {
    $display_settings = [
      'fraction_format' => '{%d }%d/%d',
    ];
    $this->createIngredientField([], [], [], $display_settings);

    $test_ingredients = [];

    // Ingredient with quantity == 1 and unit tablespoon with note.
    $test_ingredients[] = [
      'quantity' => 1,
      'unit_key' => 'tablespoon',
      'name' => $this->randomMachineName(16),
      'note' => $this->randomMachineName(16),
    ];
    // Ingredient with quantity > 1 and unit tablespoon with note.
    $test_ingredients[] = [
      'quantity' => 2,
      'unit_key' => 'tablespoon',
      'name' => $this->randomMachineName(16),
      'note' => $this->randomMachineName(16),
    ];
    // Ingredient with quantity == 0 and unit tablespoon with note.
    $test_ingredients[] = [
      'quantity' => 0,
      'unit_key' => 'tablespoon',
      'name' => $this->randomMachineName(16),
      'note' => $this->randomMachineName(16),
    ];
    // Ingredient without note.
    $test_ingredients[] = [
      'quantity' => 1,
      'unit_key' => 'tablespoon',
      'name' => $this->randomMachineName(16),
      'note' => '',
    ];
    // Ingredient with unit that has no abbreviation.
    $test_ingredients[] = [
      'quantity' => 10,
      'unit_key' => 'unit',
      'name' => $this->randomMachineName(16),
      'note' => $this->randomMachineName(16),
    ];
    // Ingredient with fractional quantity and unit tablespoon.
    $test_ingredients[] = [
      'quantity' => '1/4',
      'unit_key' => 'tablespoon',
      'name' => $this->randomMachineName(16),
      'note' => '',
    ];
    // Ingredient with mixed fractional quantity and unit tablespoon.
    $test_ingredients[] = [
      'quantity' => '2 2/3',
      'unit_key' => 'tablespoon',
      'name' => $this->randomMachineName(16),
      'note' => '',
    ];

    foreach ($test_ingredients as $ingredient) {
      // Create a new test_bundle node with the ingredient field values.
      $title = $this->randomMachineName(16);
      $edit = [
        'title[0][value]' => $title,
        'field_ingredient[0][quantity]' => $ingredient['quantity'],
        'field_ingredient[0][unit_key]' => $ingredient['unit_key'],
        'field_ingredient[0][target_id]' => $ingredient['name'],
        'field_ingredient[0][note]' => $ingredient['note'],
      ];
      $this->drupalPostForm('node/add/test_bundle', $edit, 'Save');

      // Check for the node title to verify redirection to the node view.
      $this->assertSession()->pageTextContains($title);

      // Check for the presence or absence of the ingredient quantity and unit
      // abbreviation.
      if ($ingredient['quantity'] === 0) {
        // Ingredients with quantities === 0 should not display the quantity or
        // units.
        $this->assertSession()->pageTextNotContains('0 T');
      }
      elseif ($ingredient['unit_key'] == 'unit') {
        $this->assertSession()->responseContains(new FormattableMarkup('<span class="quantity-unit">@quantity</span>', ['@quantity' => $ingredient['quantity']]));
      }
      else {
        $this->assertSession()->pageTextContains(new FormattableMarkup('@quantity T', ['@quantity' => $ingredient['quantity']]));
      }

      // Check for the ingredient name and the presence or absence of the note.
      if ($ingredient['note'] === '') {
        $this->assertSession()->pageTextContains(new FormattableMarkup('@name', ['@name' => $ingredient['name']]));
        $this->assertSession()->pageTextNotContains(new FormattableMarkup('@name (@note)', ['@name' => $ingredient['name'], '@note' => $ingredient['note']]));
      }
      else {
        $this->assertSession()->pageTextContains(new FormattableMarkup('@name (@note)', ['@name' => $ingredient['name'], '@note' => $ingredient['note']]));
      }
    }
  }

  /**
   * Tests ingredient formatter settings.
   *
   * @todo Add assertions for singular/plural unit full names.
   */
  public function testIngredientFormatterSettings() {
    $this->createIngredientField();

    // Verify that the ingredient entity link display is turned off by default.
    $this->drupalGet('admin/structure/types/manage/test_bundle/display');
    $this->assertSession()->pageTextContains('Link to ingredient: No');

    $edit = [
      'title[0][value]' => $this->randomMachineName(16),
      'field_ingredient[0][quantity]' => 4,
      'field_ingredient[0][unit_key]' => 'tablespoon',
      'field_ingredient[0][target_id]' => 'test ingredient',
      'field_ingredient[0][note]' => '',
    ];

    $this->drupalGet('node/add/test_bundle');
    // Post the values to the node form.
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Verify that the ingredient name is not linked to its entity.
    $this->assertSession()->pageTextContains('4 T');
    $this->assertSession()->pageTextContains('test ingredient');
    $this->assertSession()->linkNotExists('test ingredient', 'Ingredient entity link is not displayed.');

    // Turn ingredient entity link display on.
    $this->updateIngredientField([], [], ['link' => TRUE, 'unit_display' => 1]);

    // Verify that the ingredient entity link display is turned on.
    $this->drupalGet('admin/structure/types/manage/test_bundle/display');
    $this->assertSession()->pageTextContains('Link to ingredient: Yes');

    // Verify that the ingredient name is linked to its entity.
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('4 tablespoons');
    $this->assertSession()->pageTextContains('test ingredient');
    $this->assertSession()->linkExists('test ingredient', 0);
  }

}
