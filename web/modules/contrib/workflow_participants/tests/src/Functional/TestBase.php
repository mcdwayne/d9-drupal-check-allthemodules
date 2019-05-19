<?php

namespace Drupal\Tests\workflow_participants\Functional;

use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workflow_participants\Kernel\WorkflowParticipantsTestTrait;

/**
 * Base class for functional workflow participant tests.
 */
abstract class TestBase extends BrowserTestBase {

  use NodeCreationTrait;
  use WorkflowParticipantsTestTrait;

  /**
   * User with workflow participant permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Users that can be reviewers or editors.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $participants;

  /**
   * Workflow participant storage.
   *
   * @var \Drupal\workflow_participants\WorkflowParticipantsStorageInterface
   */
  protected $participantStorage;

  /**
   * A node to test with.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Moderation information.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'node',
    'system',
    'user',
    'workflow_participants',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->placeBlock('local_tasks_block');

    // Add a node type and enable content moderation.
    $this->createContentType(['type' => 'article']);

    $this->enableModeration('node', 'article');

    $this->node = $this->createNode([
      'type' => 'article',
      'moderation_state' => 'draft',
    ]);

    // Setup a role that can be participants.
    $role = $this->createRole([
      'can be workflow participant',
      'access user profiles',
      'access content',
    ]);

    // Dummy admin user to avoid uid 1 super perms.
    $this->createUser();
    // Real admin user.
    $this->adminUser = $this->createUser([
      'administer workflows',
      'manage workflow participants',
      'administer nodes',
      'edit any article content',
      'view any unpublished content',
    ]);
    $this->drupalLogin($this->adminUser);

    // Create 10 participants.
    foreach (range(1, 10) as $i) {
      $account = $this->createUser();
      if (in_array($i, [1, 2, 3, 4])) {
        // Users 1 through 4 can be participants.
        $account->addRole($role);
      }
      $account->save();
      $this->participants[$i] = $account;
    }

    $this->moderationInfo = $this->container->get('content_moderation.moderation_information');
    $this->participantStorage = $this->container->get('entity_type.manager')->getStorage('workflow_participants');
  }

}
