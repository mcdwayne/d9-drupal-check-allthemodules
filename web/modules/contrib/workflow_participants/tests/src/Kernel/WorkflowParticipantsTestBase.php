<?php

namespace Drupal\Tests\workflow_participants\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Base kernel test class for workflow participants.
 */
abstract class WorkflowParticipantsTestBase extends KernelTestBase {

  use UserCreationTrait;
  use WorkflowParticipantsTestTrait;

  /**
   * Participant storage.
   *
   * @var \Drupal\workflow_participants\WorkflowParticipantsStorageInterface
   */
  protected $participantStorage;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation',
    'dynamic_entity_reference',
    'entity_test',
    'filter',
    'node',
    'system',
    'user',
    'workflows',
    'workflow_participants',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('workflow_participants');
    $this->installConfig([
      'filter',
      'content_moderation',
      'workflow_participants',
      'system',
    ]);

    $this->enableModeration();

    $this->participantStorage = $this->container->get('entity_type.manager')->getStorage('workflow_participants');
  }

}
