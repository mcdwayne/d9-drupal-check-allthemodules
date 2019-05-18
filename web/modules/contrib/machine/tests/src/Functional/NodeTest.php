<?php

namespace Drupal\Tests\machine\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class NodeTest.
 *
 * @package Drupal\Tests\machine\Functional
 *
 * @group machine
 */
class NodeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'datetime',
    'machine',
  ];

  /**
   * Prepared User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer nodes',
      'administer machine configuration',
    ], NULL, TRUE);

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type'              => 'page',
        'name'              => 'Basic page',
        'display_submitted' => FALSE,
        'preview_mode'      => DRUPAL_DISABLED,
      ]);
    }

    $this->config('machine.settings')->set('types', ['node'])->save();

    \Drupal::entityDefinitionUpdateManager()->applyUpdates();
  }

  /**
   * Testing node edit for when machine is enabled.
   *
   * @test
   */
  public function aUserEditsNode() {
    $this->drupalLogin($this->user);
    $session = $this->assertSession();

    $types = $this->config('machine.settings')->get('types');
    $this->assertEquals(['node'], $types,
      'Expect we have proper config for node');

    $node = $this->drupalCreateNode();

    $this->drupalGet('node/' . $node->id() . '/edit');

    $session->fieldExists('Title');
    $session->fieldExists('Machine name');

    $page = $this->getSession()->getPage();

    $page->fillField('Title', 'Title_123');
    $page->fillField('Machine name', 'machine');
    $page->pressButton('Save and keep published');

    $session->addressEquals('node/' . $node->id());
    $session->statusCodeEquals(200);
    $session->pageTextContains('Title_123');
  }

}
