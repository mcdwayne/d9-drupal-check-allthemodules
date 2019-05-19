<?php

namespace Drupal\Tests\translators_content\Functional;

use Drupal\translators_content\TranslatorsContentTestsTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Class TranslatorsSourceLanguageAccessTest.
 *
 * @package Drupal\Tests\translators_content\Functional
 *
 * @group translators_content
 */
class TranslatorsSourceLanguageAccessTest extends BrowserTestBase {
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
  }

  /**
   * Test access to the translation routes.
   */
  public function testRoutesAccess() {
    $nid = $this->createTestNode();
    Node::load($nid)
      ->addTranslation('de', ['title' => $this->randomString()])
      ->save();

    $creator = $this->createUser(
      ['translators_content create content translations', 'translate any entity'],
      'creator'
    );
    $this->drupalLogin($creator);
    $this->registerTestSkills();
    $this->drupalGet("fr/node/1/translations/add/en/fr");
    $this->assertResponseCode(200);
    $this->assertResponseCode(403, TRUE);

    $this->drupalGet("fr/node/1/translations/add/de/fr");
    $this->assertResponseCode(403);
    $this->assertResponseCode(200, TRUE);
  }

  /**
   * Test links existence.
   */
  public function testLinksExistence() {
    $nid = $this->createTestNode();
    Node::load($nid)
      ->addTranslation('de', ['title' => $this->randomString()])
      ->save();

    $creator = $this->createUser(
      ['translators_content create content translations', 'translate any entity'],
      'creator'
    );
    $this->drupalLogin($creator);
    $this->registerTestSkills();
    $this->drupalGet('node/1/translations');
    $this->assertResponseCode(200);
    $this->assertSession()
      ->elementExists('xpath', '//a[@hreflang=\'fr\'][text()=\'Add\']/@href');

    // Create node with German original language.
    $node = Node::create([
      'type'     => 'article',
      'title'    => $this->randomString(),
      'langcode' => 'de',
    ]);
    $node->save();

    $this->drupalGet('node/2/translations');
    $this->assertResponseCode(200);
    $this->assertSession()
      ->elementNotExists('xpath', '//a[@href=\'/fr/node/2/translations/add/de/fr\'][@hreflang=\'fr\'][text()=\'Add\']/@href');
  }

  /**
   * Test the non-default original language behavior.
   */
  public function testNonDefaultOriginalLanguage() {
    $node = Node::create([
      'type'     => 'article',
      'title'    => $this->randomString(),
      'langcode' => 'de',
    ]);
    $node->save();
    $creator = $this->createUser(
      ['translators_content create content translations', 'translate any entity'],
      'creator'
    );
    $this->drupalLogin($creator);
    $this->addSkill(static::$unregisteredSkills);

    $this->drupalGet('node/1/translations');
    $this->assertResponseCode(200);
    $this->assertSession()
      ->elementExists('xpath', '//a[@hreflang=\'sq\'][text()=\'Add\']/@href');
  }

}
