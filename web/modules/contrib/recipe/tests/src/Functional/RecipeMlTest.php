<?php

namespace Drupal\Tests\recipe\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\views\Views;

/**
 * Tests the RecipeML Views style plugin.
 *
 * @group recipe
 */
class RecipeMlTest extends RecipeTestBase {

  /**
   * Tests the display of Recipe nodes using the RecipeML Views style plugin.
   */
  public function testViewsStyle() {
    // Generate values for our test node.
    $title = $this->randomMachineName(16);
    $description = $this->randomMachineName(255);
    $yield_amount = 5;
    $yield_unit = $this->randomMachineName(10);
    $source = $this->randomMachineName(255);
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

    // Enable the RecipeML view.
    $view = Views::getView('recipeml');
    $view->initDisplay();
    $view->storage->enable()->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    // Check the page for the recipe content.
    $this->drupalGet('node/1/recipeml');
    $driver = $this->getSession()->getDriver();
    $result = $driver->getAttribute('//recipe', 'xml:lang');
    $this->assertEquals($result, 'en', 'Found the xml:lang attribute.');
    $result = $driver->getText("//recipe/head/title");
    $this->assertEquals($result, $title, 'Found the recipe title.');
    $result = $driver->getText("//recipe/head/source");
    $this->assertEquals($result, $source, 'Found the recipe source.');
    $result = $driver->getText("//recipe/head/preptime[@type='Preparation time']/time/qty");
    $this->assertEquals($result, 60, 'Found the recipe preparation time.');
    $result = $driver->getText("//recipe/head/preptime[@type='Cooking time']/time/qty");
    $this->assertEquals($result, 135, 'Found the recipe cooking time.');
    $result = $driver->getText("//recipe/head/preptime[@type='Total time']/time/qty");
    $this->assertEquals($result, 195, 'Found the recipe total time.');
    $result = $driver->getText("//recipe/head/yield/qty");
    $this->assertEquals($result, $yield_amount, 'Found the recipe yield.');
    $result = $driver->getText("//recipe/head/yield/unit");
    $this->assertEquals($result, $yield_unit, 'Found the recipe yield unit.');
    $result = $driver->getText("//recipe/description");
    $this->assertEquals($result, $description, 'Found the recipe description.');
    $result = $driver->getText("//recipe/ingredients/ing/amt/qty");
    $this->assertEquals($result, $ing_0_quantity, 'Found the ingredient 0 quantity');
    $result = $driver->getText("//recipe/ingredients/ing/amt/unit");
    $this->assertEquals($result, 'T', 'Found the ingredient 0 unit');
    $result = $driver->getText("//recipe/ingredients/ing/item");
    $this->assertEquals($result, $ing_0_name, 'Found the ingredient 0 name');
    $result = $driver->getText("//recipe/ingredients/ing/prep");
    $this->assertEquals($result, $ing_0_note, 'Found the ingredient 0 note');
    $result = $driver->getText("//recipe/directions");
    $this->assertEquals($result, $instructions, 'Found the recipe instructions');
  }

}
