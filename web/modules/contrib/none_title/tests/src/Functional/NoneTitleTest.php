<?php

namespace Drupal\Tests\none_title\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class NoneTitleTest.
 *
 * Tests for None Title module.
 *
 * @group NoneTitleTest
 */
class NoneTitleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'none_title',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $user = $this->drupalCreateUser([
      'access administration pages',
      'create article content',
      'edit any article content',
      'access content overview',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('node/add/article');
    $edit = [
      'title[0][value]' => 'My node title',
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
  }

  /**
   * Test display of <none> title in nodes.
   */
  public function testNoneTitleNodeDisplay() {
    $this->drupalGet('node/1');
    $this->assertSession()
      ->elementTextContains('css', "h1.page-title .field--name-title", 'My node title');
    $edit = [
      'title[0][value]' => '<none>',
    ];
    $this->drupalPostForm('node/1/edit', $edit, t('Save'));
    $this->assertSession()
      ->elementTextContains('css', "h1.page-title .field--name-title", NULL);
  }

  /**
   * Test display of <none> title in views.
   */
  public function testNoneTitleViewDisplay() {
    $this->drupalGet('admin/content');
    $this->assertSession()
      ->elementTextContains('css', "table > tbody > tr:nth-child(1) td.views-field-title", 'My node title');
    $edit = [
      'title[0][value]' => '<none>',
    ];
    $this->drupalPostForm('node/1/edit', $edit, t('Save'));
    $this->drupalGet('admin/content');
    $this->assertSession()
      ->elementTextContains('css', "table > tbody > tr:nth-child(1) td.views-field-title", NULL);
  }

}
