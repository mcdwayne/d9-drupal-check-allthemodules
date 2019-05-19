<?php

namespace Drupal\tmgmt_memory\Tests;

use Drupal\file\Entity\File;
use Drupal\tmgmt\Tests\EntityTestBase;

/**
 * Integration test for the MemoryManager service.
 *
 * @group tmgmt_memory
 */
class IntegrationTest extends EntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'tmgmt_memory',
    'tmgmt_local',
    'tmgmt_content',
    'ckeditor',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->addLanguage('ca');
    $this->addLanguage('fr');

    $this->createNodeType('article', 'Article', TRUE, FALSE);

    // Create admin user.
    $this->loginAsAdmin([
      'translate any entity',
      'create content translations',
      'edit any article content',
    ]);
  }

  /**
   * Test the Workflow with local translator.
   */
  public function testWorkflowWithLocalTranslator() {
    // Create assignee user and configure language abilities.
    $assignee = $this->drupalCreateUser([
      'provide translation services',
    ]);
    $this->drupalLogin($assignee);
    $edit = [
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'ca',
    ];
    $this->drupalPostForm('user/' . $assignee->id() . '/edit', $edit, t('Save'));

    // Create an english source node.
    $this->drupalLogin($this->admin_user);
    $node1 = $this->createTranslatableNode('article', 'en');
    $edit = [
      'title[0][value]' => 'The title',
      'body[0][summary]' => 'The summary',
      'body[0][value]' => '<p>First paragraph in source language.</p><p>Second paragraph in source language.</p>',
    ];
    $this->drupalPostForm('node/' . $node1->id() . '/edit', $edit, t('Save'));
    // Translate the node.
    $edit = [
      'items[' . $node1->id() . ']' => TRUE,
    ];
    $this->drupalPostForm('admin/tmgmt/sources', $edit, t('Request translation'));
    $edit = [
      'translator' => 'local',
      'settings[translator]' => $assignee->id(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $this->drupalLogin($assignee);
    $edit = [
      'title|0|value[translation]' => '(ca) The title',
      'body|0|value[translation][value]' => '<p>(ca) First paragraph in target language.</p><p>(ca) Second paragraph in target language.</p>',
      'body|0|summary[translation][value]' => '(ca) The summary',
    ];
    $this->drupalPostForm('translate/items/1', $edit, t('Save as completed'));
    $this->drupalLogin($this->admin_user);
    // Check memory is empty.
    $this->drupalGet('admin/tmgmt/memory');
    /** @var \SimpleXMLElement $xpath */
    $xpath = $this->xpath('//*[@id="views-form-tmgmt-memory-page-1"]/table/tbody/tr');
    $this->assertTrue(empty($xpath[0]));

    // Complete the translation.
    $this->drupalPostForm('admin/tmgmt/items/1', [], t('Save as completed'));

    // Check translation saved.
    $this->drupalGet('admin/tmgmt/memory');
    /** @var \SimpleXMLElement $xpath */
    $xpath = $this->xpath('//*[@id="views-form-tmgmt-memory-page-1"]/table/tbody/tr');
    $this->assertEqual(trim((String) $xpath[0]->td[6]), '(ca) The title');
    $this->assertEqual(trim((String) $xpath[1]->td[6]), '(ca) First paragraph in target language.');
    $this->assertEqual(trim((String) $xpath[2]->td[6]), '(ca) Second paragraph in target language.');
    $this->assertEqual(trim((String) $xpath[3]->td[6]), '(ca) The summary');

    // Create another node completely equal to the last one.
    $node2 = $this->createTranslatableNode('article', 'en');
    $edit = [
      'title[0][value]' => 'The title',
      'body[0][summary]' => 'The summary',
      'body[0][value]' => '<p>First paragraph in source language.</p><p>Second paragraph in source language.</p>',
    ];
    $this->drupalPostForm('node/' . $node2->id() . '/edit', $edit, t('Save'));
    // Translate the node.
    $edit = [
      'items[' . $node2->id() . ']' => TRUE,
    ];
    $this->drupalPostForm('admin/tmgmt/sources', $edit, t('Request translation'));
    $edit = [
      'translator' => 'local',
      'settings[translator]' => $assignee->id(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $this->drupalLogin($assignee);
    $this->drupalGet('translate');
    $this->assertNoLink(t('View'));
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/tmgmt/items/2');
    $this->assertText('(ca) The title');
    $this->assertText('&lt;p&gt;(ca) First paragraph in target language.&lt;/p&gt;&lt;p&gt;(ca) Second paragraph in target language.&lt;/p&gt;');
    $this->assertText('(ca) The summary');
    $this->drupalPostForm(NULL, [], t('Save as completed'));

    // Check translation not saved.
    $this->drupalGet('admin/tmgmt/memory');
    /** @var \SimpleXMLElement $xpath */
    $xpath = $this->xpath('//*[@id="views-form-tmgmt-memory-page-1"]/table/tbody/tr');
    $this->assertTrue(empty($xpath[4]));

    // Create another node with the same title and summary.
    $node3 = $this->createTranslatableNode('article', 'en');
    $edit = [
      'title[0][value]' => 'The title',
      'body[0][summary]' => 'The summary',
      'body[0][value]' => '<p>First paragraph in <span>source language</span>.</p><p>Second paragraph in <span>source language</span>.</p>',
    ];
    $this->drupalPostForm('node/' . $node3->id() . '/edit', $edit, t('Save'));
    // Translate the node.
    $edit = [
      'items[' . $node3->id() . ']' => TRUE,
    ];
    $this->drupalPostForm('admin/tmgmt/sources', $edit, t('Request translation'));
    $edit = [
      'translator' => 'local',
      'settings[translator]' => $assignee->id(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $this->drupalLogin($assignee);
    $this->drupalGet('translate');
    $this->clickLink(t('View'));
    $this->clickLink(t('Translate'));
    $edit = [
      'body|0|value[translation][value]' => '<p>(ca) Alternative translation for first paragraph in <span>target language</span>.</p><p>(ca) Alternative translation for second paragraph in <span>target language</span>.</p>',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save as completed'));
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/tmgmt/items/3');
    $this->assertText('(ca) The title');
    $this->assertText('&lt;p&gt;(ca) Alternative translation for first paragraph in &lt;span&gt;target language&lt;/span&gt;.&lt;/p&gt;&lt;p&gt;(ca) Alternative translation for second paragraph in &lt;span&gt;target language&lt;/span&gt;.&lt;/p&gt;');
    $this->assertText('(ca) The summary');
    $this->drupalPostForm(NULL, [], t('Save as completed'));

    // Check translation not saved.
    $this->drupalGet('admin/tmgmt/memory');
    /** @var \SimpleXMLElement $xpath */
    $xpath = $this->xpath('//*[@id="views-form-tmgmt-memory-page-1"]/table/tbody/tr');
    $this->assertEqual(trim((String) $xpath[4]->td[6]), '(ca) Alternative translation for first paragraph in target language.');
    $this->assertEqual(trim((String) $xpath[5]->td[6]), '(ca) Alternative translation for second paragraph in target language.');

    // Import translation from TMX file.
    file_put_contents('public://example.tmx', file_get_contents(drupal_get_path('module', 'tmgmt_memory') . '/tests/testing_tmx/example.tmx'));
      /** @var \Drupal\file\Entity\File $file */
    $file = File::create(['uri' => 'public://example.tmx']);
    $edit = array(
      'files[import]' => $file->getFileUri(),
    );
    $this->drupalPostForm('admin/tmgmt/memory/import', $edit, t('Upload'));
    $this->assertText(t('File imported successfully.'));
    $this->drupalGet('admin/tmgmt/memory');
    /** @var \SimpleXMLElement $xpath */
    $xpath = $this->xpath('//*[@id="views-form-tmgmt-memory-page-1"]/table/tbody/tr');
    $this->assertEqual(trim((String) $xpath[6]->td[6]), 'Bonjour tout le monde!');
    $this->assertEqual(trim((String) $xpath[9]->td[6]), 'Un altre paràgraf.');

    // Test the highlight of the segments.
    $this->clickLink('View');
    $this->clickLink('View');
    $this->assertRaw('<span class="tmgmt_memory_highlight">The title</span>');
    $this->drupalGet('http://d8.dev/admin/tmgmt/memory/usages/2');
    $this->assertRaw('<span class="tmgmt_memory_highlight">(ca) The title</span>');
  }

}
