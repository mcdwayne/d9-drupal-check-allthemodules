<?php

namespace Drupal\collect_client\Tests;

use Drupal\collect\Entity\Container;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Collect Client UI.
 *
 * @group collect_client
 */
class CollectClientWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'rest',
    'basic_auth',
    'collect',
    'node',
    'collect_test',
    'field_ui',
    'link',
    'collect_client',
    'image',
    'block',
  ];

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

    // Create article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $permissions = [
      'administer collect',
      'administer collect_client',
      'access administration pages',
      'access content overview',
      'create article content',
      'administer content types',
      'administer node fields',
      'edit own article content',
      'administer site configuration',
      'administer permissions',
      'administer account settings',
      'administer user fields',
      'administer users',
    ];
    $this->createRole($permissions, 'collect');
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->adminUser->addRole('collect');
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);

    // Create a REST resource config entity for Collect Container.
    $service_url = Url::fromUri('base:collect/api/v1/submissions/', ['absolute' => TRUE])->toString();
    $this->container->get('config.factory')
      ->getEditable('collect_client.settings')
      ->set('service.url', $service_url)
      ->set('service.user', $this->adminUser->getUsername())
      ->set('service.password', $this->adminUser->pass_raw)
      ->save();
    $this->drupalPostForm('admin/people/permissions', [
      'anonymous[restful post collect]' => 1,
      'collect[restful post collect]' => 1,
    ], t('Save permissions'));

    // Place tasks and actions blocks.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests capturing entites using shortcut operation link.
   */
  public function testShortcutEntityCapture() {
    // Uninstall Collect module to make sure the operation defined by Collect
    // Client is visible.
    $this->container->get('module_installer')->uninstall(['collect'], FALSE);

    // Add a new node.
    $node = $this->drupalCreateNode();

    // Go to content page and click the "Capture (Collect)" operation link.
    $this->drupalGet('admin/content');
    $this->clickLink(t('Capture (Collect)'));
    $this->assertRaw(t('The @entity_type %label has been captured as a new container.', [
      '@entity_type' => 'content',
      '%label' => $node->label(),
    ]));

    // Install Collect module.
    $this->container->get('module_installer')->install(['collect'], FALSE);

    $this->container->get('cron')->run();

    // Assert that created node has been captured.
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);
  }

  /**
   * Tests capturing entites and their references from selected entity types.
   */
  public function testContinuousEntityReferenceCapture() {
    // Add a picture field to the user.
    $this->drupalPostForm('admin/config/people/accounts/fields/add-field', [
      'new_storage_type' => 'image',
      'label' => 'user_picture',
      'field_name' => 'user_picture',
    ], t('Save and continue'));

    // Add a profile picture to the User.
    $image = $this->drupalGetTestFiles('image')[0];
    $edit = [
      'files[field_user_picture_0]' => $image->uri,
    ];
    $this->drupalPostForm('user/' . $this->adminUser->id() . '/edit', $edit, t('Upload'));
    $edit = [
      'field_user_picture[0][alt]' => 'Profile picture',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    // Turn on continuous capture for Content entities.
    // Select user and user picture reference as reference fields.
    $this->drupalPostForm('admin/config/services/collect-client/entity', [
      'entity_capture[node][continuous][enable]' => TRUE,
      'entity_capture[node][fields][reference_fields][uid]' => 'uid',
      'entity_capture[user][fields][reference_fields][field_user_picture]' => 'field_user_picture',
    ], t('Save configuration'));

    // Add a new node.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->container->get('cron')->run();

    // Assert that created entities are captured.
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 6);

    // Assert that uuid property is added into captured data.
    $image_container = Container::load($this->getLatestId() - 4);
    $user_container = Container::load($this->getLatestId() - 2);
    $data = Json::decode($user_container->getData());
    $this->assertEqual($data['values']['_links']['field_user_picture'][0]['uuid'], $image_container->uuid());

    // Edit article details to test continuous entity update.
    $this->drupalPostForm('node/' . $node->id() . '/edit', [
      'body[0][value]' => 'Collect Client Update Test',
    ], t('Save'));
    $this->container->get('cron')->run();
    $this->drupalGet('admin/content/collect');

    // Updated article container should be added. File and user entities should
    // be skipped, as they are already sent and cached.
    $this->assertEqual(count($this->xpath('//tbody/tr')), 7);

    // Turn off continuous capture for Content entities.
    $this->drupalPostForm('admin/config/services/collect-client/entity', [
      'entity_capture[node][continuous][enable]' => FALSE,
      'entity_capture[node][fields][reference_fields][uid]' => FALSE,
      'entity_capture[user][fields][reference_fields][field_user_picture]' => FALSE,
    ], t('Save configuration'));

    // Assert the Content entity type is not selected.
    $this->assertNoFieldChecked('edit-entity-capture-node-continuous-enable');

    // Create a new article and assert that is not captured.
    $this->drupalCreateNode();
    $this->container->get('cron')->run();
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 7);
  }

  /**
   * Tests comparing containers captured by client and by server.
   */
  public function testCompareCapturedContainers() {
    // Capture a user through client.
    $this->captureEntity('user', SafeMarkup::checkPlain($this->adminUser->getUsername()), $this->adminUser->id());
    $this->container->get('cron')->run();

    $user_client_container = Container::load($this->getLatestId());
    $user_client_values = Json::decode($user_client_container->getData())['values'];

    // Capture a user through collect.
    $this->drupalPostForm('admin/content/collect/capture', [
      'entity_type' => 'user',
    ], t('Select entity type'));
    $this->drupalPostForm(NULL, [
      'entity_type' => 'user',
      'operation' => 'single',
      'entity' => SafeMarkup::checkPlain($this->adminUser->getUsername()) . ' (' . $this->adminUser->id() . ')',
    ], t('Capture'));

    $user_server_container = Container::load($this->getLatestId());
    $user_server_values = Json::decode($user_server_container->getData())['values'];

    // Assert that both containers are the same.
    $this->assertEqual($user_server_values, $user_client_values);
  }

  /**
   * Tests single entity capture.
   */
  public function testSingleEntityCapture() {
    $entity_type = t('Content');
    $bundle = 'article';
    $bundle_path = "admin/structure/types/manage/$bundle";
    // Add an article.
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Article',
    ], t('Save'));

    // Capture the entity and ensure it is listed.
    $this->captureEntity('node', 'Article', 1);
    $this->container->get('cron')->run();
    $this->assertText(t('The @entity_type entity has been captured.', [
      '@entity_type' => $entity_type,
    ]));
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);

    // Capture the entity again. New value container should not be created.
    $this->captureEntity('node', 'Article', 1);
    $this->container->get('cron')->run();
    $this->assertText(t('The @entity_type entity has been captured.', [
      '@entity_type' => $entity_type,
    ]));
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);

    $article_container_id = $this->getLatestId();
    $field_definition_container_id = $this->getLatestId() - 1;

    // Setup Collect JSON model for article content type.
    $this->drupalGet('admin/content/collect/' . $article_container_id);
    $this->clickLink(t('Set up a @plugin model', [
      '@plugin' => t('Collect JSON'),
    ]));
    $this->drupalPostForm(NULL, [
      'id' => 'collect_json_node_article',
    ], t('Save'));

    // Add a new field.
    $field_name = 'link';
    $this->drupalPostForm("$bundle_path/fields/add-field", [
      'new_storage_type' => 'link',
      'label' => 'Link',
      'field_name' => $field_name,
    ], t('Save and continue'));

    // Update the article and capture it again.
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
    $this->drupalPostForm('node/1/edit', [
      'title[0][value]' => 'Article Update',
      'field_link[0][uri]' => 'http://example.com',
      'field_link[0][title]' => 'Example.com',
    ], t('Save'));
    $this->captureEntity('node', 'Article', 1);
    $this->container->get('cron')->run();

    // Assert that field definition is updated.
    $this->drupalGet("admin/content/collect/$field_definition_container_id");
    $this->assertText('Link (field_link)');
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);

    // Tests Capture entity form to invalid inputs and check the error messages.
    $this->drupalPostForm('admin/content/collect-client/entity/capture', [], t('Capture'));
    $this->assertText(t('Entity type field is required.'));
    $this->assertText(t('Operation field is required.'));
    $entity_type = 'user';
    $this->drupalPostForm('admin/content/collect-client/entity/capture', [
      'entity_type' => $entity_type,
    ], t('Select entity type'));
    $this->assertFieldByName('entity');
    $this->assertNoFieldByName('bundle');
    $this->drupalPostForm(NULL, [
      'entity_type' => $entity_type,
      'operation' => 'single',
    ], t('Capture'));
    $this->assertText('You need to enter entity title and id.');
  }

  /**
   * Tests multiple entity capturing.
   */
  public function testMultipleEntityCapture() {
    // Tests capturing multiple entities.
    $entity_type = 'user';
    $this->drupalPostForm('admin/content/collect-client/entity/capture', [
      'entity_type' => $entity_type,
    ], t('Select entity type'));
    $this->drupalPostForm(NULL, [
      'entity_type' => $entity_type,
      'operation' => 'multiple',
    ], t('Capture'));
    $this->container->get('cron')->run();
    $this->assertText(t('All @entity_type entites have been captured.', [
      '@entity_type' => t('User'),
    ]));

    // Add articles.
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Article 1',
    ], t('Save'));
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Article 2',
    ], t('Save'));
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Article 3',
    ], t('Save'));

    // Tests capturing multiple entities with the selected bundle.
    $this->drupalPostForm('admin/content/collect-client/entity/capture', [
      'entity_type' => 'node',
    ], t('Select entity type'));
    $this->drupalPostForm(NULL, [
      'entity_type' => 'node',
      'operation' => 'multiple',
      'bundle' => 'article',
    ], t('Capture'));
    $this->container->get('cron')->run();
    $this->assertText(t('All @entity_type entites from the bundle @bundle have been captured.', [
      '@entity_type' => t('Content'),
      '@bundle' => 'article',
    ]));
    $this->drupalGet('admin/content/collect');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 8);
  }

  /**
   * Captures an entity.
   */
  public function captureEntity($entity_type, $name, $id) {
    $this->drupalPostForm('admin/content/collect-client/entity/capture', [
      'entity_type' => $entity_type,
    ], t('Select entity type'));
    $this->drupalPostForm(NULL, [
      'entity_type' => $entity_type,
      'operation' => 'single',
      'entity' => $name . ' (' . $id . ')',
    ], t('Capture'));
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
    $id_key = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->getEntityType()
      ->getKey('id');
    $ids = $this->container->get('entity.query')->get($entity_type)
      ->sort($id_key, 'DESC')
      ->pager(1)
      ->execute();
    return current($ids);
  }

}
