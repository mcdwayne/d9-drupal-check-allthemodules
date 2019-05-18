<?php

/**
 * @file
 * Contains \Drupal\revision_ui\Tests\RevisionUiNodeTest.
 */

namespace Drupal\revision_ui\Tests;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Tests\NodeTestBase;
use Drupal\node\NodeInterface;

/**
 * Create a node with revisions and test viewing and reverting,
 * revisions field by field for users with access for this content type.
 *
 * @group revision_ui
 */
class RevisionUiNodeTest extends NodeTestBase {

  /**
   * An array of node revisions.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * Revision log messages.
   *
   * @var array
   */
  protected $revisionLogs;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'datetime',
    'language',
    'content_translation',
    'revision_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('it')->save();

    $field_storage_definition = [
      'field_name' => 'untranslatable_string_field',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ];
    $field_storage = FieldStorageConfig::create($field_storage_definition);
    $field_storage->save();

    $field_definition = [
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ];
    $field = FieldConfig::create($field_definition);
    $field->save();

    // Create and log in user.
    $web_user = $this->drupalCreateUser(
      [
        'view page revisions',
        'revert page revisions',
        'delete page revisions',
        'edit any page content',
        'delete any page content',
        'translate any entity',
      ]
    );

    $this->drupalLogin($web_user);

    // Create initial node.
    $node = $this->drupalCreateNode();
    $settings = get_object_vars($node);
    $settings['revision'] = 1;
    $settings['isDefaultRevision'] = TRUE;

    $nodes = [];
    $logs = [];

    // Get original node.
    $nodes[] = clone $node;

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $logs[] = $node->revision_log = $this->randomMachineName(32);

      // Create revision with a random title and body and update variables.
      $node->title = $this->randomMachineName();
      $node->body = [
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      ];
      $node->untranslatable_string_field->value = $this->randomString();
      $node->setNewRevision();

      // Edit the 2nd revision with a different user.
      if ($i == 1) {
        $editor = $this->drupalCreateUser();
        $node->setRevisionAuthorId($editor->id());
      }
      else {
        $node->setRevisionAuthorId($web_user->id());
      }

      $node->save();

