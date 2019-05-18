<?php

namespace Drupal\Tests\edit_unpublished_node_warning\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class WarningDisplayTest.
 *
 * @group unpublishedWarning
 *
 * @package Drupal\Tests\edit_unpublished_node_warning\Functional
 */
class WarningDisplayTest extends BrowserTestBase {

  /**
   * Enable the modules we need for this test.
   *
   * @var array
   */
  protected static $modules = [
    'filter',
    'node',
    'user',
    'edit_unpublished_node_warning',
  ];

  /**
   * An administrator user we can use for all tests.
   *
   * @var \Drupal\user\Entity\User
   */
  public $administrator;

  /**
   * Setup a node and user.
   */
  protected function setUp() {
    parent::setUp();

    // Create an administrator user.
    $this->administrator = $this->drupalCreateUser([
      'bypass node access',
      'view the administration theme',
    ]);
  }

  /**
   * The warning message is shown when viewing or editing an unpublished page.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testWarningMessageIsShownOnUnpublishedPage() {
    // Login as our administrator.
    $this->drupalLogin($this->administrator);

    // Setup a node we can view.
    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode();
    $node->setPublished(FALSE);
    $node->save();

    // Check this page is unpublished.
    $this->assertFalse($node->isPublished());

    // Check we see the message.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($node->getTitle());
    $this->assertSession()->pageTextContains(t('This content is unpublished. This will not be visible to site users until it is published.'));

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('This content is unpublished. This will not be visible to site users until it is published.'));
  }

  /**
   * The warning message is NOT shown when editing or viewing a published page.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testWarningMessageIsNotShownOnPublishedPage() {
    // Login as our administrator.
    $this->drupalLogin($this->administrator);

    // Setup a node we can view.
    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode();

    // Check this page is published.
    $this->assertTrue($node->isPublished());

    // Check we don't see the message.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($node->getTitle());
    $this->assertSession()->pageTextNotContains(t('This content is unpublished. This will not be visible to site users until it is published.'));

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains(t('This content is unpublished. This will not be visible to site users until it is published.'));
  }

}
