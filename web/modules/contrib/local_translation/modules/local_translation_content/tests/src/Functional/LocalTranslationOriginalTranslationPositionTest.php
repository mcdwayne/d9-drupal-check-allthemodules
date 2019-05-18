<?php

namespace Drupal\Tests\local_translation_content\Functional;

use Drupal\local_translation_content\LocalTranslationContentTestsTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Class LocalTranslationOriginalTranslationPositionTest.
 *
 * @package Drupal\Tests\local_translation_content\Functional
 *
 * @group local_translation_content
 */
class LocalTranslationOriginalTranslationPositionTest extends BrowserTestBase {
  use LocalTranslationContentTestsTrait;

  /**
   * {@inheritdoc}
   */
  public $profile = 'standard';
  /**
   * {@inheritdoc}
   */
  public static $modules = ['local_translation_content'];

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
      ['local_translation_content update content translations', 'translate article node'],
      'editor'
    );
    $this->skills->addSkill(static::$registeredSkills, $editor);
    $this->drupalLogin($editor);

    $this->drupalGet("node/1/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'de\'][text()=\'Edit\'][1]/@href');
    $this->assertSession()->elementContains('xpath', '//td[1]', '(Original language)');
  }

}
