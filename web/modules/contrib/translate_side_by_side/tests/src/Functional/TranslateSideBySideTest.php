<?php

namespace Drupal\Tests\translate_side_by_side\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the translation table works.
 *
 * @group translate_side_by_side
 */
class TranslateSideBySideTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'translate_side_by_side',
    'content_translation',
    'language',
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
   * Block ID.
   *
   * @var int
   */
  protected $blockId1;

  /**
   * Menu ID.
   *
   * @var int
   */
  protected $menuId1;

  /**
   * Term ID.
   *
   * @var string
   */
  protected $termId1;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    // Create FR.
    $this->drupalPostForm('/admin/config/regional/language/add', [
      'predefined_langcode' => 'fr',
    ], 'Add language');

    // Set prefixes to en and fr.
    $this->drupalPostForm('/admin/config/regional/language/detection/url', [
      'prefix[en]' => 'en',
      'prefix[fr]' => 'fr',
    ], 'Save configuration');

    $this->drupalPostForm('/admin/config/regional/language/detection', [
      'language_interface[enabled][language-url]' => FALSE,
      'language_content[configurable]' => TRUE,
      'language_content[enabled][language-url]' => TRUE,
    ], 'Save settings');

    // Turn on content translation for taxonomy.
    $this->drupalPostform('/admin/config/regional/content-language', [
      'entity_types[node]' => TRUE,
      'settings[node][page][translatable]' => TRUE,
      'settings[node][page][fields][title]' => TRUE,
      'entity_types[block_content]' => TRUE,
      'settings[block_content][basic][translatable]' => TRUE,
      'settings[block_content][basic][fields][body]' => TRUE,
      'entity_types[menu_link_content]' => TRUE,
      'settings[menu_link_content][menu_link_content][translatable]' => TRUE,
      'settings[menu_link_content][menu_link_content][fields][title]' => TRUE,
      'entity_types[taxonomy_term]' => TRUE,
      'settings[taxonomy_term][tags][translatable]' => TRUE,
      'settings[taxonomy_term][tags][fields][name]' => TRUE,
      'settings[taxonomy_term][tags][fields][description]' => TRUE,
    ], 'Save');

    // Create nodes.
    $this->node1 = $this->createNode([
      'title' => 'Node one',
      'type' => 'page',
    ]);
    $this->node2 = $this->createNode([
      'title' => 'Node two',
      'type' => 'page',
    ]);
    // Translate node.
    $this->drupalPostform('/fr/node/' . $this->node1->id() . '/translations/add/en/fr', [
      'title[0][value]' => 'Nodule une',
    ], 'Save (this translation)');

    // Create block.
    $this->drupalPostform('/en/block/add', [
      'info[0][value]' => 'Block one info',
      'body[0][value]' => 'Block one body',
    ], 'Save');
    $this->blockId1 = preg_match("/\d+/", $this->getUrl());
    // Translate block.
    $this->drupalPostform('/fr/block/' . $this->blockId1 . '/translations/add/en/fr', [
      'body[0][value]' => 'Block une body',
    ], 'Save');

    // Create menu.
    $this->drupalPostform('/en/admin/structure/menu/manage/main/add', [
      'title[0][value]' => 'Menu one',
      'link[0][uri]' => '/node/1',
    ], 'Save');
    $this->menuId1 = preg_match("/\d+/", $this->getUrl());
    // Translate menu.
    $this->drupalPostform('/fr/admin/structure/menu/item/' . $this->menuId1 . '/edit/translations/add/en/fr', [
      'title[0][value]' => 'Menu une',
    ], 'Save');

    // Create term.
    $this->drupalPostform('/en/admin/structure/taxonomy/manage/tags/add', [
      'name[0][value]' => 'Term one',
    ], 'Save');
    $term1link = $this->getSession()->getPage()->find('css', '.messages--status A');
    $term1 = explode('/', $term1link->getAttribute('href'));
    $this->termId1 = $term1[count($term1) - 1];
    // Translate term.
    $this->drupalPostform('/fr/taxonomy/term/' . $this->termId1 . '/translations/add/en/fr', [
      'name[0][value]' => 'Term une',
    ], 'Save');
  }

  /**
   * Test translation output.
   */
  public function testTranslation() {
    $this->drupalPostform('/en/admin/reports/translate_side_by_side', [
      'target' => 'fr',
    ], 'Load');
    $this->assertSession()->elementContains('css', '.tsbstable_menumain_' . $this->menuId1 . ' td[lang="en"]', 'Menu one');
    $this->assertSession()->elementContains('css', '.tsbstable_menumain_' . $this->menuId1 . ' td[lang="fr"]', 'Menu une');

    $this->assertSession()->elementContains('css', '.tsbstable_node' . $this->node1->id() . '_title td[lang="en"]', 'Node one');
    $this->assertSession()->elementContains('css', '.tsbstable_node' . $this->node1->id() . '_title td[lang="fr"]', 'Nodule une');
    $this->assertSession()->elementContains('css', '.tsbstable_node' . $this->node2->id() . '_title td[lang="en"]', 'Node two');
    $this->assertTrue(($this->getSession()->getPage()->find('css', '.tsbstable_node' . $this->node2->id() . '_title td[lang="fr"]')->getHtml() === ''),
      '.tsbstable_node' . $this->node2->id() . '_title td[lang="fr"] not empty');

    $this->assertSession()->elementContains('css', '.tsbstable_block_content' . $this->blockId1 . '_body td[lang="en"]', 'Block one body');
    $this->assertSession()->elementContains('css', '.tsbstable_block_content' . $this->blockId1 . '_body td[lang="fr"]', 'Block une body');

    $this->assertSession()->elementContains('css', '.tsbstable_taxonomytags' . $this->termId1 . '_name td[lang="en"]', 'Term on');
    $this->assertSession()->elementContains('css', '.tsbstable_taxonomytags' . $this->termId1 . '_name td[lang="fr"]', 'Term une');

    // Test: Fill untranslated with source.
    $this->drupalPostform('/en/admin/reports/translate_side_by_side', [
      'target' => 'fr',
      'filluntranslated' => TRUE,
    ], 'Load');
    $this->assertSession()->elementContains('css', '.tsbstable_node' . $this->node2->id() . '_title td[lang="fr"]', 'Node two');
  }

}
