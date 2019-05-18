<?php

namespace Drupal\Tests\ingredient\Functional;

use Drupal\ingredient\Entity\Ingredient;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the translatability of Ingredient entities.
 *
 * @group recipe
 */
class IngredientTranslationTest extends BrowserTestBase {

  use IngredientTestTrait;

  /**
   * The content translation manager service.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The router builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The entity definition update manager service.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The langcode of the source language.
   *
   * @var string
   */
  protected $baseLangcode = 'en';

  /**
   * Target langcode for translation.
   *
   * @var string
   */
  protected $translateToLangcode = 'fr';
  /**
   * The node to check the translated value on.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The ingredient that should be translated.
   *
   * @var \Drupal\ingredient\IngredientInterface
   */
  protected $ingredient;

  /**
   * The ingredient in the source language.
   *
   * @var string
   */
  protected $baseIngredientName = 'OriginalIngredientName';

  /**
   * The translated value for the ingredient.
   *
   * @var string
   */
  protected $translatedIngredientName = 'TranslatedIngredientName';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['content_translation', 'ingredient', 'node'];

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

    $this->ingredientCreateContentType();
    $this->setUpLanguages();
    $this->enableTranslation();
    $this->setUpIngredient();
    $this->createIngredientField();
    $this->setUpNode();

    // Create and log in the admin user.
    $permissions = [
      'access content',
      'view ingredient',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests Ingredient translation.
   */
  public function testTranslatedIngredientDisplay() {
    $ingredient_path = 'ingredient/' . $this->ingredient->id();
    $ingredient_translation_path = $this->translateToLangcode . '/' . $ingredient_path;
    $node_path = 'node/' . $this->node->id();
    $node_translation_path = $this->translateToLangcode . '/' . $node_path;

    // Check the Ingredient entity display views for the names.
    $this->drupalGet($ingredient_path);
    $this->assertSession()->pageTextNotContains($this->translatedIngredientName);
    $this->assertSession()->pageTextContains($this->baseIngredientName);
    $this->drupalGet($ingredient_translation_path);
    $this->assertSession()->pageTextContains($this->translatedIngredientName);
    $this->assertSession()->pageTextNotContains($this->baseIngredientName);

    // Check the Node display views for the names.
    $this->drupalGet($node_path);
    $this->assertSession()->pageTextNotContains($this->translatedIngredientName);
    $this->assertSession()->pageTextContains($this->baseIngredientName);
    $this->drupalGet($node_translation_path);
    $this->assertSession()->pageTextContains($this->translatedIngredientName);
    $this->assertSession()->pageTextNotContains($this->baseIngredientName);
  }

  /**
   * Adds additional languages.
   */
  protected function setUpLanguages() {
    ConfigurableLanguage::createFromLangcode($this->translateToLangcode)->save();
    $this->rebuildContainer();
  }

  /**
   * Enables translations where it needed.
   */
  protected function enableTranslation() {
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    $this->getContentTranslationManager()->setEnabled('node', 'test_bundle', TRUE);
    $this->getContentTranslationManager()->setEnabled('ingredient', 'ingredient', TRUE);
    drupal_static_reset();
    $this->getEntityTypeManager()->clearCachedDefinitions();
    $this->getRouterBuilder()->rebuild();
    $this->getEntityDefinitionUpdateManager()->applyUpdates();
  }

  /**
   * Creates a test subject node, with translation.
   */
  protected function setUpNode() {
    $this->node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'test_bundle',
      'field_ingredient' => [
        'quantity' => 1,
        'unit_key' => 'cup',
        'target_id' => $this->ingredient->id(),
        'note' => '',
      ],
      'langcode' => $this->baseLangcode,
    ]);
    $this->node->save();

    $this->node->addTranslation($this->translateToLangcode, $this->node->toArray());
    $this->node->save();
  }

  /**
   * Creates a test subject ingredient, with translation.
   */
  protected function setUpIngredient() {
    $this->ingredient = Ingredient::create(['name' => $this->baseIngredientName]);
    $this->ingredient->save();

    $this->ingredient->addTranslation($this->translateToLangcode, ['name' => $this->translatedIngredientName]);
    $this->ingredient->save();
  }

  /**
   * Gets the content translation manager service.
   *
   * @return \Drupal\content_translation\ContentTranslationManagerInterface
   *   The content translation manager service.
   */
  protected function getContentTranslationManager() {
    if (!$this->contentTranslationManager) {
      $this->contentTranslationManager = $this->container->get('content_translation.manager');
    }

    return $this->contentTranslationManager;
  }

  /**
   * Gets the router builder service.
   *
   * @return \Drupal\Core\Routing\RouteBuilderInterface
   *   The router builder service.
   */
  protected function getRouterBuilder() {
    if (!$this->routerBuilder) {
      $this->routerBuilder = $this->container->get('router.builder');
    }

    return $this->routerBuilder;
  }

  /**
   * Gets the entity definition update manager service.
   *
   * @return \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   *   The entity definition update manager service.
   */
  protected function getEntityDefinitionUpdateManager() {
    if (!$this->entityDefinitionUpdateManager) {
      $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    }

    return $this->entityDefinitionUpdateManager;
  }

}
