<?php

namespace Drupal\collect\Tests;

use Drupal\collect\Entity\Container;
use Drupal\collect\Plugin\collect\Model\FieldDefinition;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestFieldOverride;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * Tests comparing field definitions and capturing/recreation of entities.
 *
 * @group collect
 */
class EntityWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'collect',
    'collect_client',
    'collect_test',
    'node',
    'field_ui',
    'link',
    'entity_test',
    'block',
  );

  /**
   * Administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Place tasks, actions and page title blocks.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create article content type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
      'description' => 'Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.'
    ]);

    $this->adminUser = $this->drupalCreateUser([
      'administer collect',
      'administer collect_client',
      'access administration pages',
      'administer site configuration',
      'administer permissions',
      'administer nodes',
      'administer node fields',
      'administer content types',
      'access content overview',
      'create article content',
      'administer users',
      'edit own article content',
      'delete own article content',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests capturing entites using shortcut operation link.
   */
  public function testShortcutEntityCapture() {
    // Add a new node.
    $node = $this->drupalCreateNode();

    // Go to content page and click the "Capture (Collect)" operation link.
    $this->drupalGet('admin/content');
    $this->clickLink(t('Capture (Collect)'));
    $node_container = Container::load($this->getLatestId());
    $this->assertRaw(t('The @entity_type %label has been captured as a new container. You can access it <a href="@container_url">here</a>.', [
      '@entity_type' => 'content',
      '%label' => $node->label(),
      '@container_url' => $node_container->url(),
    ]));

    // Assert that created node has been captured.
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);
  }

  /**
   * Tests capturing entites and their references from selected entity types.
   */
  public function testContinuousEntityReferenceCapture() {
    \Drupal::service('module_installer')->uninstall(['entity_test'], FALSE);
    $this->drupalGet('admin/config/services/collect');

    // Assert the list of fields for reference capturing is displayed.
    $this->assertText(t('Content'));
    $this->assertText(t('List of fields for reference capturing'));
    $this->assertText(t('Authored by'));
    $this->assertText(t('The username of the content author.'));
    $this->assertText(t('User'));
    $this->assertText(t('All bundles'));

    // Assert that standard user reference field is selected by default.
    $this->assertFieldChecked('edit-entity-capture-node-fields-reference-fields-uid');
    $this->assertText(t('Standard user references have been selected by default.'));

    // Turn on continuous capture for Content and User entities.
    $this->drupalPostForm(NULL, [
      'entity_capture[node][continuous][enable]' => TRUE,
      'entity_capture[user][continuous][enable]' => TRUE,
      'entity_capture[node][fields][reference_fields][uid]' => 'uid',
    ], t('Save configuration'));

    // Install entity_test module and assert that user reference fields are
    // selected.
    \Drupal::service('module_installer')->install(['entity_test'], FALSE);
    $this->drupalGet('admin/config/services/collect');
    $this->assertText(t('Entity Test label'));
    $this->assertFieldChecked('edit-entity-capture-entity-test-label-fields-reference-fields-user-id');
    $this->assertText(t('Standard user references have been selected by default.'));

    // Add a new node.
    $node = $this->drupalCreateNode();

    // Assert that created (Content) entity and referenced (User) entity are
    // captured.
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 4);

    // Apply a Collect JSON model plugin.
    // Assert that 'Authored by' field contains link to the captured container
    // of referenced User entity.
    $article_container = $this->getLatestId();
    $this->drupalGet('admin/content/collect/' . $article_container);
    $this->clickLink(t('Set up a @plugin model', [
      '@plugin' => t('Collect JSON'),
    ]));
    $this->drupalPostForm(NULL, [
      'id' => 'collect_json_node_article',
    ], t('Save'));
    $user_container_origin_uri = Container::load($article_container - 2)->getOriginUri();
    $this->clickLink($user_container_origin_uri);
    $this->clickLink(t('Raw data'));
    $this->assertText($this->adminUser->getEmail());
    $this->assertText($user_container_origin_uri);

    // Edit Node details to test continuous entity update.
    $node->set('title', 'testContinuousEntityCapture')->save();
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 6);

    // Turn off continuous capture for Content and User entities.
    $this->drupalPostForm('admin/config/services/collect', array(
      'entity_capture[node][continuous][enable]' => FALSE,
      'entity_capture[user][continuous][enable]' => FALSE,
    ), t('Save configuration'));

    // Assert the Content and User entity types are not selected.
    $this->assertNoFieldChecked('edit-entity-capture-node-continuous-enable');
    $this->assertNoFieldChecked('edit-entity-capture-user-continuous-enable');

    // Create a new node and assert that is not captured.
    $this->drupalCreateNode();
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 8);
  }

  /**
   * Tests container revision tab is displayed.
   */
  public function testContainerRevisions() {
    // Assert that revision tab is displayed.
    $this->drupalPostForm('user/' . $this->adminUser->id() . '/edit', [
      'current_pass' => $this->adminUser->pass_raw,
      'mail' => 'admin@admin.com',
    ], t('Save'));
    $this->captureEntity('user', $this->adminUser->getUsername(), $this->adminUser->id());
    $this->clickLink(t('Revisions'));
    $this->assertText(t('Current revision'));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);

    // Tests URI pattern for entities that do not have 'canonical' link.
    $entity = EntityTestFieldOverride::create([]);
    $entity->save();
    $this->captureEntity('entity_test_field_override', 'Entity', $entity->id());
    $this->assertText(Url::fromUri('base:entity/' . $entity->getEntityTypeId() . '/' . $entity->bundle() . '/' . $entity->uuid())->setAbsolute()->toString());
  }

  /**
   * Tests multiple entity capturing.
   */
  public function testMultipleEntityCapture() {
    // Tests capturing multiple User entities.
    $entity_type = 'user';
    $this->drupalPostForm('admin/content/collect/capture', [
      'entity_type' => $entity_type,
    ], t('Select entity type'));
    $this->drupalPostForm(NULL, [
      'entity_type' => $entity_type,
      'operation' => 'multiple',
    ], t('Capture'));
    $this->assertText(t('All @entity_type entites have been captured.', [
      '@entity_type' => t('User'),
    ]));

    // Add articles.
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Article 1',
    ], t('Save and publish'));
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Article 2',
    ], t('Save and publish'));
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Article 3',
    ], t('Save and publish'));

    // Tests capturing multiple entities with the selected bundle.
    $entity_type = 'node';
    $bundle = 'article';
    $this->drupalPostForm('admin/content/collect/capture', [
      'entity_type' => $entity_type,
    ], t('Select entity type'));
    $this->drupalPostForm(NULL, [
      'entity_type' => $entity_type,
      'operation' => 'multiple',
      'bundle' => $bundle,
    ], t('Capture'));
    $this->assertText(t('All @entity_type entites from the bundle @bundle have been captured.', [
      '@entity_type' => t('Content'),
      '@bundle' => $bundle,
    ]));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 8);
  }

  /**
   * Tests Capture single entities and recreation of entities.
   */
  public function testRecreateEntities() {
    // Add a new field.
    $bundle = 'article';
    $bundle_path = "admin/structure/types/manage/$bundle";
    $field_name = 'link';
    $this->drupalPostForm("$bundle_path/fields/add-field", [
      'new_storage_type' => 'link',
      'label' => 'Link',
      'field_name' => $field_name,
    ], t('Save and continue'));

    // Add an article.
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Article',
      'field_link[0][uri]' => 'http://example.com',
    ], t('Save and publish'));

    // Capture the entity and ensure it is listed.
    $this->captureEntity('node', 'Article', 1);
    $entity_type = t('Content');
    $this->assertText(t('The @entity_type entity has been captured.', [
      '@entity_type' => $entity_type,
    ]));

    // Add suggested Collect JSON model plugin.
    $this->clickLink(t('Set up a @plugin model', [
      '@plugin' => t('Collect JSON'),
    ]));
    $this->drupalPostForm(NULL, ['id' => 'collect_json_node_article'], t('Save'));

    // Recreate the entity.
    $first_article_container_id = $this->getLatestId();
    $field_container_ids = \Drupal::entityQuery('collect_container')->condition('schema_uri', FieldDefinition::URI)->execute();
    $first_field_container_id = array_shift($field_container_ids);
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalGet("admin/content/collect/$first_article_container_id");
    $this->clickLink(t('Recreate this @entity_type entity', [
      '@entity_type' => $entity_type,
    ]));
    $this->assertText(t('The @entity_type has been created.', [
      '@entity_type' => $entity_type,
    ]));
    $this->clickLink(t('here'));
    $this->assertText(t('Article'));
    $this->drupalGet('admin/content');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);

    // Delete the field and recreate an entity from old container item.
    $this->drupalPostForm("$bundle_path/fields/node.$bundle.field_$field_name/delete", NULL, t('Delete'));
    $this->drupalGet("admin/content/collect/$first_article_container_id");
    $this->clickLink(t('Recreate this @entity_type entity', [
      '@entity_type' => t('Content'),
    ]));
    $this->assertRaw(t('Field %field_name does not exist.', [
      '%field_name' => "field_$field_name",
    ]));
    $this->assertText(t('The @entity_type has been created.', [
      '@entity_type' => $entity_type,
    ]));
    $this->drupalGet('admin/content');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 3);

    // Add a new field with the same name as previous one.
    // Change the storage type of the new field.
    // Recreate an entity from the old container item and assert warning.
    $this->drupalPostForm("$bundle_path/fields/add-field", [
      'new_storage_type' => 'string',
      'label' => 'Link',
      'field_name' => $field_name,
    ], t('Save and continue'));
    $this->drupalGet("admin/content/collect/$first_article_container_id");
    $this->clickLink(t('Recreate this @entity_type entity', [
      '@entity_type' => $entity_type,
    ]));
    // @todo add message about wrong field type.
    $this->assertRaw(t('Field %field_name skipped due to type mismatch.', [
      '%field_name' => "field_$field_name",
    ]));
    $this->assertText(t('The @entity_type has been created.', [
      '@entity_type' => $entity_type,
    ]));
    $this->drupalGet('admin/content');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 4);

    // Delete the old field and create new one with same name.
    // Edit an article and delete content from the field.
    // Capture the article.
    // Make the field required and try to create an entity.
    // Assert that required field is blank.
    $this->drupalPostForm("$bundle_path/fields/node.$bundle.field_$field_name/delete", NULL, t('Delete'));
    $this->drupalPostForm("$bundle_path/fields/add-field", [
      'new_storage_type' => 'link',
      'label' => 'Link',
      'field_name' => $field_name,
    ], t('Save and continue'));
    $this->drupalPostForm('node/2/edit', [
      'field_link[0][uri]' => '',
    ], t('Save and keep published'));
    $this->captureEntity('node', 'Article', 2);
    $this->drupalPostForm('admin/structure/collect/model/manage/collect_json_node_article', [], t('Save'));
    $this->drupalPostForm("$bundle_path/fields/node.article.field_link", [
      'label' => $field_name,
      'required' => TRUE,
    ], t('Save settings'));
    $this->drupalGet("admin/content/collect/$first_article_container_id");
    $this->clickLink(t('Recreate this @entity_type entity', [
      '@entity_type' => $entity_type,
    ]));
    $this->drupalGet('node/5/edit');
    $this->assertFieldById('edit-field-link-0-uri', '');

    // Update the field with cardinality 2.
    $this->drupalPostForm("$bundle_path/fields/node.$bundle.field_$field_name/storage", [
      'cardinality' => 'number',
      'cardinality_number' => 2,
    ], t('Save field settings'));

    // Fill the fields, capture the entity, check the new cardinality on the
    // definition model, recreate the entity and assert that both fields are
    // filled.
    $this->drupalPostForm('node/3/edit', [
      'field_link[0][uri]' => 'http://example.net',
      'field_link[1][uri]' => 'http://example.com',
    ], t('Save and keep published'));
    $this->captureEntity('node', 'Article', 3);
    $multiple_field_container_id = $this->getLatestId();
    $this->drupalGet("admin/content/collect/$first_field_container_id");
    $this->clickLink(t('Raw data'));
    $this->assertText('&quot;cardinality&quot;: 2,');
    // @todo here the link field is present on model with an outdated
    //   cardinality (1) setting while the definition container is updated (2).
    //   Make the model autoupdate properly.
    $this->drupalGet('admin/structure/collect/model/manage/collect_json_node_article');

    $this->drupalGet("admin/content/collect/$multiple_field_container_id");
    $this->clickLink(t('Recreate this @entity_type entity', [
      '@entity_type' => $entity_type,
    ]));
    $this->drupalGet('node/6/edit');
    $this->assertFieldById('edit-field-link-0-uri', 'http://example.net');
    $this->assertFieldById('edit-field-link-1-uri', 'http://example.com');

    // Update the field with cardinality 1 and test the warning about different
    // field cardinality.
    $field_storage = FieldStorageConfig::load("node.field_$field_name");
    $field_storage->setCardinality(1)->save();
    $this->drupalGet("admin/content/collect/$multiple_field_container_id");
    $this->clickLink(t('Recreate this @entity_type entity', [
      '@entity_type' => $entity_type,
    ]));
    $this->assertRaw(t('Field %field_name has a different cardinality.', [
      '%field_name' => "field_$field_name",
    ]));
    $count_message = t('%name: this field cannot hold more than @count values.', [
      '%name' => $field_name,
      '@count' => 1,
    ]);
    $this->assertRaw(t('Invalid value for %field_name: @message', [
      '%field_name' => "field_$field_name",
      '@message' => $count_message,
    ]));
    $this->assertText(t('The entity could not be recreated.'));

    // Test Capture entity form to invalid inputs and check the error messages.
    $this->drupalPostForm('admin/content/collect/capture', NULL, t('Capture'));
    $this->assertText(t('Entity type field is required.'));
    $this->assertText(t('Operation field is required.'));
    $entity_type = 'user';
    $this->drupalPostForm('admin/content/collect/capture', array(
      'entity_type' => $entity_type,
    ), t('Select entity type'));
    $this->assertFieldByName('entity');
    $this->assertNoFieldByName('bundle');
    $this->drupalPostForm(NULL, array(
      'entity_type' => $entity_type,
      'operation' => 'single',
    ), t('Capture'));
    $this->assertText('You need to enter entity title and id.');

    // Test capturing single User entity.
    $this->captureEntity($entity_type, $this->adminUser->getUsername(), $this->adminUser->id());
    $user_container_id = $this->getLatestId();
    $this->assertText(t('The @entity_type entity has been captured.', [
      '@entity_type' => t('User'),
    ]));
    $this->drupalGet("admin/content/collect/$user_container_id");
    $user_container_url = $this->getUrl();
    $this->clickLink(t('Raw data'));
    $this->assertText($this->adminUser->getUsername());

    // Tests creating an entity that cannot be created and checks the error
    // messages.
    $this->drupalGet($user_container_url);
    $this->clickLink(t('Set up a @plugin model', [
      '@plugin' => t('Collect JSON'),
    ]));
    $this->drupalPostForm(NULL, [
      'id' => 'collect_json_user',
    ], t('Save'));
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalGet($user_container_url);
    $this->clickLink(t('Recreate this @content_type entity', [
      '@content_type' => t('User'),
    ]));
    $this->assertText(t('Invalid value for @field_name: The username @username is already taken.', [
      '@field_name' => 'name',
      '@username' => SafeMarkup::checkPlain($this->adminUser->getUsername()),
    ]));
    $this->assertText(t('Invalid value for @field_name: The email address @email is already taken.', [
      '@field_name' => 'mail',
      '@email' => $this->adminUser->getEmail(),
    ]));
    $this->assertText(t('The entity could not be recreated.'));

    // Try to recreate an entity with missing bundle in the system.
    $this->drupalPostForm('admin/content/', [
      'node_bulk_form[0]' => TRUE,
      'node_bulk_form[1]' => TRUE,
      'node_bulk_form[2]' => TRUE,
      'node_bulk_form[3]' => TRUE,
      'node_bulk_form[4]' => TRUE,
      'node_bulk_form[5]' => TRUE,
      'action' => 'node_delete_action'
    ], t('Apply to selected items'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->drupalPostForm('admin/structure/types/manage/article/delete', [], t('Delete'));
    $this->drupalGet('admin/content/collect/' . $first_article_container_id);
    $this->clickLink(t('Recreate this @content_type entity', [
      '@content_type' => t('Content'),
    ]));
    $missing_fields_button = t('Create missing fields');
    $this->assertText(t('The entity could not be recreated. The bundle @bundle is missing. Use @missing_fields button to create it before continuing with this operation.', ['@bundle' => 'article', '@missing_fields' => $missing_fields_button]));
    $this->clickLink($missing_fields_button);
    $this->assertLink($missing_fields_button);

    // Tests creating an entity from container item which schema URI does not
    // match entity schema URI.
    $this->fetchWebResource(200);
    $this->drupalGet('admin/content/collect/create-entity/' . $this->getLatestId());
    $this->assertResponse(403);

    // Recreate an entity whose bundle is not missing.
    // Create a new user.
    $user = User::create([
      'mail' => 'test@example.org',
      'name' => 'test',
    ]);
    $user->save();

    // Capture a user.
    $this->drupalGet('admin/people');
    $this->assertLink(t('Capture (Collect)'));
    $this->captureEntity('user', 'test', $user->id());

    // Remove a user entity.
    $user->delete();
    $this->drupalGet('admin/people');
    $this->assertNoRaw('test@example.org');

    // Recreate a captured entity.
    $this->drupalGet('admin/content/collect/' . $this->getLatestId());
    $this->clickLink(t('Recreate this @entity entity', ['@entity' => t('User')]));
    $this->assertText(t('The @entity has been created', ['@entity' => t('User')]));
    $created_user_id = \Drupal::entityQuery('user')->condition('mail', 'test@example.org')->range(0, 1)->execute();
    $this->drupalGet('user/' . reset($created_user_id) . '/edit');
    $this->assertRaw('test@example.org');
    $this->assertText('test');
  }

  /**
   * Tests comparison of Field definitions data.
   */
  public function testCompareFieldDefinition() {
    $bundle = 'article';
    $bundle_path = "admin/structure/types/manage/$bundle";
    $field_name = 'link';
    // Add an article.
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Article',
    ], t('Save and publish'));

    // Add a new link field with cardinality 1.
    $this->drupalPostForm("$bundle_path/fields/add-field", [
      'new_storage_type' => 'string',
      'label' => 'Test link label',
      'field_name' => $field_name,
    ], t('Save and continue'));

    // Capture the article.
    $this->captureEntity('node', 'Article', 1);
    $article_container_id = $this->getLatestId();

    // Assert there is no button on the field definition model page.
    $this->drupalGet('admin/structure/collect/model/manage/collectjson_definition');
    $this->assertNoLink(t('Compare with current Field Definition data'));

    // Apply a model plugin.
    $this->drupalGet('admin/content/collect/' . $article_container_id);
    $this->clickLink(t('Set up a @plugin model', [
      '@plugin' => t('Collect JSON'),
    ]));
    $this->drupalPostForm(NULL, [
      'id' => 'collect_json_node_article',
    ], t('Save'));

    // Assert that compared field definitions are not the same.
    $this->drupalGet('admin/structure/collect/model/manage/collect_json_node_article');
    $this->assertNoLink(t('Create missing fields'));
    $this->clickLink(t('Compare with current Field Definition data'));
    $this->assertText(t('Compared Field Definitions are the same.'));

    // Change the link label.
    $this->drupalPostForm("$bundle_path/fields/node.$bundle.field_$field_name", [
      'label' => 'Changed link label',
    ], t('Save settings'));

    // Assert the changes are displayed.
    $this->drupalGet('admin/structure/collect/model/manage/collect_json_node_article/diff');
    $this->assertText('Test link');
    $this->assertText('Changed link label');

    // Delete the link field.
    $this->drupalPostForm("$bundle_path/fields/node.$bundle.field_$field_name/delete", [], t('Delete'));

    // Create missing field from current system.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalGet('admin/structure/collect/model/manage/collect_json_node_article');
    $this->clickLink(t('Create missing fields'));
    $this->assertText(t('Missing field @field_name is successfully created.', [
      '@field_name' => "field_$field_name",
    ]));
    $this->drupalGet("$bundle_path/fields/node.$bundle.field_$field_name");
    $this->assertText('Test link');

    // Change field link cardinality to 2.
    // Capture the articles to update the field definition container.
    // Delete the link field and bundle.
    // Create missing bundle and missing field with storage settings.
    $this->drupalPostForm("$bundle_path/fields/node.$bundle.field_$field_name/storage", [
      'cardinality_number' => 2,
    ], t('Save field settings'));
    $this->captureEntity('node', 'Article', 1);
    $this->drupalPostForm("$bundle_path/fields/node.$bundle.field_$field_name/delete", [], t('Delete'));
    $this->drupalPostForm('node/1/delete', [], t('Delete'));
    $this->drupalPostForm("$bundle_path/delete", [], t('Delete'));
    $this->assertNoText('Use articles for time-sensitive content like news, press releases or blog posts.');
    $this->drupalGet('admin/structure/collect/model/manage/collect_json_node_article');
    $this->clickLink(t('Create missing fields'));
    $this->assertText(t('Missing field @field_name is successfully created.', [
      '@field_name' => "field_$field_name",
    ]));
    $this->assertText(t('Missing bundle @bundle is created.', [
      '@bundle' => t('Article'),
    ]));
    $this->drupalGet('admin/structure/types');
    $this->assertText(t('Article'));
    $this->assertRaw('Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.');
    $this->drupalGet("$bundle_path/fields/node.$bundle.field_$field_name/storage");
    $this->assertFieldByName('cardinality_number', 2);

    // Assert we have no permission to access the page.
    $this->drupalLogout();
    $this->drupalGet('admin/structure/collect/model/manage/collect_json_node_article/diff');
    $this->assertResponse(403);
    $this->drupalGet('admin/structure/collect/model/manage/collect_json_node_article/create-missing-fields');
    $this->assertResponse(403);
  }

  /**
   * Returns the highest stored ID of a given entity type.
   *
   * @param string $entity_type
   *   The entity type ID to get the ID for.
   *
   * @return int
   *   The highest ID of the stored entities.
   */
  protected function getLatestId($entity_type = 'collect_container') {
    $id_key = \Drupal::entityManager()->getStorage($entity_type)->getEntityType()->getKey('id');
    $ids = \Drupal::entityQuery($entity_type)
      ->sort($id_key, 'DESC')
      ->pager(1)
      ->execute();
    return current($ids);
  }

  /**
   * Captures an entity.
   */
  public function captureEntity($entity_type, $name, $id) {
    $this->drupalPostForm('admin/content/collect/capture', [
      'entity_type' => $entity_type,
    ], t('Select entity type'));
    $this->drupalPostForm(NULL, [
      'entity_type' => $entity_type,
      'operation' => 'single',
      'entity' => $name . ' (' . $id . ')',
    ], t('Capture'));
  }

  /**
   * Fetches a web resource.
   */
  public function fetchWebResource($status_code, $accept_header = 'text/html') {
    $url = \Drupal::url('collect.make_response', ['status_code' => $status_code], ['absolute' => TRUE]);
    $this->drupalPostForm('admin/content/collect/url', [
      'url' => $url,
      'accept_header' => $accept_header,
    ], t('Get page'));
    return $url;
  }

}
