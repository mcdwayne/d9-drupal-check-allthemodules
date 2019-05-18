<?php

namespace Drupal\Tests\collect_crm\Kernel;

use Drupal\collect\Entity\Model;
use Drupal\collect_crm\Plugin\collect\Processor\ActivityCreator;
use Drupal\crm_core_activity\Entity\Activity;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Tests features of ActivityCreator processor.
 *
 * @group collect_crm
 */
class ActivityCreatorTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'user',
    'serialization',
    'hal',
    'rest',
    'system',
    'views',
    'datetime',
    'node',
    'field',
    'text',
    'name',
    'options',
    'dynamic_entity_reference',
    'collect_common',
    'collect_crm',
    'crm_core_contact',
    'crm_core_activity',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['collect']);
    $this->installConfig(['collect_crm']);

    $this->installEntitySchema('crm_core_activity');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('collect_container');

    $this->installSchema('system', ['sequences']);
  }

  /**
   * Tests features of ActivityCreator processor.
   */
  public function testActivityCreator() {
    /** @var \Drupal\collect\CaptureEntity $entity_capturer */
    $entity_capturer = \Drupal::service('collect.capture_entity');

    // Create a user and capture it into a container.
    $first_user = User::create([
      'name' => 'First',
      'mail' => 'first@example.com',
    ]);
    $first_user->save();

    // Values container of captured user entity.
    $user_container = $entity_capturer->captureEntityInsert($first_user);

    // Apply a CollectJSON model plugin.
    $model = Model::create([
      'id' => 'collect_json_user',
      'uri_pattern' => $user_container->getSchemaUri(),
      'plugin_id' => 'collectjson',
      'container_revision' => TRUE,
      'label' => 'Collect Json User model',
    ]);
    $model->save();

    /** @var \Drupal\collect\TypedData\TypedDataProvider $typed_data_provider */
    $typed_data_provider = \Drupal::service('collect.typed_data_provider');

    // Create a new instance of ActivityCreator.
    $activity_creator_configuration = [
      'plugin_id' => 'activity_creator',
      'title_property' => '_default_title',
    ];
    $activity_creator = new ActivityCreator($activity_creator_configuration, 'activity_creator', NULL, \Drupal::logger('default'), $typed_data_provider, \Drupal::entityManager());
    $context = [];
    // Process the data of user container using ActivityCreator processor.
    $activity_creator->process($typed_data_provider->getTypedData($user_container), $context);
    $activities = Activity::loadMultiple();
    $user_created_activity = end($activities);

    // Activity created from a captured user has label: "@entity_type".
    $this->assertEquals('user ' . $first_user->getAccountName(), $user_created_activity->label());

    // Create a sample node type.
    $node_type = NodeType::create(['type' => 'collect', 'name' => 'Collect']);
    $node_type->save();

    // Create a node and capture it into a container.
    $node = Node::create([
      'type' => 'collect',
      'title' => 'Node',
      'uid' => $first_user->id(),
    ]);
    $node->save();

    // Values container of captured node entity.
    $node_container = $entity_capturer->capture($node);

    // Apply a CollectJSON model plugin.
    $model = Model::create([
      'id' => 'collect_json_node_collect',
      'uri_pattern' => $node_container->getSchemaUri(),
      'plugin_id' => 'collectjson',
      'container_revision' => TRUE,
      'label' => 'Collect Json Node Collect model',
    ]);
    $model->save();

    // Process the data of node container using ActivityCreator processor.
    $context = [];
    $activity_creator->process($typed_data_provider->getTypedData($node_container), $context);
    $activities = Activity::loadMultiple();
    $node_created_activity = end($activities);

    // Activity created from a captured node has label: "@entity_type @bundle".
    $this->assertEquals('node ' . $node_type->label() . ' ' . $node->label(), $node_created_activity->label());
  }

}
