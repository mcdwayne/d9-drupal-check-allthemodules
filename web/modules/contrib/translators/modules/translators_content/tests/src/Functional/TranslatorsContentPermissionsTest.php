<?php

namespace Drupal\Tests\translators_content\Functional;

use Drupal\translators_content\TranslatorsContentTestsTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Class TranslatorsContentPermissionsTest.
 *
 * @package Drupal\Tests\translators_content\Functional
 *
 * @group translators_content
 */
class TranslatorsContentPermissionsTest extends BrowserTestBase {
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
   * Register translation skills for specific user.
   *
   * @param \Drupal\user\Entity\User $user
   *   User to be added with translation skills.
   */
  protected function registerSkillsFor(User $user) {
    $this->addSkill(['en', 'fr'], $user);
  }

  /**
   * Create testing node.
   *
   * @param bool $with_translation
   *   Flag for create a translation or not.
   *
   * @return int
   *   Node ID.
   */
  protected function createTestNode($with_translation = FALSE) {
    $this->drupalLogin($this->rootUser);
    // Create testing node.
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => $this->randomString(),
    ], 'Save');

    if ($with_translation) {
      $node = Node::load(1);
      foreach ($this->getAllTestingLanguages() as $language) {
        if (!$node->hasTranslation($language)) {
          $translation = $node->addTranslation(
            $language,
            ['title' => $this->randomString()]
          );
          $translation->save();
        }
      }

    }
    $this->drupalLogout();
    return 1;
  }

  /**
   * Test that all permissions are exist.
   */
  public function testPermissionsExistence() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/people/permissions');
    $this->assertResponseCode(200);
    $permissions_prefix = '(in translation skills)';

    // Check for Content Translators permissions section.
    $this->assertTextHelper('Content Translators', FALSE);

    // Check for "static" permissions existence.
    $this->assertTextHelper("Create translations $permissions_prefix", FALSE);
    $this->assertTextHelper("Edit translations $permissions_prefix", FALSE);
    $this->assertTextHelper("Delete translations $permissions_prefix", FALSE);

    // Check for "content" permissions existence.
    $this->assertTextHelper("Article: Create new content $permissions_prefix", FALSE);
    $this->assertTextHelper("Article: Edit own content $permissions_prefix", FALSE);
    $this->assertTextHelper("Article: Edit any content $permissions_prefix", FALSE);
    $this->assertTextHelper("Article: Delete own content $permissions_prefix", FALSE);
    $this->assertTextHelper("Article: Delete any content $permissions_prefix", FALSE);

    // Additionally check that legacy permissions are NOT EXIST anymore!
    $this->assertTextHelper("Translate Article content $permissions_prefix");
    $this->assertTextHelper("Translate any entity $permissions_prefix");
  }

  /**
   * Test permissions visibility while they are enabled and not.
   */
  public function testPermissionsVisibility() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/people/permissions');
    $this->assertResponseCode(200);
    $permissions_prefix = '(in translation skills)';

    // Check for "static" permissions existence.
    $this->assertSession()->responseContains("Create translations $permissions_prefix");
    $this->assertSession()->responseContains("Edit translations $permissions_prefix");
    $this->assertSession()->responseContains("Delete translations $permissions_prefix");

    // Check for "content" permissions existence.
    $this->assertSession()->responseContains("Article: Create new content $permissions_prefix");
    $this->assertSession()->responseContains("Article: Edit own content $permissions_prefix");
    $this->assertSession()->responseContains("Article: Edit any content $permissions_prefix");
    $this->assertSession()->responseContains("Article: Delete own content $permissions_prefix");
    $this->assertSession()->responseContains("Article: Delete any content $permissions_prefix");

    $this->enablePermissionsChecking(TRUE);

    // Check for "static" permissions NON-existence.
    $this->assertSession()->responseNotContains("Create translations $permissions_prefix");
    $this->assertSession()->responseNotContains("Edit translations $permissions_prefix");
    $this->assertSession()->responseNotContains("Delete translations $permissions_prefix");

    // Check for "content" permissions NON-existence.
    $this->assertSession()->responseNotContains("Article: Create new content $permissions_prefix");
    $this->assertSession()->responseNotContains("Article: Edit own content $permissions_prefix");
    $this->assertSession()->responseNotContains("Article: Edit any content $permissions_prefix");
    $this->assertSession()->responseNotContains("Article: Delete own content $permissions_prefix");
    $this->assertSession()->responseNotContains("Article: Delete any content $permissions_prefix");
  }

  /**
   * Test user's content translation "create" access.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testContentTranslationCreator() {
    $node_id = $this->createTestNode();

    // Disable filtering as it is only
    // needed to be enabled for testing limited users.
    $this->drupalLogin($this->rootUser);
    $this->enableFilterTranslationOverviewToSkills(TRUE);
    $this->drupalLogout();

    $creator1 = $this->createUser(
      ['create content translations', 'translate article node'],
      'creator1'
    );
    $creator2 = $this->createUser(
      ['create content translations', 'translate any entity'],
      'creator2'
    );

    $this->drupalLogin($creator1);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Add\']/@href');

    $this->drupalLogout();
    $this->drupalLogin($creator2);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Add\']/@href');
  }

  /**
   * Test user's content translation "edit" access.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testContentTranslationEditor() {
    $node_id = $this->createTestNode(TRUE);

    // Disable filtering as it is only
    // needed to be enabled for testing limited users.
    $this->drupalLogin($this->rootUser);
    $this->enableFilterTranslationOverviewToSkills(TRUE);
    $this->drupalLogout();

    $editor1 = $this->createUser(
      ['update content translations', 'translate any entity'],
      'editor1'
    );
    $editor2 = $this->createUser(
      ['update content translations', 'translate article node'],
      'editor2'
    );

    $this->drupalLogin($editor1);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Edit\']/@href');
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'en\'][text()=\'Edit\']/@href');

    $this->drupalLogout();
    $this->drupalLogin($editor2);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Edit\']/@href');
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'en\'][text()=\'Edit\']/@href');
  }

  /**
   * Test user's content translation "delete" access.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testContentTranslationDeleter() {
    $node_id = $this->createTestNode(TRUE);

    // Disable filtering as it is only
    // needed to be enabled for testing limited users.
    $this->drupalLogin($this->rootUser);
    $this->enableFilterTranslationOverviewToSkills(TRUE);
    $this->drupalLogout();

    $deleter1 = $this->createUser(
      ['delete content translations', 'translate any entity'],
      'deleter1'
    );
    $deleter2 = $this->createUser(
      ['delete content translations', 'translate article node'],
      'deleter2'
    );

    $this->drupalLogin($deleter1);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Delete\']/@href');
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'en\'][text()=\'Delete\']/@href');

    $this->drupalLogout();
    $this->drupalLogin($deleter2);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Delete\']/@href');
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'en\'][text()=\'Delete\']/@href');
  }

  /**
   * Test user's translators_content "create" access.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testLimitedCreator() {
    $node_id = $this->createTestNode();

    $creator1 = $this->createUser(
      ['translators_content create content translations', 'translate article node'],
      'creator1'
    );
    $creator2 = $this->createUser(
      ['translators_content create content translations', 'translate any entity'],
      'creator2'
    );

    $this->drupalLogin($creator1);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementNotExists('xpath', '//a[text()=\'Add\']/@href');

    $this->drupalLogout();

    $this->drupalLogin($creator2);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementNotExists('xpath', '//a[text()=\'Add\']/@href');

    $this->drupalLogout();

    $this->registerSkillsFor($creator1);
    $this->registerSkillsFor($creator2);

    $this->drupalLogin($creator1);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Add\']/@href');

    $this->clickLink('Add');
    $this->assertResponseCode(403, TRUE);

    $this->drupalLogout();
    $this->drupalLogin($creator2);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Add\']/@href');

    $this->clickLink('Add');
    $this->assertResponseCode(403, TRUE);
  }

  /**
   * Test user's translators_content "edit" access.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testLimitedEditor() {
    $node_id = $this->createTestNode(TRUE);

    $editor1 = $this->createUser(
      ['translators_content update content translations', 'translate article node'],
      'editor1'
    );
    $editor2 = $this->createUser(
      ['translators_content update content translations', 'translate any entity'],
      'editor2'
    );

    $this->drupalLogin($editor1);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementNotExists('xpath', '//a[text()=\'Edit\']/@href');

    $this->drupalLogout();
    $this->drupalLogin($editor2);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementNotExists('xpath', '//a[text()=\'Edit\']/@href');

    $this->drupalLogout();
    $this->registerSkillsFor($editor1);
    $this->registerSkillsFor($editor2);
    $this->drupalLogin($editor1);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Edit\']/@href');
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'en\'][text()=\'Edit\']/@href');

    $this->clickLink('Edit');
    $this->assertResponseCode(403, TRUE);

    $this->drupalLogout();
    $this->drupalLogin($editor2);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Edit\']/@href');
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'en\'][text()=\'Edit\']/@href');

    $this->clickLink('Edit');
    $this->assertResponseCode(403, TRUE);

    $this->drupalLogout();
    $this->drupalLogin($editor1);

    $this->drupalGet("de/node/$node_id/edit");
    $this->assertResponseCode(403);

    $this->drupalLogout();
    $this->drupalLogin($editor2);

    $this->drupalGet("sq/node/$node_id/edit");
    $this->assertResponseCode(403);
  }

  /**
   * Test user's translators_content "delete" access.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testLimitedDeleter() {
    $node_id = $this->createTestNode(TRUE);

    $deleter1 = $this->createUser(
      ['translators_content delete content translations', 'translate article node'],
      'deleter1'
    );
    $deleter2 = $this->createUser(
      ['translators_content delete content translations', 'translate any entity'],
      'deleter2'
    );

    $this->drupalLogin($deleter1);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementNotExists('xpath', '//a[text()=\'Delete\']/@href');

    $this->drupalLogout();
    $this->drupalLogin($deleter2);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementNotExists('xpath', '//a[text()=\'Delete\']/@href');

    $this->drupalLogout();
    $this->registerSkillsFor($deleter1);
    $this->registerSkillsFor($deleter2);
    $this->drupalLogin($deleter1);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Delete\']/@href');
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'en\'][text()=\'Delete\']/@href');

    $this->clickLink('Delete');
    $this->assertResponseCode(403, TRUE);

    $this->drupalLogout();
    $this->drupalLogin($deleter2);

    $this->drupalGet("node/$node_id/translations");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Delete\']/@href');
    $this->assertSession()->elementNotExists('xpath', '//a[@hreflang=\'en\'][text()=\'Delete\']/@href');

    $this->clickLink('Delete');
    $this->assertResponseCode(403, TRUE);

    $this->drupalLogout();
    $this->drupalLogin($deleter1);

    $this->drupalGet("de/node/$node_id/delete");
    $this->assertResponseCode(403);

    $this->drupalLogout();
    $this->drupalLogin($deleter2);

    $this->drupalGet("sq/node/$node_id/delete");
    $this->assertResponseCode(403);
  }

  /**
   * Test that ensures we don't restrict access to the user's edit form.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testUserEditFormWorkaround() {
    $user = $this->createUser();
    $this->drupalLogin($user);

    // Check entity local task tabs existence.
    $this->drupalGet("user/{$user->id()}");
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Edit\']/@href');

    // Check for the edit page access.
    $this->drupalGet("user/{$user->id()}/edit");
    $this->assertResponseCode(403, TRUE);
    $this->assertResponseCode(200);

    // Additionally check that we don't give access to this form
    // for anonymous users.
    $this->drupalLogout();
    $this->drupalGet("user/{$user->id()}/edit");
    $this->assertResponseCode(403);
  }

  /**
   * Test entity local task tabs existence.
   */
  public function testEntityLocalTasksAccess() {
    $node_id = $this->createTestNode(TRUE);
    $user = $this->createUser(
      [
        'translators_content delete content translations',
        'translators_content update content translations',
        'translate article node',
      ],
      'testuser'
    );
    $this->drupalLogin($user);
    $this->registerSkillsFor($user);
    // Check for EXISTING tabs.
    $this->drupalGet('fr/node/' . $node_id);
    $this->assertResponseCode(200);
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Edit\']/@href');
    $this->assertSession()->elementExists('xpath', '//a[text()=\'Delete\']/@href');

    // Check for NON-EXISTING tabs.
    $this->drupalGet('de/node/' . $node_id);
    $this->assertResponseCode(200);
    $this->assertSession()->elementNotExists('xpath', '//a[text()=\'Edit\']/@href');
    $this->assertSession()->elementNotExists('xpath', '//a[text()=\'Delete\']/@href');
  }

}
