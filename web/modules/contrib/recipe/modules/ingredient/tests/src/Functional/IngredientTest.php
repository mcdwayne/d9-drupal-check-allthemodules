<?php

namespace Drupal\Tests\ingredient\Functional;

use Drupal\Core\URL;
use Drupal\ingredient\Entity\Ingredient;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Ingredient CRUD functions.
 *
 * @group recipe
 */
class IngredientTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'ingredient', 'field_ui', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Install Drupal.
    parent::setUp();
    // Add the system menu blocks to appropriate regions.
    $this->setupIngredientMenus();
    // Add the system breadcrumb block.
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Set up menus and tasks in their regions.
   */
  protected function setupIngredientMenus() {
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'secondary_menu']);
    $this->drupalPlaceBlock('local_actions_block', ['region' => 'content']);
    $this->drupalPlaceBlock('page_title_block', ['region' => 'content']);
  }

  /**
   * @covers \Drupal\ingredient\IngredientListBuilder
   * @covers \Drupal\ingredient\Form\IngredientForm
   * @covers \Drupal\ingredient\IngredientBreadcrumbBuilder
   * @covers \Drupal\ingredient\Form\IngredientDeleteForm
   */
  public function testIngredient() {
    $web_user = $this->drupalCreateUser([
      'add ingredient',
      'edit ingredient',
      'view ingredient',
      'delete ingredient',
      'administer ingredient',
      'administer ingredient display',
      'administer ingredient fields',
      'administer ingredient form display',
    ]);

    $this->drupalLogin($web_user);

    // Web_user user has the right to view listing.
    $this->drupalGet('admin/content/ingredient');

    // WebUser can add entity content.
    $this->assertSession()->linkExists('Add Ingredient');

    $this->clickLink('Add Ingredient');

    $this->assertSession()->fieldExists('name[0][value]');

    // Post content, save an instance. Go back to list after saving.
    $edit = [
      'name[0][value]' => 'test name',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Entity listed.
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkExists('Delete');

    $this->clickLink('test name');

    // Entity shown.
    $this->assertSession()->pageTextContains('test name');
    $this->assertSession()->pageTextContains('Edit');
    $this->assertSession()->pageTextContains('Delete');

    // Check for the breadcrumb.
    $expected_breadcrumb = [];
    $expected_breadcrumb[] = URL::fromRoute('<front>')->toString();
    $expected_breadcrumb[] = URL::fromRoute('ingredient.landing_page')->toString();

    // Fetch links in the current breadcrumb.
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $got_breadcrumb = [];
    foreach ($links as $link) {
      $got_breadcrumb[] = $link->getAttribute('href');
    }

    // Compare expected and got breadcrumbs.
    $this->assertSame($expected_breadcrumb, $got_breadcrumb, 'The breadcrumb is correctly displayed on the page.');

    // Delete the entity.
    $this->clickLink('Delete');

    // Confirm deletion.
    $this->assertSession()->linkExists('Cancel');
    $this->drupalPostForm(NULL, [], 'Delete');

    // Back to list, must be empty.
    $this->assertSession()->pageTextNotContains('test name');

    // Settings page.
    $this->drupalGet('admin/structure/ingredient_settings');
    $this->assertSession()->pageTextContains('Ingredient Settings');

    // Make sure the field manipulation links are available.
    $this->assertSession()->linkExists('Settings');
    $this->assertSession()->linkExists('Manage fields');
    $this->assertSession()->linkExists('Manage form display');
    $this->assertSession()->linkExists('Manage display');
  }

}
