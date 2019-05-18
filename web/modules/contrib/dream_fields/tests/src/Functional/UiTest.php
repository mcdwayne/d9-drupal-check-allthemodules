<?php

namespace Drupal\Tests\dream_fields\Functional;

use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the dream fields UI.
 *
 * @group dream_fields
 */
class UiTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'dream_fields',
    'entity_test',
    'node',
    'field_ui',
  ];

  /**
   * The profile to use.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
    $this->drupalLogin($this->drupalCreateUser([
      'administer content types',
      'administer nodes',
      'bypass node access',
      'administer node fields',
      'access dream fields',
      'use all dream fields',
    ]));
  }

  /**
   * Test the user interface works.
   */
  public function testUi() {
    $this->drupalGet('admin/structure/types/manage/page/fields');
    $this->clickLink('Add field');
    $this->getSession()->getPage()->find('css', '.dream-field')->click();
    $this->submitForm([
      'label' => 'New Field',
    ], 'Create field');
    $this->assertSession()->pageTextContains('Your field is created and ready to be used, you can find more advanced options on this page or use the tabs at the top.');
    $this->drupalGet('node/add/page');
    $this->assertSession()->elementExists('css', 'input[name="field_new_field[0][value]"]');
    $this->assertSession()->pageTextContains('New Field');
  }

}
