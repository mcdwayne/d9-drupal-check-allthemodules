<?php

namespace Drupal\Tests\evergreen\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Core\Form\FormState;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\evergreen\Entity\EvergreenContent;
use Drupal\evergreen\Entity\EvergreenConfig;
use Drupal\evergreen_form_test\Form\TestForm;

/**
 * Tests the new entity API for evergreen content.
 *
 * @group evergreen
 * @SuppressWarnings(StaticAccess)
 */
class EvergreenServiceTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'datetime', 'user', 'node', 'evergreen', 'evergreen_form_test'];

  /**
   * Setup.
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $install_schemas = ['user', 'node', 'evergreen_content'];
    foreach ($install_schemas as $schema) {
      $this->installEntitySchema($schema);
    }
    $this->service = \Drupal::service('evergreen');
  }

  /**
   * Test EvergreenService::getConfiguration()
   */
  public function testGetConfiguration() {
    $config = EvergreenConfig::create([
      'id' => 'node.page',
      'evergreen_entity_type' => 'node',
      'evergreen_bundle' => 'page',
      'evergreen_default_status' => EVERGREEN_STATUS_EVERGREEN,
    ]);
    $config->save();

    $entity = Node::create([
      'type' => 'page',
      'title' => 'My test page',
    ]);
    $entity->save();

    $configuration = $this->service->getConfiguration($entity);
    $this->assertTrue($configuration instanceof EvergreenConfig, 'Configuration should be an instance of EvergreenConfig');
    $this->assertEquals('node', $configuration->getEvergreenEntityType());
    $this->assertEquals('page', $configuration->getEvergreenBundle());
  }

  /**
   * Test EvergreenService::getConfiguration() when there is no configuration.
   */
  public function testGetConfigurationWithoutConfiguration() {
    $entity = Node::create([
      'type' => 'page',
      'title' => 'My test page',
    ]);
    $entity->save();
    $this->assertFalse($this->service->getConfiguration($entity), 'No configuration should be present for node.page');
  }

  /**
   * Test EvergreenService::getContent()
   */
  public function testGetContent() {
    $config = EvergreenConfig::create([
      'id' => 'node.page',
      'evergreen_entity_type' => 'node',
      'evergreen_bundle' => 'page',
      'evergreen_default_status' => EVERGREEN_STATUS_EVERGREEN,
    ]);
    $config->save();

    $entity = Node::create([
      'type' => 'page',
      'title' => 'My test page',
    ]);
    $entity->save();

    $content = $this->service->getContent($entity, $config);
    $this->assertTrue($content instanceof EvergreenContent, "The content should be an EvergreenContent entity");
    $this->assertTrue($content->isNew(), "The content should be new");
    $this->assertEquals(EVERGREEN_STATUS_EVERGREEN, $content->getEvergreenStatus(), "The content should match config status");
    $this->assertEquals('node', $content->getEvergreenEntityType());
    $this->assertEquals('page', $content->getEvergreenBundle());
  }

  /**
   * Test EvergreenService::getContent() with existing content.
   */
  public function testGetContentWithExistingContent() {
    $config = EvergreenConfig::create([
      'id' => 'node.page',
      'evergreen_entity_type' => 'node',
      'evergreen_bundle' => 'page',
      'evergreen_default_status' => EVERGREEN_STATUS_EVERGREEN,
    ]);
    $config->save();

    $entity = Node::create([
      'type' => 'page',
      'title' => 'My test page',
    ]);
    $entity->save();

    $content = $this->service->getContent($entity, $config);
    $content->set('evergreen_status', 0);
    $content->save();

    $loaded_content = $this->service->getContent($entity, $config);
    $this->assertTrue($loaded_content instanceof EvergreenContent, "The content should be an EvergreenContent entity");
    $this->assertFalse($loaded_content->isNew(), "The content should not be new");
    $this->assertEquals(0, $loaded_content->getEvergreenStatus(), "The content should not be evergreen");
  }

  /**
   * Test EvergreenService::isEnabled()
   */
  public function testIsEnabled() {
    $config = EvergreenConfig::create([
      'id' => 'node.page',
      'evergreen_entity_type' => 'node',
      'evergreen_bundle' => 'page',
      'evergreen_default_status' => EVERGREEN_STATUS_EVERGREEN,
    ]);
    $config->save();

    $entity = Node::create([
      'type' => 'page',
      'title' => 'My test page',
    ]);
    $entity->save();

    $this->assertTrue($this->service->isEnabled($entity), 'Page should detect that it is enabled');
  }

  /**
   * Test EvergreenService::entityHasExpired()
   */
  public function testEntityHasExpired() {
    $config = EvergreenConfig::create([
      'id' => 'node.page',
      'evergreen_entity_type' => 'node',
      'evergreen_bundle' => 'page',
      'evergreen_default_status' => EVERGREEN_STATUS_EVERGREEN,
    ]);
    $config->save();

    $entity = Node::create([
      'type' => 'page',
      'title' => 'My test page',
    ]);
    $entity->save();

    $this->assertFalse($this->service->entityHasExpired($entity), "The entity should not be expired");

    $content = $this->service->getContent($entity, $config);
    $content->set('evergreen_status', 0);
    $content->set('evergreen_expires', strtotime('-1 year'));
    $content->save();

    $this->assertTrue($this->service->entityHasExpired($entity), "The entity should be expired");
  }

  /**
   * Test that the evergreen form does not get added for forms without entities.
   */
  public function testAddFormOnFormWithoutEntity() {
    $form_state = new FormState();
    $form = [];
    $form_id = 'my_fake_form';

    $test_form = new TestForm();
    $form_state->setFormObject($test_form);

    $this->service->addForm($form, $form_state, $form_id);
    $this->assertTrue(empty($form));
  }

  /**
   * Test that the evergreen form does get added for forms with entities that are configured.
   */
  public function testAddFormOnFormWithEntity() {
    $config = EvergreenConfig::create([
      'id' => 'node.page',
      'evergreen_entity_type' => 'node',
      'evergreen_bundle' => 'page',
      'evergreen_default_status' => EVERGREEN_STATUS_EVERGREEN,
    ]);
    $config->save();

    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();

    $entity = Node::create([
      'type' => 'page',
      'title' => 'My test page',
      'uid' => 1,
    ]);

    $form_state = new FormState();
    $form_id = 'my_fake_form';

    $node_form = $this->container->get('entity.manager')
      ->getFormObject('node', 'edit')
      ->setEntity($entity);

    $form_builder = $this->container->get('entity.form_builder');
    $form = $form_builder->getForm($entity, 'edit');
    $form_state->setFormObject($node_form);

    $this->service->addForm($form, $form_state, $form_id);
    $this->assertTrue(isset($form['evergreen']), 'The evergreen form should be present but is missing');
  }

  /**
   * Test that the evergreen form does not get added for forms with entities that are not configured.
   */
  public function testAddFormOnFormWithWrongEntity() {
    $config = EvergreenConfig::create([
      'id' => 'node.page',
      'evergreen_entity_type' => 'node',
      'evergreen_bundle' => 'page',
      'evergreen_default_status' => EVERGREEN_STATUS_EVERGREEN,
    ]);
    $config->save();

    $type = NodeType::create(['type' => 'article', 'name' => 'article']);
    $type->save();
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();

    $entity = Node::create([
      'type' => 'article',
      'title' => 'My test article',
      'uid' => 1,
    ]);

    $form_state = new FormState();
    $form_id = 'my_fake_form';

    $node_form = $this->container->get('entity.manager')
      ->getFormObject('node', 'edit')
      ->setEntity($entity);

    $form_builder = $this->container->get('entity.form_builder');
    $form = $form_builder->getForm($entity, 'edit');
    $form_state->setFormObject($node_form);

    $this->service->addForm($form, $form_state, $form_id);
    $this->assertFalse(isset($form['evergreen']), 'The evergreen form should not be present');
  }

  /**
   * Test EvergreenServer::entityIsEvergreen()
   */
  public function testEntityIsEvergreen() {
    $config = EvergreenConfig::create([
      'id' => 'node.page',
      'evergreen_entity_type' => 'node',
      'evergreen_bundle' => 'page',
      'evergreen_default_status' => EVERGREEN_STATUS_EVERGREEN,
    ]);
    $config->save();

    $entity = Node::create([
      'type' => 'page',
      'title' => 'My test page',
    ]);
    $entity->save();

    // with no content entity, the entity should be evergreen b/c of the
    // default status
    $this->assertTrue($this->service->entityIsEvergreen($entity), "The node should be evergreen");

    // no add a content entity but keep the content set as evergreen
    $content = $this->service->getContent($entity, $config);
    $content->set('evergreen_status', EVERGREEN_STATUS_EVERGREEN);
    $content->set('evergreen_expires', strtotime('-1 year'));
    $content->save();
    $this->assertTrue($this->service->entityIsEvergreen($entity), "The node should still be evergreen");

    $content->set('evergreen_status', 0);
    $content->save();
    $this->assertFalse($this->service->entityIsEvergreen($entity), "The node should not be evergreen anymore");
  }

}
