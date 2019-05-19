<?php

namespace Drupal\Tests\wbm2cm\Kernel;

use Drupal\content_moderation\Plugin\WorkflowType\ContentModeration;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\workbench_moderation\Entity\ModerationState;
use Drupal\workbench_moderation\Entity\ModerationStateTransition;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\StateInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowTypeInterface;

/**
 * @covers  \Drupal\wbm2cm\WorkflowCollector
 * @group wbm2cm
 */
class WorkflowCollectorTest extends KernelTestBase {

  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'migrate',
    'node',
    'options',
    'system',
    'text',
    'user',
    'views',
    'wbm2cm',
    'workbench_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('node');

    $this->createState('archived', FALSE, TRUE);
    $this->createState('draft', FALSE, FALSE);
    $this->createState('review', FALSE, FALSE);
    $this->createState('legal_review', FALSE, FALSE);
    $this->createState('postponed', FALSE, FALSE);
    $this->createState('published', TRUE, TRUE);

    $states = [
      'archived',
      'draft',
      'review',
      'published',
    ];
    $this->createContentType('page', $states);
    $this->createContentType('article', $states);

    $states = [
      'archived',
      'draft',
      'review',
      'legal_review',
      'postponed',
      'published',
    ];
    $this->createContentType('press_release', $states);

    $states = [
      'archived',
      'draft',
      'review',
      'legal_review',
      'published',
    ];
    $this->createContentType('announcement', $states);
    $this->createContentType('offering', $states);

    $this->createTransition('archived', 'draft');
    $this->createTransition('draft', 'draft');
    $this->createTransition('draft', 'review');
    $this->createTransition('review', 'draft');
    $this->createTransition('review', 'legal_review');
    $this->createTransition('legal_review', 'draft');
    $this->createTransition('review', 'published');
    $this->createTransition('legal_review', 'published');
    $this->createTransition('published', 'archived');
    $this->createTransition('published', 'draft');

