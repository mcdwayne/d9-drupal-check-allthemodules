<?php

namespace Drupal\Tests\translators_content\Functional;

use Drupal\translators_content\TranslatorsContentTestsTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Class TranslatorsOriginalTranslationPositionTest.
 *
 * @package Drupal\Tests\translators_content\Functional
 *
 * @group translators_content
 */
class TranslatorsOriginalTranslationPositionTest extends BrowserTestBase {
  use TranslatorsContentTestsTrait;

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';
  /**
   * {@inheritdoc}
   */
  public static $modules = ['translators_content'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setUpTest();
    Node::create([
      'type'     => 'article',
      'title'    => $this->randomString(),
      'language' => static::$unregisteredSkills[0],
    ])->save();
  }

  /**
   * Test the original translation language row position.
   */
  public function testOriginalTranslationPosition() {
    $editor = $this->createUser(
      ['translators_content update content translations', 'translate article node'],
      'editor'
    );
    $this->addSkill(static::$registeredSkills, $editor);
    $this->drupalLogin($editor);

    $this->drupalGet("node/1/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'de\'][text()=\'Edit\'][1]/@href');
    $this->assertSession()->elementContains('xpath', '//td[1]', '(Original language)');
  }

}
