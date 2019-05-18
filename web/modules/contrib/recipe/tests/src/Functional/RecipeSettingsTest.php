<?php

namespace Drupal\Tests\recipe\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of the Recipe module settings.
 *
 * @group recipe
 */
class RecipeSettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['recipe'];

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

    // Create and log in the admin user with Recipe content permissions.
    $permissions = [
      'create recipe content',
      'edit any recipe content',
      'administer content types'
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the pseudo-field label settings.
   */
  public function testPseudoFieldLabels() {
    $title = $this->randomMachineName(16);
    $yield_amount = 5;
    $yield_unit = $this->randomMachineName(10);
    $preptime = 60;
    $cooktime = 135;

    $edit = [
      'title[0][value]' => $title,
      'recipe_yield_amount[0][value]' => $yield_amount,
      'recipe_yield_unit[0][value]' => $yield_unit,
      'recipe_prep_time[0][value]' => $preptime,
      'recipe_cook_time[0][value]' => $cooktime,
    ];

    // Post the values to the node form.
    $this->drupalPostForm('node/add/recipe', $edit, 'Save');
    $this->assertSession()->pageTextContains(new FormattableMarkup('Recipe @title has been created.', ['@title' => $title]));

    // Check for the default pseudo-field labels.
    $this->assertSession()->pageTextContains('Total time');
    $this->assertSession()->pageTextContains('Yield');

    // Alter the pseudo-field labels.
    $total_time_label = $this->randomMachineName(20);
    $yield_label = $this->randomMachineName(20);
    $edit = [
      'recipe_total_time_label' => $total_time_label,
      'recipe_yield_label' => $yield_label,
    ];

    // Post the values to the settings form.
    $this->drupalPostForm('admin/structure/types/manage/recipe', $edit, 'Save content type');
    $this->assertSession()->pageTextContains('The content type Recipe has been updated.');

    // Check the node display for the new labels.
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains($total_time_label);
    $this->assertSession()->pageTextContains($yield_label);

    // Alter the pseudo-field label displays.
    $edit = [
      'recipe_total_time_label_display' => 'hidden',
      'recipe_yield_label_display' => 'hidden',
    ];

    // Post the values to the settings form.
    $this->drupalPostForm('admin/structure/types/manage/recipe', $edit, 'Save content type');
    $this->assertSession()->pageTextContains('The content type Recipe has been updated.');

    // Check the node display for the new labels.
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextNotContains($total_time_label);
    $this->assertSession()->pageTextNotContains($yield_label);
  }

}
