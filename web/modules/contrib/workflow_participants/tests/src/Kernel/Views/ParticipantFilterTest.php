<?php

namespace Drupal\Tests\workflow_participants\Kernel\Views;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\Tests\workflow_participants\Kernel\WorkflowParticipantsTestTrait;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Confirm nodes are limited for participants and non-participants in a view.
 *
 * @group workflow_participants
 */
class ParticipantFilterTest extends ViewsKernelTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;
  use UserCreationTrait;
  use WorkflowParticipantsTestTrait;

  /**
   * Node author.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $author;

  /**
   * Editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor;

  /**
   * Reviewer user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $reviewer;

  /**
   * Participant storage.
   *
   * @var \Drupal\workflow_participants\WorkflowParticipantsStorageInterface
   */
  protected $participantStorage;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  public static $modules = [
    'content_moderation',
    'dynamic_entity_reference',
    'field',
    'filter',
    'node',
    'node_test_config',
    'system',
    'text',
    'user',
    'views',
    'workflows',
    'workflow_participants',
    'workflow_participants_test_views',
  ];

  /**
   * Views to enable.
   *
   * @var array
   */
  public static $testViews = ['participant_filter_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('workflow_participants');
    $this->installConfig([
      'content_moderation',
      'filter',
      'node',
      'node_test_config',
    ]);

    $this->enableModeration('node', 'default');

    // Create author.
    $this->author = $this->createUser([
      'create default content',
      'edit own default content',
      'manage own workflow participants',
    ]);

    // Create reviewer.
    $this->reviewer = $this->createUser([
      'access content',
      'can be workflow participant',
    ]);

    // Create editor.
    $this->editor = $this->createUser([
      'access content',
      'can be workflow participant',
    ]);

    // Create test node.
    $this->node = $this->createNode(['type' => 'default', 'uid' => $this->author->id()]);

    $this->participantStorage = $this->container->get('entity_type.manager')->getStorage('workflow_participants');

    // Load test views.
    ViewTestData::createTestViews(get_class($this), ['workflow_participants_test_views']);
  }

  /**
   * Confirm filter correctly limits nodes workflow participants can see.
   *
   * @dataProvider provideData
   */
  public function testParticipantFilterLimitsNodes($display, $participants, $settings, $users_with_results, $users_without_results) {
    $expected_results = [
      ['nid' => $this->node->id()],
    ];

    // Add participants to the node.
    foreach ($participants as $participant) {
      $type = "{$participant}s";
      /** @var \Drupal\workflow_participants\Entity\WorkflowParticipants $participants */
      $participantsObj = $this->participantStorage->loadForModeratedEntity($this->node);
      $participantsObj->{$type}[] = $this->{$participant}->id();
      $participantsObj->save();
    }

    $view = Views::getView('participant_filter_test');

    // Confirm participants that have been configured to see nodes can see them.
    foreach ($users_with_results as $user) {
      $this->setCurrentUser($this->{$user});

      $view->setDisplay($display);
      $options = $view->display_handler->getOption('filters');
      $options['participant_role_filter']['value'] = $settings;
      $view->displayHandlers->get($display)->overrideOption('filters', $options);
      $this->executeView($view);

      $mapping = ['nid' => 'nid'];
      $this->assertIdenticalResultset($view, $expected_results, $mapping, 'Expected result set to contain the test node(s)');
    }

    $view->destroy();

    // Confirm users not set as participant cannot see nodes.
    foreach ($users_without_results as $user) {
      $this->setCurrentUser($this->{$user});

      $view->setDisplay($display);
      $options = $view->display_handler->getOption('filters');
      $options['participant_role_filter']['value'] = $settings;
      $view->displayHandlers->get($display)->overrideOption('filters', $options);
      $this->executeView($view);

      $this->assertEmpty($view->result, 'Expecting user to not see node.');
    }
  }

  /**
   * Data provider.
   *
   * @return array
   *   Array of different test cases.
   */
  public function provideData() {
    return [
      [
        'display' => 'participant_page',
        'participants' => [
          'editor',
          'reviewer',
        ],
        'settings' => [
          'author' => 'author',
          'reviewer' => 0,
          'editor' => 0,
        ],
        'users_with_results' => [
          'author',
        ],
        'users_without_results' => [
          'editor',
          'reviewer',
        ],
      ],
      [
        'display' => 'participant_page',
        'participants' => [
          'reviewer',
          'editor',
        ],
        'settings' => [
          'author' => 0,
          'reviewer' => 'reviewer',
          'editor' => 0,
        ],
        'users_with_results' => [
          'reviewer',
        ],
        'users_without_results' => [
          'author',
          'reviewer',
        ],
      ],
      [
        'display' => 'participant_page',
        'participants' => [
          'reviewer',
          'editor',
        ],
        'settings' => [
          'author' => 0,
          'reviewer' => 'reviewer',
          'editor' => 'editor',
        ],
        'users_with_results' => [
          'reviewer',
          'editor',
        ],
        'users_without_results' => [
          'author',
        ],
      ],
    ];
  }

}