    module_load_install('wbm2cm');
    wbm2cm_install();
  }

  public function testWorkflowCollection() {
    $modules = ['content_moderation', 'workflows'];
    $this->enableModules($modules);
    wbm2cm_modules_installed($modules);

    $this->assertSame(3, \Drupal::entityQuery('workflow')->count()->execute());
    return;

    $this->assertWorkflow(
      'article__page',
      ['page', 'article'],
      ['archived', 'draft', 'review', 'published'],
      ['legal_review', 'postponed'],
      [
        'archived_draft',
        'draft_draft',
        'draft_review',
        'review_draft',
        'review_published',
        'published_draft',
        'published_archived',
      ],
      [
        'review_legal_review',
        'legal_review_draft',
        'legal_review_published',
      ]
    );
    $this->assertWorkflow(
      'press_release',
      ['press_release'],
      [
        'archived',
        'draft',
        'review',
        'legal_review',
        'postponed',
        'published',
      ],
      [],
      [
        'archived_draft',
        'draft_draft',
        'draft_review',
        'review_draft',
        'review_legal_review',
        'legal_review_draft',
        'review_published',
        'legal_review_published',
        'published_archived',
        'published_draft',
      ],
      []
    );
  }

  /**
   * Asserts various aspects of a workflow.
   *
   * @param string $id
   *   The workflow ID.
   * @param string[] $expected_node_types
   *   The content types that the workflow is expected to support.
   * @param string[] $expected_states
   *   The state IDs that the workflow is expected to have.
   * @param string[] $unexpected_states
   *   The state IDs that the workflow specifically should not have.
   * @param string[] $expected_transitions
   *   The transition IDs that the workflow is expected to have.
   * @param string[] $unexpected_transitions
   *   The transition IDs that the workflow specifically should not have.
   */
  protected function assertWorkflow($id, array $expected_node_types, array $expected_states, array $unexpected_states, array $expected_transitions, array $unexpected_transitions) {
    $workflow = Workflow::load($id);
    $this->assertInstanceOf(Workflow::class, $workflow);

    /** @var ContentModeration $plugin */
    $plugin = $workflow->getTypePlugin();
    $this->assertInstanceOf(ContentModeration::class, $plugin);

    $actual_node_types = $plugin->getBundlesForEntityType('node');
    foreach ($expected_node_types as $node_type) {
      $this->assertContains($node_type, $actual_node_types);
    }

    foreach ($expected_states as $state_id) {
      $this->assertState($plugin, $state_id);
    }
    foreach ($unexpected_states as $state_id) {
      $this->assertNoState($plugin, $state_id);
    }

    foreach ($expected_transitions as $transition_id) {
      $this->assertTransition($plugin, $transition_id);
    }
    foreach ($unexpected_transitions as $transition_id) {
      $this->assertNoTransition($plugin, $transition_id);
    }
  }

  /**
   * Asserts that a workflow type plugin has a specific state.
   *
   * @param \Drupal\workflows\WorkflowTypeInterface $plugin
   *   The workflow type plugin.
   * @param string $state_id
   *   The state ID.
   */
  protected function assertState(WorkflowTypeInterface $plugin, $state_id) {
    $this->assertInstanceOf(StateInterface::class, $plugin->getState($state_id));
  }

  /**
   * Asserts that a workflow type plugin does not have a specific state.
   *
   * @param \Drupal\workflows\WorkflowTypeInterface $plugin
   *   The workflow type plugin.
   * @param string $state_id
   *   The state ID.
   */
  protected function assertNoState(WorkflowTypeInterface $plugin, $state_id) {
    try {
      $plugin->getState($state_id);
      $this->fail();
    }
    catch (\InvalidArgumentException $e) {
      $this->assertTrue(TRUE);
    }
  }

  /**
   * Asserts that a workflow type plugin has a specific transition.
   *
   * @param \Drupal\workflows\WorkflowTypeInterface $plugin
   *   The workflow type plugin.
   * @param string $transition_id
   *   The transition ID.
   */
  protected function assertTransition(WorkflowTypeInterface $plugin, $transition_id) {
    $this->assertInstanceOf(TransitionInterface::class, $plugin->getTransition($transition_id));
  }

  /**
   * Asserts that a workflow type plugin does not have a specific transition.
   *
   * @param \Drupal\workflows\WorkflowTypeInterface $plugin
   *   The workflow type plugin.
   * @param string $transition_id
   *   The transition ID.
   */
  protected function assertNoTransition(WorkflowTypeInterface $plugin, $transition_id) {
    try {
      $plugin->getTransition($transition_id);
      $this->fail();
    }
    catch (\InvalidArgumentException $e) {
      $this->assertTrue(TRUE);
    }
  }

  /**
   * Creates a moderated content type.
   *
   * @param string $id
   *   The content type ID.
   * @param string[] $moderation_states
   *   The moderation states that the content type should allow.
   */
  protected function createContentType($id, array $moderation_states) {
    $this
      ->drupalCreateContentType([
        'type' => $id,
      ])
      ->setThirdPartySetting('workbench_moderation', 'enabled', TRUE)
      ->setThirdPartySetting('workbench_moderation', 'allowed_moderation_states', $moderation_states)
      ->save();
  }

  /**
   * Creates a moderation state.
   *
   * @param string $id
   *   The state ID.
   * @param bool $published
   *   Whether content in this state is published.
   * @param bool $default_revision
   *   Whether content in this state is the default revision.
   */
  protected function createState($id, $published, $default_revision) {
    ModerationState::create([
      'id' => $id,
      'label' => $this->randomMachineName(),
      'published' => $published,
      'default_revision' => $default_revision,
    ])->save();
  }

  /**
   * Creates a moderation state transition.
   *
   * @param string $from_state
   *   The moderation state from which to transition.
   * @param string $to_state
   *   The moderation state to which to transition.
   */
  protected function createTransition($from_state, $to_state) {
    ModerationStateTransition::create([
      'id' => "{$from_state}_{$to_state}",
      'label' => $this->randomMachineName(),
      'stateFrom' => $from_state,
      'stateTo' => $to_state,
    ])->save();
  }

}
