<?php

namespace Drupal\Tests\recipe\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;

/**
 * Tests the functionality of the Recipe content type and Recipe blocks.
 *
 * @group recipe
 */
class RecipeNodeTest extends RecipeTestBase {

  /**
   * Creates a recipe node using the node form and tests the display.
   */
  public function testRecipeContent() {
    // Generate values for our test node.
    $title = $this->randomMachineName(16);
    $description = $this->randomMachineName(255);
    $yield_amount = 5;
    $yield_unit = $this->randomMachineName(10);
    $source = 'http://www.example.com';
    $notes = $this->randomMachineName(255);
    $instructions = $this->randomMachineName(255);
    $preptime = 60;
    $cooktime = 135;

    // Ingredient with quantity == 1 and unit tablespoon with note.
    $ing_0_quantity = 1;
    $ing_0_unit = 'tablespoon';
    $ing_0_name = $this->randomMachineName(16);
    $ing_0_note = $this->randomMachineName(16);

    $edit = [
      'title[0][value]' => $title,
      'recipe_description[0][value]' => $description,
      'recipe_yield_amount[0][value]' => $yield_amount,
      'recipe_yield_unit[0][value]' => $yield_unit,
      'recipe_source[0][value]' => $source,
      'recipe_notes[0][value]' => $notes,
      'recipe_instructions[0][value]' => $instructions,
      'recipe_prep_time[0][value]' => $preptime,
      'recipe_cook_time[0][value]' => $cooktime,
      'recipe_ingredient[0][quantity]' => $ing_0_quantity,
      'recipe_ingredient[0][unit_key]' => $ing_0_unit,
      'recipe_ingredient[0][target_id]' => $ing_0_name,
      'recipe_ingredient[0][note]' => $ing_0_note,
    ];

    // Post the values to the node form.
    $this->drupalPostForm('node/add/recipe', $edit, 'Save');
    $this->assertSession()->pageTextContains(new FormattableMarkup('Recipe @title has been created.', ['@title' => $title]));

    // Check the page for the recipe content.
    $this->assertSession()->responseContains($description);
    $this->assertSession()->pageTextContains(new FormattableMarkup('@amount @unit', ['@amount' => $yield_amount, '@unit' => $yield_unit]));
    $this->assertSession()->responseContains('<a href="http://www.example.com">http://www.example.com</a>');
    $this->assertSession()->responseContains($notes);
    $this->assertSession()->responseContains($instructions);
    $this->assertSession()->pageTextContains('1 hour');
    $this->assertSession()->pageTextContains('2 hours, 15 minutes');
    $this->assertSession()->pageTextContains('3 hours, 15 minutes');

    $this->assertSession()->pageTextContains('1 T');
    $this->assertSession()->pageTextContains(new FormattableMarkup('@name (@note)', ['@name' => $ing_0_name, '@note' => $ing_0_note]));

    // Check the page HTML for the recipe RDF properties.
    $properties = [
      'schema:Recipe',
      'schema:name',
      'schema:recipeInstructions',
      'schema:recipeIngredient',
      'schema:description',
      'schema:prepTime',
      'schema:cookTime',
      'schema:totalTime',
      'schema:recipeYield',
    ];
    foreach ($properties as $property) {
      $this->assertSession()->responseContains($property);
    }

    // Check the page HTML for the ISO 8601 recipe durations.
    $durations = [
      'prep_time' => 'PT1H',
      'cook_time' => 'PT2H15M',
      'total_time' => 'PT3H15M',
    ];
    foreach ($durations as $duration) {
      $this->assertSession()->responseContains($duration);
    }

    // Check for the breadcrumb.
    $expected_breadcrumb = [];
    $expected_breadcrumb[] = Url::fromRoute('<front>')->toString();
    $expected_breadcrumb[] = Url::fromRoute('recipe.landing_page')->toString();

    // Fetch links in the current breadcrumb.
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $got_breadcrumb = [];
    foreach ($links as $link) {
      $got_breadcrumb[] = (string) $link->getAttribute('href');
    }

    // Compare expected and got breadcrumbs.
    $this->assertSame($expected_breadcrumb, $got_breadcrumb, 'The breadcrumb is correctly displayed on the page.');
  }

  /**
   * Tests the visibility of the Recipe pseudo-fields.
   */
  public function testPseudoFields() {
    // Create a node with values in all of the pseudo-field sub-fields.
    $edit = [
      'title[0][value]' => $this->randomMachineName(16),
      'recipe_yield_amount[0][value]' => 1,
      'recipe_yield_unit[0][value]' => $this->randomMachineName(16),
      'recipe_prep_time[0][value]' => 1,
      'recipe_cook_time[0][value]' => 1,
    ];
    $this->drupalPostForm('node/add/recipe', $edit, 'Save');

    // Verify that the pseudo-fields are shown on the node view.
    $this->assertSession()->pageTextContains('Yield');
    $this->assertSession()->pageTextContains('Total time');

    // Create a node with no value in the yield_amount and a value in only one
    // time field.
    $edit = [
      'title[0][value]' => $this->randomMachineName(16),
      'recipe_yield_unit[0][value]' => $this->randomMachineName(16),
      'recipe_cook_time[0][value]' => 1,
    ];
    $this->drupalPostForm('node/add/recipe', $edit, 'Save');

    // Verify that the pseudo-fields are not shown on the node view.
    $this->assertSession()->pageTextNotContains('Yield');
    $this->assertSession()->pageTextNotContains('Total time');

    // Create a node with no values in time fields.
    $edit = [
      'title[0][value]' => $this->randomMachineName(16),
    ];
    $this->drupalPostForm('node/add/recipe', $edit, 'Save');

    // Verify that the pseudo-fields are not shown on the node view.
    $this->assertSession()->pageTextNotContains('Yield');
    $this->assertSession()->pageTextNotContains('Total time');
  }

}
