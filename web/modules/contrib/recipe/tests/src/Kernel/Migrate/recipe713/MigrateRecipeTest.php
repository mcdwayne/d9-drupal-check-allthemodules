<?php

namespace Drupal\Tests\recipe\Kernel\Migrate\recipe713;

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Tests migration of recipe node fields.
 *
 * @group recipe
 */
class MigrateRecipeTest extends MigrateRecipe713TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'ingredient',
    'language',
    'menu_ui',
    'node',
    'rdf',
    'recipe',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('ingredient');
    $this->installEntitySchema('node');
    $this->installConfig(static::$modules);
    $this->installSchema('node', ['node_access']);
    $this->executeMigrations([
      'language',
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_node',
      'd7_node_translation',
      'recipe713_ingredient',
      'recipe713_recipe',
      'recipe713_recipe_translation',
    ]);
  }

  /**
   * Asserts various aspects of a recipe.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The imported recipe node.
   * @param \stdClass $recipe
   *   A stdClass object with the following properties:
   *   - $recipe->description: A short description of the recipe.
   *   - $recipe->ingredients: An array of ingredients for the recipe.
   *   - $recipe->instructions: Instructions on how to prepare the recipe.
   *   - $recipe->notes: Other notes about this recipe.
   *   - $recipe->preptime: The preparation time in minutes.
   *   - $recipe->source: Who deserves credit for this recipe.
   *   - $recipe->yield: A measure of how much this recipe will produce.
   *   - $recipe->yield_unit: The unit that $yield is expressed in.
   */
  protected function assertRecipeFields(NodeInterface $node, \stdClass $recipe) {
    $this->assertEquals($recipe->cooktime, $node->recipe_cook_time->value);
    $this->assertSame($recipe->description, $node->recipe_description->value);
    $this->assertSame($recipe->instructions, $node->recipe_instructions->value);
    $this->assertSame($recipe->notes, $node->recipe_notes->value);
    $this->assertEquals($recipe->preptime, $node->recipe_prep_time->value);
    $this->assertSame($recipe->source, $node->recipe_source->value);
    $this->assertEquals($recipe->yield, $node->recipe_yield_amount->value);
    $this->assertSame($recipe->yield_unit, $node->recipe_yield_unit->value);

    for ($i = 0; $i < count($recipe->ingredients); $i++) {
      $this->assertEquals($recipe->ingredients[$i]->quantity, $node->recipe_ingredient[$i]->quantity);
      $this->assertEquals($recipe->ingredients[$i]->unit_key, $node->recipe_ingredient[$i]->unit_key);
      $this->assertEquals($recipe->ingredients[$i]->ingredient_id, $node->recipe_ingredient[$i]->target_id);
      $this->assertEquals($recipe->ingredients[$i]->note, $node->recipe_ingredient[$i]->note);
    }
  }

  /**
   * Tests the Drupal 7 recipe to Drupal 8 migration.
   */
  public function testRecipeFields() {
    $database_connection = Database::getConnection('default', 'migrate');

    $recipes = $database_connection
      ->select('recipe', 'r')
      ->fields('r')
      ->execute()
      ->fetchAll();

    foreach ($recipes as &$source) {
      // Get a list of ingredient IDs from the old database.
      $source->ingredients = $database_connection->select('recipe_node_ingredient', 'rni')
        ->fields('rni')
        ->condition('rni.nid', $source->nid)
        ->execute()
        ->fetchAll();
    }

    $node = Node::load(1);
    $this->assertRecipeFields($node, array_shift($recipes));

    // Verify the fields of an English-language recipe.
    $node = Node::load(2);
    $this->assertRecipeFields($node, array_shift($recipes));
    $this->assertTrue($node->hasTranslation('is'), 'Node 2 has an Islandic translation');

    // Verify the fields of a Islandic-language recipe that was translated from
    // node 2.
    $translation = $node->getTranslation('is');
    $this->assertRecipeFields($translation, array_shift($recipes));
  }

}
