<?php
/**
 * @file
 * Contains \Drupal\collect\Tests\RelationWebTest.
 */

namespace Drupal\collect\Tests;

use Drupal\collect\Entity\Relation;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the relations UI.
 *
 * @group collect
 */
class RelationWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a user.
    $this->user = $this->createUser(['administer collect']);

    // Place tasks, actions and page title blocks.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests relation CRUD in the UI.
   */
  public function testRelationCrudUi() {
    // Unauthorized user should not have access.
    $this->drupalGet('admin/content/collect/relation');
    $this->assertResponse(403);

    // Check the overview.
    $this->drupalLogin($this->user);
    $this->drupalGet(t('admin/content/collect'));
    $this->clickLink(t('Relations'));
    $this->assertUrl('admin/content/collect/relation');
    $this->assertText(t('Relations'));
    // Filter elements should be present.
    $this->assertFieldByName('source_uri');
    $this->assertFieldByName('target_uri');
    $this->assertFieldByName('relation_uri');
    // The list should be empty.
    $this->assertText(t('No relation available.'));

    // Create.
    $this->clickLink(t('Create a relation'));
    $this->assertUrl('admin/content/collect/relation/add');
    $this->drupalPostForm(NULL, [
      'source_uri[0][value]' => 'abc',
      'target_uri[0][value]' => 'def',
      'relation_uri[0][value]' => 'ghi',
    ], t('Save'));
    $relations = Relation::loadMultiple();
    $relation = reset($relations);
    $this->assertRaw(t('The @content_type %label has been added.', ['@content_type' => 'relation', '%label' => $relation->id()]));
    // The relation should be in the list.
    $this->assertNoText(t('No relation available.'));
    $this->assertText('abc');
    $this->assertText('def');
    $this->assertText('ghi');

    // Review.
    $this->clickLink($relation->id());
    $this->assertUrl('admin/content/collect/relation/' . $relation->id());
    $this->assertText('abc');
    $this->assertText('def');
    $this->assertText('ghi');

    // Update.
    $this->clickLink(t('Edit'));
    $this->assertUrl('admin/content/collect/relation/' . $relation->id() . '/edit');
    $this->assertRaw(t('Edit @content_type %label', ['@content_type' => 'relation', '%label' => $relation->id()]));
    $this->assertLink(t('Delete'));
    $this->drupalPostForm(NULL, [
      'source_uri[0][value]' => '123',
      'target_uri[0][value]' => '456',
      'relation_uri[0][value]' => '789',
    ], t('Save'));
    $this->assertRaw(t('The @content_type %label has been updated.', ['@content_type' => 'relation', '%label' => $relation->id()]));
    $this->assertText('123');
    $this->assertText('456');
    $this->assertText('789');

    // Delete.
    $this->clickLink(t('Delete'));
    $this->assertUrl('admin/content/collect/relation/' . $relation->id() . '/delete?destination=' . \Drupal::url('entity.collect_relation.collection'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    // The list should be empty again.
    $this->assertText(t('No relation available.'));
    $this->assertNoText('123');
  }

  /**
   * Tests relation type CRUD in the UI.
   */
  public function testTypeCrudUi() {
    $this->drupalGet('admin/structure/collect/relation');
    $this->assertResponse(403);

    $this->drupalLogin($this->user);
    $this->drupalGet('admin/structure/collect/relation');
    $this->assertText(t('Relation types'));

    // Create.
    $this->clickLink(t('Add relation type'));
    $this->drupalPostForm(NULL, [
      'label' => 'Aaa',
      'id' => 'aaa',
      'uri_pattern' => 'schema:aaa',
      'plugin_id' => 'generic',
    ], t('Save'));
    $this->assertRaw(t('The @entity_type %label has been added.', ['@entity_type' => 'relation type', '%label' => 'Aaa']));
    $this->assertText('schema:aaa');
    $this->assertText(t('Generic'));

    // Update.
    $this->clickLink(t('Edit'));
    $this->assertRaw(t('Edit %label @entity_type', ['@entity_type' => 'relation type', '%label' => 'Aaa']));
    $this->assertFieldByName('label', 'Aaa');
    $this->assertFieldByName('id', 'aaa');
    $this->assertFieldByName('uri_pattern', 'schema:aaa');
    $this->drupalPostForm(NULL, [
      'label' => 'Aab',
      'uri_pattern' => 'schema:aab',
    ], t('Save'));
    $this->assertRaw(t('The @entity_type %label has been updated.', ['@entity_type' => 'relation type', '%label' => 'Aab']));
    $this->assertText('schema:aab');

    // Delete.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertNoText('schema:aab');
  }

}
