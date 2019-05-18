<?php

namespace Drupal\Tests\node_view_language_permissions\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the condition plugins work.
 *
 * @group node_view_language_permissions
 */
class NodeViewLanguagePermissionsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node_view_language_permissions',
  ];

  /**
   * Use the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin;

  /**
   * Auth user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Node one.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node1;

  /**
   * Node two.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node2;

  /**
   * Node three.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node3;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);
    $this->user = $this->drupalCreateUser([], NULL, FALSE);

    // Create FR.
    $this->drupalPostForm('/admin/config/regional/language/add', [
      'predefined_langcode' => 'fr',
    ], 'Add language');

    // Set prefixes to en and fr.
    $this->drupalPostForm('/admin/config/regional/language/detection/url', [
      'prefix[en]' => 'en',
      'prefix[fr]' => 'fr',
    ], 'Save configuration');

    // Set up URL and language selection page methods.
    $this->drupalPostForm('/admin/config/regional/language/detection', [
      'language_interface[enabled][language-url]' => FALSE,
      'language_content[configurable]' => TRUE,
      'language_content[enabled][language-url]' => TRUE,
    ], 'Save settings');

    // Turn on content translation for pages.
    $this->drupalPostform('/admin/structure/types/manage/page', [
      'language_configuration[content_translation]' => TRUE,
    ], 'Save content type');
    $this->drupalPostform('/admin/structure/types/manage/article', [
      'language_configuration[content_translation]' => TRUE,
    ], 'Save content type');

    // Create nodes.
    $this->node1 = $this->createNode([
      'title' => 'Node one',
      'type' => 'article',
    ]);
    $this->node2 = $this->createNode([
      'title' => 'Node two',
      'type' => 'page',
    ]);
    $this->node3 = $this->createNode([
      'title' => 'Node three',
      'type' => 'article',
    ]);

    // Translate nodes.
    $this->drupalPostform('/fr/node/' . $this->node1->id() . '/translations/add/en/fr', [
      'title[0][value]' => 'Nodule une',
    ], 'Save (this translation)');

    $this->drupalPostform('/fr/node/' . $this->node2->id() . '/translations/add/en/fr', [
      'title[0][value]' => 'Nodule deux',
    ], 'Save (this translation)');

    $this->drupalPostform('/fr/node/' . $this->node3->id() . '/translations/add/en/fr', [
      'title[0][value]' => 'Nodule trois',
    ], 'Save (this translation)');

    // Set permissions.
    $this->drupalPostform('/admin/people/permissions', [
      'authenticated[view any article en content]' => TRUE,
      'authenticated[view any page fr content]' => TRUE,
    ], 'Save permissions');
    $this->drupalPostForm('admin/reports/status/rebuild', [], 'Rebuild permissions');

    $this->drupalLogout();
  }

  /**
   * Test access when logged out.
   */
  public function testLoggedOut() {

    $this->drupalGet('/en/node/' . $this->node1->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/en/node/' . $this->node2->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/en/node/' . $this->node3->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/fr/node/' . $this->node1->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/fr/node/' . $this->node2->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/fr/node/' . $this->node3->id());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test access when logged in.
   */
  public function testLoggedIn() {

    $this->drupalLogin($this->user);

    $this->drupalGet('/en/node/' . $this->node1->id());
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/en/node/' . $this->node2->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/fr/node/' . $this->node1->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/fr/node/' . $this->node2->id());
    $this->assertSession()->statusCodeEquals(200);

  }

  /**
   * Test access when translation is unpublished.
   */
  public function testUnpublished() {
    $this->drupalLogin($this->admin);
    $this->drupalPostform('/admin/people/permissions', [
      'authenticated[view any article en content]' => TRUE,
      'authenticated[view any article fr content]' => TRUE,
    ], 'Save permissions');
    $this->drupalLogout();

    $this->drupalLogin($this->user);
    $this->drupalGet('/en/node/' . $this->node3->id());
    $this->assertSession()->pageTextContains('Node three');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();

    $this->drupalLogin($this->admin);

    $this->drupalPostForm('/en/node/' . $this->node3->id() . '/edit', [
      'status[value]' => FALSE,
    ], 'Save (this translation)');

    $this->drupalPostForm('/fr/node/' . $this->node3->id() . '/edit', [
      'status[value]' => TRUE,
    ], 'Save (this translation)');
    $this->drupalPostForm('admin/reports/status/rebuild', [], 'Rebuild permissions');

    $this->drupalLogout();

    $this->drupalLogin($this->user);

    $this->drupalGet('/en/node/' . $this->node1->id());
    $this->assertSession()->pageTextContains('Node one');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/en/node/' . $this->node3->id());
    $this->assertSession()->pageTextNotContains('Node three');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/fr/node/' . $this->node1->id());
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/fr/node/' . $this->node3->id());
    $this->assertSession()->pageTextContains('Nodule trois');
    $this->assertSession()->statusCodeEquals(200);
  }

}
