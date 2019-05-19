<?php

namespace Drupal\Tests\translators_content\Functional;

use Drupal\translators_content\TranslatorsContentTestsTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Class TranslatorsContentGeneralPermissionsTest.
 *
 * @package Drupal\Tests\translators_content\Functional
 *
 * @group translators_content
 */
class TranslatorsContentGeneralPermissionsTest extends BrowserTestBase {
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
    // Make all required configurations before testing.
    $this->setUpTest();
  }

  /**
   * Test "Create new content" permission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCreatePermission() {
    $creator = $this->createUser(['translators_content create article content']);
    $this->drupalLogin($creator);
    $this->registerTestSkills();

    foreach (static::$registeredSkills as $skill) {
      if ($skill === $this->defaultLanguage) {
        $this->drupalGet('node/add/article');
      }
      else {
        $this->drupalGet($skill . '/node/add/article');
      }
      $this->assertResponseCode(200);
    }

    foreach (static::$unregisteredSkills as $skill) {
      $this->drupalGet($skill . '/node/add/article');
      $this->assertResponseCode(403);
      $this->assertTextHelper('Access denied', FALSE);
    }
  }

  /**
   * Test "Edit own content" permission.
   */
  public function testEditOwnPermission() {
    $editor = $this->createUser(['translators_content edit own article content']);
    $this->drupalLogin($editor);
    $this->registerTestSkills();

    $nid = $this->createTestNode($editor->id());
    $this->addTestTranslations(Node::load($nid), $editor->id());

    foreach (static::$registeredSkills as $skill) {
      if ($skill === $this->defaultLanguage) {
        $this->drupalGet("node/$nid/edit");
      }
      else {
        $this->drupalGet("$skill/node/$nid/edit");
      }
      $this->assertResponseCode(200);
    }
    foreach (static::$unregisteredSkills as $skill) {
      $this->drupalGet("$skill/node/$nid/edit");
      $this->assertResponseCode(403);
      $this->assertTextHelper('Access denied', FALSE);
    }
  }

  /**
   * Test "Edit any content" permission.
   */
  public function testEditAnyPermission() {
    $editor = $this->createUser(['translators_content edit any article content']);
    $this->drupalLogin($editor);
    $this->registerTestSkills();

    $nid = $this->createTestNode();
    $this->addTestTranslations(Node::load($nid));

    foreach (static::$registeredSkills as $skill) {
      if ($skill === $this->defaultLanguage) {
        $this->drupalGet("node/$nid/edit");
      }
      else {
        $this->drupalGet("$skill/node/$nid/edit");
      }
      $this->assertResponseCode(200);
    }
    foreach (static::$unregisteredSkills as $skill) {
      $this->drupalGet("$skill/node/$nid/edit");
      $this->assertResponseCode(403);
      $this->assertTextHelper('Access denied', FALSE);
    }
  }

  /**
   * Test "Delete own content" permission.
   */
  public function testDeleteOwnPermission() {
    $deleter = $this->createUser(['translators_content delete own article content']);
    $this->drupalLogin($deleter);
    $this->registerTestSkills();

    $nid = $this->createTestNode($deleter->id());
    $this->addTestTranslations(Node::load($nid), $deleter->id());

    foreach (static::$registeredSkills as $skill) {
      if ($skill === $this->defaultLanguage) {
        $this->drupalGet("node/$nid/delete");
      }
      else {
        $this->drupalGet("$skill/node/$nid/delete");
      }
      $this->assertResponseCode(200);
    }
    foreach (static::$unregisteredSkills as $skill) {
      $this->drupalGet("$skill/node/$nid/delete");
      $this->assertResponseCode(403);
      $this->assertTextHelper('Access denied', FALSE);
    }
  }

  /**
   * Test "Delete any content" permission.
   */
  public function testDeleteAnyPermission() {
    $deleter = $this->createUser(['translators_content delete any article content']);
    $this->drupalLogin($deleter);
    $this->registerTestSkills();

    $nid = $this->createTestNode();
    $this->addTestTranslations(Node::load($nid));

    foreach (static::$registeredSkills as $skill) {
      if ($skill === $this->defaultLanguage) {
        $this->drupalGet("node/$nid/delete");
      }
      else {
        $this->drupalGet("$skill/node/$nid/delete");
      }
      $this->assertResponseCode(200);
    }
    foreach (static::$unregisteredSkills as $skill) {
      $this->drupalGet("$skill/node/$nid/delete");
      $this->assertResponseCode(403);
      $this->assertTextHelper('Access denied', FALSE);
    }
  }

  /**
   * Test case when user has permission to create and delete but not to edit.
   */
  public function testMissingEditPermissions() {
    $user = $this->createUser([
      'translators_content create article content',
      'translators_content delete any article content',
    ]);
    $this->drupalLogin($user);
    $this->registerTestSkills();

    // Ensure that user doesn't have edit permissions.
    $has_edit_permissions = $user->hasPermission('translators_content edit any article content')
      || $user->hasPermission('translators_content edit own article content');
    $this->assertFalse($has_edit_permissions);

    $nid = $this->createTestNode();
    $this->drupalGet("node/$nid/edit");
    $this->assertResponseCode(403);
    $this->assertTextHelper('Access denied', FALSE);
  }

}
