<?php

namespace Drupal\Tests\custom_tokens\Functional;

use Drupal\local_testing\LocalTestingTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * The token UI test.
 */
class TokenUiTest extends BrowserTestBase {

  use LocalTestingTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'custom_tokens',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->createUser([
      'administer custom tokens',
      'access administration pages',
    ]));
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test creating token entities from the UI.
   */
  public function testTokenUi() {
    $this->drupalGet('admin/structure');
    $this->clickLink('Custom Tokens');
    $this->clickLink('Add Token');

    $this->submitForm([
      'label' => 'Foo',
      'id' => 'foo',
      'tokenName' => 'Bar',
      'tokenValue' => 'Baz',
    ], 'Save');

    $this->assertSession()->pageTextContains('Custom token was successfully saved.');

    $this->assertSession()->elementContains('css', 'table', 'Foo');
    $this->assertSession()->elementContains('css', 'table', '[Bar]');
    $this->assertSession()->elementContains('css', 'table', 'Baz');

    $this->clickLink('Edit');

    $this->assertSession()->fieldValueEquals('label', 'Foo');
    $this->assertSession()->fieldValueEquals('id', 'foo');
    $this->assertSession()->fieldValueEquals('tokenName', 'Bar');
    $this->assertSession()->fieldValueEquals('tokenValue', 'Baz');

    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');

    $this->assertSession()->pageTextContains('The token Foo has been deleted.');
  }

}
