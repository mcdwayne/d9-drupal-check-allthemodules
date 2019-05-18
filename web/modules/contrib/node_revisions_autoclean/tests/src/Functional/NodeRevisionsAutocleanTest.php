<?php

namespace Drupal\Tests\node_revisions_autoclean\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Class NodeRevisionsAutocleanTest.
 *
 * @package Drupal\Tests\node_revisions_autoclean\Functional
 * @group node_revisions_autoclean
 * @ingroup node_revisions_autoclean
 */
class NodeRevisionsAutocleanTest extends BrowserTestBase {
  use StringTranslationTrait;

  public static $modules = ['node', 'node_revisions_autoclean', 'language'];

  /**
   * Drupal\Core\Language\LanguageManager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Setups tests.
   */
  protected function setUp() {
    parent::setUp();
    $this->languageManager = \Drupal::service('language_manager');
    // Create, log in user & Install French language.
    $user = $this->drupalCreateUser(['administer languages', 'access administration pages']);
    $this->drupalLogin($user);
    $edit = ['predefined_langcode' => 'fr'];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $this->rebuildContainer();

    // Creates default node types.
    $config = \Drupal::configFactory()->getEditable('node_revisions_autoclean.settings');
    $config
      ->set('enable_on_cron', 0)
      ->set('enable_on_node_update', 0)
      ->save(TRUE);
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'page',
        'name' => 'Page',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Initialize autclean general settings.
   *
   * @param bool $enabledAutoclean
   *   Enabled Autoclean.
   */
  private function initializeSettings($enabledAutoclean = FALSE) {
    $config = \Drupal::configFactory()->getEditable('node_revisions_autoclean.settings');
    $config
      ->set('enable_on_cron', $enabledAutoclean ? 1 : 0)
      ->set('enable_on_node_update', $enabledAutoclean ? 1 : 0)
      ->set('node.page', $enabledAutoclean ? 2 : 0)
      ->set('interval.page', '0')
      ->set('node.article', $enabledAutoclean ? 2 : 0)
      ->set('interval.article', $enabledAutoclean ? 'P1W' : '0')
      ->save(TRUE);
  }

  /**
   * Creates node and its revisions.
   *
   * @param string $title
   *   Node's title.
   * @param string $type
   *   Node's type.
   *
   * @return Drupal\node\Entity\Node
   *   The created node.
   */
  public function createNodeWithRevisions($title, $type = 'page') {
    $user = $this->drupalCreateUser();
    $node = Node::create([
      'type' => $type,
      'title' => '1',
    ]);
    $node->setPublished(TRUE);
    $node->save();
    $this->addRevision($node, $user, FALSE);
    $this->addRevision($node, $user, TRUE);
    $this->addRevision($node, $user, FALSE);
    $this->addRevision($node, $user, TRUE);
    $this->addRevision($node, $user, FALSE);
    $this->addRevision($node, $user, TRUE);
    $this->addRevision($node, $user, TRUE);
    $this->addRevision($node, $user, FALSE);
    $this->addRevision($node, $user, FALSE);
    $this->addRevision($node, $user, FALSE);
    $this->addRevision($node, $user, FALSE);

    return $node;
  }

  /**
   * Adds revision to a node.
   *
   * @param Drupal\node\Entity\Node $node
   *   Node.
   * @param Drupal\user\Entity\User $user
   *   User.
   * @param bool $published
   *   Published.
   * @param string $langcode
   *   Langcode, default default site langcode.
   * @param \DateTime $dt
   *   Datetime, current if null.
   */
  public function addRevision(Node &$node, User $user, $published = FALSE, $langcode = NULL, \DateTime $dt = NULL) {
    if (!isset($dt)) {
      $dt = new \DateTime();
    }
    if (!isset($langcode)) {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }
    $node->setNewRevision(TRUE);
    $node->set('langcode', $langcode);
    $node->setRevisionCreationTime($dt->getTimestamp());
    $node->setRevisionUserId($user->id());
    $node->setPublished($published);
    $node->save();
  }

  /**
   * Tests permissions anonymous.
   */
  public function testAdminConfigAnonymousAccess() {
    $assert = $this->assertSession();

    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/content/revisions-autoclean');
    $assert->statusCodeEquals(403);

  }

  /**
   * Test permissions admin.
   */
  public function testAdminConfigAdminAccess() {
    $assert = $this->assertSession();
    $user = $this->drupalCreateUser(['configure revisions autoclean settings']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/content/revisions-autoclean');
    $assert->statusCodeEquals(200);
  }

  /**
   * Test initial configuration.
   */
  public function testModuleInitialConfiguration() {
    $config = \Drupal::configFactory()->get('node_revisions_autoclean.settings');
    $this->assertNotNull($config->get('enable_on_cron'), $this->t("Configuration enable_on_cron missing in node_revisions_autoclean.settings"));
    $this->assertNotNull($config->get('enable_on_node_update'), $this->t("Configuration enable_on_node_update missing in node_revisions_autoclean.settings"));
    $this->assertEquals($config->get('enable_on_cron'), 0);
    $this->assertEquals($config->get('enable_on_node_update'), 0);
  }

  /**
   * Test initial config form with article & page.
   */
  public function testConfigForm() {
    $assert = $this->assertSession();

    $user = $this->drupalCreateUser(['configure revisions autoclean settings']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/content/revisions-autoclean');

    $assert->checkboxNotChecked('enable_on_cron');
    $assert->checkboxNotChecked('enable_on_node_update');

    $assert->fieldExists('node__article');
    $assert->fieldExists('interval__article');
    $assert->fieldExists('node_enable_date_article');
    $assert->fieldValueEquals('interval__article', '0');
    $assert->checkboxNotChecked('node_enable_date_article');
    $assert->fieldValueEquals('node__article', '0');

    $assert->fieldExists('node__page');
    $assert->fieldExists('interval__page');
    $assert->fieldExists('node_enable_date_page');
    $assert->fieldValueEquals('interval__page', '0');
    $assert->checkboxNotChecked('node_enable_date_page');
    $assert->fieldValueEquals('node__page', '0');
  }

  /**
   * Test post form.
   */
  public function testDrupalPostForm() {
    $assert = $this->assertSession();
    $user = $this->drupalCreateUser(['configure revisions autoclean settings']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/content/revisions-autoclean');

    $this->drupalPostForm(NULL, [
      'enable_on_cron' => '0',
      'enable_on_node_update' => '1',
      'node__article' => '2',
      'node_enable_date_article' => '1',
      'interval__article' => 'P1W',
      'node__page' => '2',
      'node_enable_date_page' => '0',
      'interval__page' => 'P1W',
    ], $this->t('Submit'));

    $config = \Drupal::config('node_revisions_autoclean.settings');

    $this->assertEquals('0', $config->get('enable_on_cron'), 'Value for enable_on_cron ' . $config->get('enable_on_cron'));
    $this->assertEquals('1', $config->get('enable_on_node_update'), 'Value for enable_on_node_update : ' . $config->get('enable_on_node_update'));
    $this->assertEquals('2', $config->get('node.article'), 'Value for node.article : ' . $config->get('node.article'));
    $this->assertEquals('P1W', $config->get('interval.article'), 'Value for interval.article : ' . $config->get('interval.article'));
    $this->assertEquals('2', $config->get('node.page'), 'Value for node.page : ' . $config->get('node.page'));
    $this->assertEquals('0', $config->get('interval.page'), 'Value for interval.page : ' . $config->get('interval.page'));

    $assert->pageTextContains('Node revisions settings have been updated.');
  }

  /**
   * Test new configuration.
   */
  public function testConfiguration() {
    $this->initializeSettings(TRUE);
    $config = \Drupal::config('node_revisions_autoclean.settings');
    $this->assertEquals('1', $config->get('enable_on_cron'), 'Value for enable_on_cron ' . $config->get('enable_on_cron'));
    $this->assertEquals('1', $config->get('enable_on_node_update'), 'Value for enable_on_node_update : ' . $config->get('enable_on_node_update'));
    $this->assertEquals('2', $config->get('node.article'), 'Value for node.article : ' . $config->get('node.article'));
    $this->assertEquals('2', $config->get('node.page'), 'Value for node.page : ' . $config->get('node.page'));
    $this->assertEquals('P1W', $config->get('interval.article'), 'Value for interval.article : ' . $config->get('interval.article'));
    $this->assertEquals('0', $config->get('interval.page'), 'Value for interval.page : ' . $config->get('interval.page'));
  }

  /**
   * Test revisions.
   */
  public function testInitialRevisions() {
    $this->initializeSettings(FALSE);
    $node = $this->createNodeWithRevisions('1', 'page');
    /* @var $revisionsManager RevisionsManager */
    $revisionsManager = \Drupal::service('node_revisions_autoclean.revisions_manager');
    $revisions = $revisionsManager->loadRevisions($node);

    $this->assertEquals(12, count($revisions), 'Revisions initial : ' . count($revisions));
  }

  /**
   * Tests that revisions are deleted as they should be.
   */
  public function testDeleteRevisions() {
    $node = $this->createNodeWithRevisions('1', 'page');
    /* @var $revisionsManager RevisionsManager */
    $revisionsManager = \Drupal::service('node_revisions_autoclean.revisions_manager');
    $revisions = $revisionsManager->loadRevisions($node);
    // CreateNodeWithRevisions creates 12 revisions, check :
    $this->assertEquals(12, count($revisions), 'Revisions initial : ' . count($revisions));

    $this->initializeSettings(TRUE);
    $user = $this->drupalCreateUser(['configure revisions autoclean settings']);
    $this->addRevision($node, $user, TRUE);
    $revisionsAfterSave = $revisionsManager->loadRevisions($node);
    // There should be 3 revisions.
    $this->assertEquals(3, count($revisionsAfterSave), 'Revisions after save : ' . count($revisionsAfterSave));
    $this->addRevision($node, $user, FALSE);
    $this->addRevision($node, $user, FALSE);
    $this->addRevision($node, $user, FALSE);
    $revisionsAfterSave2 = $revisionsManager->loadRevisions($node);
    // There should be 6 revisions : all last drafts + 3.
    $this->assertEquals(6, count($revisionsAfterSave2), 'Revisions after insert & save  : ' . count($revisionsAfterSave2));
    $this->addRevision($node, $user, TRUE);
    $this->addRevision($node, $user, TRUE);
    $revisionsAfterSave3 = $revisionsManager->loadRevisions($node);

    // There should be 3 (current + 2 in settings).
    $this->assertEquals(3, count($revisionsAfterSave3), 'Revisions after insert & save  : ' . count($revisionsAfterSave3));

    // Disables autoclean.
    $this->initializeSettings(FALSE);
    $this->addRevision($node, $user, TRUE);
    $revisionsAfterSave4 = $revisionsManager->loadRevisions($node);
    // There should be 4.
    $this->assertEquals(4, count($revisionsAfterSave4), 'Revisions after insert & save  : ' . count($revisionsAfterSave4));
  }

  /**
   * Tests that revisions are deleted as they should be with date constraint.
   */
  public function testDeleteDateRevisions() {
    $node = $this->createNodeWithRevisions('1', 'article');
    /* @var $revisionsManager RevisionsManager */
    $revisionsManager = \Drupal::service('node_revisions_autoclean.revisions_manager');
    $revisions = $revisionsManager->loadRevisions($node);
    // CreateNodeWithRevisions creates 12 revisions, check.
    $this->assertEquals(12, count($revisions), 'Revisions initial : ' . count($revisions));

    $oneMonthAgo = new \DateTime();
    $oneMonthAgo->sub(new \DateInterval('P1M'));
    $now = new \DateTime();

    $this->initializeSettings(TRUE);
    $user = $this->drupalCreateUser(['configure revisions autoclean settings']);
    $this->addRevision($node, $user, TRUE);
    $revisionsAfterSave = $revisionsManager->loadRevisions($node);
    // There should be 13 revisions.
    $this->assertEquals(13, count($revisionsAfterSave), 'Revisions after save : ' . count($revisionsAfterSave));
    $this->addRevision($node, $user, TRUE, 'en', $oneMonthAgo);
    $this->addRevision($node, $user, TRUE, 'en', $oneMonthAgo);
    $this->addRevision($node, $user, TRUE, 'en', $oneMonthAgo);
    $this->addRevision($node, $user, TRUE, 'en', $now);
    $this->addRevision($node, $user, TRUE, 'en', $now);
    $this->addRevision($node, $user, TRUE, 'en', $now);
    $this->addRevision($node, $user, TRUE, 'en', $now);
    $this->addRevision($node, $user, TRUE, 'en', $now);
    $revisionsAfterSave2 = $revisionsManager->loadRevisions($node);
    // There should be 18 revisions : all except these onemonthago.
    $this->assertEquals(18, count($revisionsAfterSave2), 'Revisions after insert & save  : ' . count($revisionsAfterSave2));

  }

  /**
   * Tests node revisions autoclean with multiple languages.
   */
  public function testDeleteMultilanguageRevisions() {
    $this->initializeSettings(TRUE);
    $node = $this->createNodeWithRevisions('1', 'page');
    $revisionsManager = \Drupal::service('node_revisions_autoclean.revisions_manager');
    $revisions = $revisionsManager->loadRevisions($node);
    // There should be 3 revisions.
    $this->assertEquals(7, count($revisions), 'Revisions after save : ' . count($revisions));
    $user = $this->drupalCreateUser();
    $this->addRevision($node, $user, TRUE);
    $this->addRevision($node, $user, TRUE);
    $this->addRevision($node, $user, TRUE);
    $this->addRevision($node, $user, TRUE, 'fr');
    $this->addRevision($node, $user, TRUE, 'fr');
    $this->addRevision($node, $user, TRUE, 'fr');
    $revisions = $revisionsManager->loadRevisions($node);
    $this->assertEquals(6, count($revisions), 'Revisions after save : ' . count($revisions));
    $this->addRevision($node, $user, FALSE, 'fr');
    $this->addRevision($node, $user, FALSE, 'fr');
    $this->addRevision($node, $user, FALSE, 'fr');
    $this->addRevision($node, $user, FALSE, 'fr');
    $revisions = $revisionsManager->loadRevisions($node);
    $this->assertEquals(10, count($revisions), 'Revisions after save : ' . count($revisions));
    $this->addRevision($node, $user, TRUE, 'fr');
    $revisions = $revisionsManager->loadRevisions($node);
    $this->assertEquals(6, count($revisions), 'Revisions after save : ' . count($revisions));

  }

}
