<?php

namespace Drupal\Tests\recipe\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\views\Views;

/**
 * Tests the plain text Views style plugin.
 *
 * @group recipe
 *
 * @todo Add tests for the style options and adding other fields.
 * @todo Find out why there is a dependency on the Locale module and eliminate
 *   it. Without Locale an exception is thrown on line 70 when getting the page.
 */
class PlainTextStyleTest extends RecipeTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'locale', 'recipe', 'views'];

  /**
   * Tests the display of Recipe nodes using the plain text Views style plugin.
   */
  public function testViewsStyle() {
    // Generate values for our test node.
    $title = $this->randomMachineName(16);
    $description = $this->randomMachineName(255);
    $notes = $this->randomMachineName(255);
    $instructions = $this->randomMachineName(255);

    // Ingredient with quantity == 1 and unit tablespoon with note.
    $ing_0_quantity = 1;
    $ing_0_unit = 'tablespoon';
    $ing_0_name = $this->randomMachineName(16);
    $ing_0_note = $this->randomMachineName(16);

    $edit = [
      'title[0][value]' => $title,
      'recipe_description[0][value]' => $description,
      'recipe_notes[0][value]' => $notes,
      'recipe_instructions[0][value]' => $instructions,
      'recipe_ingredient[0][quantity]' => $ing_0_quantity,
      'recipe_ingredient[0][unit_key]' => $ing_0_unit,
      'recipe_ingredient[0][target_id]' => $ing_0_name,
      'recipe_ingredient[0][note]' => $ing_0_note,
    ];

    // Post the values to the node form.
    $this->drupalPostForm('node/add/recipe', $edit, 'Save');
    $this->assertSession()->pageTextContains(new FormattableMarkup('Recipe @title has been created.', ['@title' => $title]));

    // Enable the plain text view.
    $view = Views::getView('recipe_plain_text');
    $view->initDisplay();
    $view->storage->enable()->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    // Check the page for the recipe content.
    $this->drupalGet('node/1/plain-text');
    $this->assertSession()->responseContains($title);
    $this->assertSession()->responseContains($description);
    $this->assertSession()->responseContains($notes);
    $this->assertSession()->responseContains($instructions);
    $this->assertSession()->responseContains(new FormattableMarkup('1 T @name (@note)', ['@name' => $ing_0_name, '@note' => $ing_0_note]));
  }

}