      // Make sure we get revision information.
      $node = Node::load($node->id());
      $nodes[] = clone $node;
    }

    $this->nodes = $nodes;
    $this->revisionLogs = $logs;
  }


  /**
   * Tests the revision fields are correctly and independently reverted.
   */
  public function testRevisionRevert() {

    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $nodes = $this->nodes;
    $checked_fields = [
      'edit-revert-title',
      'edit-revert-body',
      'edit-revert-untranslatable-string-field',
    ];

    // Get latest revision for simple checks.
    $node = $nodes[3];

    // Confirm revision page with list of fields.
    $this->drupalGet("node/" . $node->id() . "/revisions/" . $nodes[1]->getRevisionid() . "/revert");
    $this->assertRaw(t('Choose the fields you want to revert to the revision from %revision-date.',
      ['%revision-date' => format_date($nodes[1]->getRevisionCreationTime())]), 'On field revision form.');
    $this->assertText('Changed content');
    foreach ($checked_fields as $field) {
      $this->assertFieldChecked($field);
    }

    // Uncheck the body field.
    $post_revert = ['revert_body' => FALSE];

    // Confirm revision fields are reverted correctly and independently.
    $this->drupalPostForm("node/" . $node->id() . "/revisions/" . $nodes[1]->getRevisionid() . "/revert",
      $post_revert, t('Revert'));
    $this->assertRaw(t('@type %title has been reverted to the revision from %revision-date.', [
      '@type' => 'Basic page',
      '%title' => $nodes[1]->label(),
      '%revision-date' => format_date($nodes[1]->getRevisionCreationTime()),
    ]), 'Revision reverted.');
    $node_storage->resetCache([$node->id()]);
    $reverted_node = $node_storage->load($node->id());
    // Confirm untranslated field is revised.
    $this->assertTrue(($nodes[1]->untranslatable_string_field->value == $reverted_node->untranslatable_string_field->value),
      'Untranslated field reverted correctly.');
    // Confirm body is same as unrevised.
    $this->assertTrue(($nodes[3]->body->value == $reverted_node->body->value && $nodes[1]->body->value != $reverted_node->body->value),
      'Body reverted correctly.');

  }

  /**
   * Tests the revision translations are correctly reverted field by field.
   */
  public function testRevisionTranslationRevert() {

    // Create a node and a few revisions.
    $node = $this->drupalCreateNode(['langcode' => 'en']);

    $initial_revision_id = $node->getRevisionId();
    $initial_title = $node->label();
    $this->createRevisions($node, 2);

    // Translate the node and create a few translation revisions.
    // The latter of these revisions will be the one used to revert the node.
    $translation = $node->addTranslation('it');
    $this->createRevisions($translation, 3);
    $revert_id  = $node->getRevisionId();
    $translated_title = $translation->label();
    $translated_untranslated_field = $translation->untranslatable_string_field->value;

    // Create a new revision for the default translation in-between a series of
    // translation revisions.
    $this->createRevisions($node, 1);
    $default_translation_title = $node->label();
    $default_translation_body = $node->body->value;

    // And create a few more translation revisions.
    $this->createRevisions($translation, 2);
    $translation_revision_id = $translation->getRevisionId();
    $latest_translated_body = $translation->body->value;
    $latest_untranslatable_string = $node->untranslatable_string_field->value;

    // Translation url.
    $revert_translation_url = Url::fromRoute('node.revision_revert_translation_confirm', [
      'node' => $node->id(),
      'node_revision' => $revert_id,
      'langcode' => 'it',
    ]);

    // Confirm revision page with list of fields.
    $this->drupalGet($revert_translation_url);
    $this->assertRaw(t('Choose the fields you want to revert to the revision from %revision-date.',
      ['%revision-date' => format_date($node->getRevisionCreationTime())]), 'On field revision form.');
    $this->assertText('Translated content');
    $this->assertText('Content shared among translations');

    // Uncheck the body field.
    $post_revert = ['revert_body' => FALSE];

    // Now revert the translation revision preceding the last default
    // translation revision, and check that the desired value was reverted but
    // the default translation value and the body was preserved.
    $this->drupalPostForm($revert_translation_url, $post_revert, t('Revert'));
    /** @var \Drupal\node\NodeStorage $node_storage */
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $node_storage->resetCache();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($node->id());

    $this->assertTrue($node->getRevisionId() > $translation_revision_id, 'New revision built.');
    // English node has to be the same as before.
    $this->assertEqual($node->label(), $default_translation_title, '(en) Title reverted correctly.');
    $this->assertEqual($node->body->value, $default_translation_body, '(en) Body reverted correctly.');
    $this->assertEqual($node->untranslatable_string_field->value, $latest_untranslatable_string, '(en) Untranslatable field reverted correctly');
    // Translated (it) node has to change at title.
    $this->assertEqual($node->getTranslation('it')->label(), $translated_title, '(it) Title reverted correctly.');
    $this->assertEqual($node->getTranslation('it')->body->value, $latest_translated_body, '(it) Body reverted correctly.');
    $this->assertEqual($node->getTranslation('it')->untranslatable_string_field->value, $latest_untranslatable_string, '(it) Untranslatable field reverted correctly');

    $latest_revision_id = $translation->getRevisionId();
    // Uncheck body field and check untranslatabel field.
    $post_revert = ['revert_body' => FALSE, 'revert_untranslatable_string_field' => TRUE];

    // Now revert the translation revision preceding the last default
    // translation revision again, and check that the desired value was reverted
    // but the default translation value was preserved. But in addition the
    // untranslated field will be reverted as well.
    $this->drupalPostForm($revert_translation_url, $post_revert, t('Revert'));
    $node_storage->resetCache();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->getRevisionId() > $latest_revision_id, 'New revision built.');
    // English node: untranslatable has changed.
    $this->assertEqual($node->untranslatable_string_field->value, $translated_untranslated_field, '(en) Untranslatable field reverted correctly');
    // Translated (it) node has to change at untranslatable field.
    $this->assertEqual($node->getTranslation('it')->untranslatable_string_field->value, $translated_untranslated_field, '(it) Untranslatable field reverted correctly');
  }

  /**
   * Creates a series of revisions for the specified node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param $count
   *   The number of revisions to be created.
   */
  protected function createRevisions(NodeInterface $node, $count) {
    for ($i = 0; $i < $count; $i++) {
      $node->title = $this->randomString();
      $node->body = [
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      ];
      $node->untranslatable_string_field->value = $this->randomString();
      $node->setNewRevision(TRUE);
      $node->save();
    }

  }

}
