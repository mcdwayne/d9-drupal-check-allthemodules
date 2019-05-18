<?php

namespace Drupal\Tests\entity_toolbar\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Test the existence of entity toolbars.
 *
 * @group entity_toolbar
 */
class EntityToolbarTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'taxonomy',
    'entity_toolbar',
  ];

  /**
   * The admin user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $toolbarUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
      ]);
      $this->drupalCreateContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);
    }

    $vocabulary = Vocabulary::create([
      'name' => 'Llama',
      'vid' => 'llama',
    ]);
    $vocabulary->save();

    $vocabulary = Vocabulary::create([
      'name' => 'Zebra',
      'vid' => 'zebra',
    ]);
    $vocabulary->save();

    $this->adminUser = $this->drupalCreateUser([
      'access toolbar',
      'administer menu',
      'access administration pages',
      'administer site configuration',
      'administer taxonomy',
      'administer content types',
    ]);

    $this->toolbarUser = $this->drupalCreateUser([
      'access toolbar',
    ]);
  }

  /**
   * Tests toolbar integration.
   */
  public function testToolbarIntegration() {
    $library_css_url = 'entity_toolbar/css/entity.toolbar.css';
    $toolbar_selector = '#toolbar-bar .toolbar-tab';
    $taxonomy_tab = '#toolbar-item-taxonomy-term';
    $taxonomy_tray = '#toolbar-item-taxonomy-term-tray';

    $node_tab = '#toolbar-item-node';
    $node_tray = '#toolbar-item-node-tray';

    // Ensures that node types toolbar item is accessible only for user with the
    // adequate permissions.
    $this->drupalGet('');
    $this->assertSession()->responseNotContains($library_css_url);
    $this->assertSession()->elementNotExists('css', $toolbar_selector);
    $this->assertSession()->elementNotExists('css', $taxonomy_tab);

    $this->drupalLogin($this->toolbarUser);
    $this->assertSession()->responseNotContains($library_css_url);
    $this->assertSession()->elementExists('css', $toolbar_selector);
    $this->assertSession()->elementNotExists('css', $taxonomy_tab);
    $this->assertSession()->elementNotExists('css', $node_tab);

    $this->drupalLogin($this->adminUser);

    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:entity_toolbar.type.node');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:entity_toolbar.type.taxonomy_term');

    $this->assertSession()->elementExists('css', $toolbar_selector);

    $this->assertSession()->responseContains($library_css_url);
    $this->assertSession()->elementExists('css', $toolbar_selector);
    $this->assertSession()->elementExists('css', $taxonomy_tab);
    $this->assertSession()->elementExists('css', $node_tab);
    $this->assertSession()->elementTextContains('css', $taxonomy_tab, 'Term Types');
    $this->assertSession()->elementTextContains('css', $node_tab, 'Content Types');

    $this->assertSession()->elementExists('css', $taxonomy_tray);
    $this->assertSession()->elementExists('css', $node_tray);

  }

  /**
   * Tests toolbar cache tags.
   */
  public function testToolbarCacheTags() {

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('');

    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:entity_toolbar.type.node');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:entity_toolbar.type.taxonomy_term');

  }

  /**
   * Tests toolbar config forms.
   */
  public function testToolbarConfigForms() {

    $taxonomy_tab = '#toolbar-item-taxonomy-term';
    $node_tab = '#toolbar-item-node';

    $this->drupalLogin($this->adminUser);

    // Test config forms.
    $this->drupalGet('admin/config/content/entity_toolbar/node/edit');
    $this->assertSession()->responseContains('node.type_add');

    $edit = ['label' => 'Node types'];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->assertSession()->elementTextContains('css', $node_tab, 'Node types');

    $this->drupalGet('admin/config/content/entity_toolbar/taxonomy_term/edit');
    $this->assertSession()->responseContains('entity.taxonomy_vocabulary.add_form');
    $edit = ['label' => 'Vocabularies'];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->assertSession()->elementTextContains('css', $taxonomy_tab, 'Vocabularies');

    // Test ajax responses.
    $this->drupalGet('admin/entity_toolbar/node');
    $this->assertSession()->statusCodeEquals('200');
    $this->assertSession()->responseContains('"selector":"#entity-toolbar-placeholder-node"');
    $this->assertSession()->responseContains('admin\/structure\/types\/manage\/article');

    $this->drupalGet('admin/entity_toolbar/taxonomy_term');
    $this->assertSession()->statusCodeEquals('200');
    $this->assertSession()->responseContains('"selector":"#entity-toolbar-placeholder-taxonomy_term"');
    $this->assertSession()->responseContains('admin\/structure\/taxonomy\/manage\/llama\/overview');
    $this->assertSession()->responseContains('admin\/structure\/taxonomy\/manage\/zebra\/overview');
  }

}
