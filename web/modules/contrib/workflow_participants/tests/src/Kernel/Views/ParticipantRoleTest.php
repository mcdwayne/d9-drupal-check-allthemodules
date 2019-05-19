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
 * Confirm field shows appropriate participant status.
 *
 * @group workflow_participants
 */
class ParticipantRoleTest extends ViewsKernelTestBase {

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
   * Second test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Participant storage.
   *
   * @var \Drupal\workflow_participants\WorkflowParticipantsStorageInterface
   */
  protected $participantStorage;

  /**
   * First test node.
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
  public static $testViews = ['participant_fields_test'];

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
    $this->user = $this->createUser([
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
   */
  public function testParticipantFilterLimitsNodes() {
    $display = 'participant_role';

    // Set current user to author of node.
    $this->setCurrentUser($this->author);
    $view = Views::getView('participant_fields_test');
    $view->setDisplay($display);
    $view->render();

    // Confirm result set not empty and the text Author is present.
    $this->assertCount(1, $view->result, "Result set should contain one entry.");
    $this->assertEquals('Author', $view->field['participant_role_field']->last_render_text, 'Author text not present');

    // Add reviewer to node.
    /** @var \Drupal\workflow_participants\Entity\WorkflowParticipants $participants */
    $participants = $this->participantStorage->loadForModeratedEntity($this->node);
    $participants->reviewers[] = $this->user->id();
    $participants->save();

    $view->destroy();
    $view->setDisplay($display);
    $this->executeView($view);

    // Set participant as current user.
    $this->setCurrentUser($this->user);
    $view->setDisplay($display);
    $view->render();

    // Confirm result set not empty and Reviewer text is found.
    $this->assertCount(1, $view->result, "Result set should contain one entry.");
    $this->assertEquals('Reviewer', $view->field['participant_role_field']->last_render_text, 'Reviewer text not present.');

    // Add editor to node.
    /** @var \Drupal\workflow_participants\Entity\WorkflowParticipants $participants */
    $participants = $this->participantStorage->loadForModeratedEntity($this->node);
    $participants->editors[] = $this->user->id();
    $participants->save();

    $view->destroy();
    $view->setDisplay($display);
    $this->executeView($view);

    // Set participant as current user.
    $this->setCurrentUser($this->user);
    $view->setDisplay($display);
    $view->render();

    // Confirm result set not empty and Reviewer, Editor text is found.
    $this->assertCount(1, $view->result, "Result set should contain one entry.");
    $this->assertRegExp('#Reviewer#', $view->field['participant_role_field']->last_render_text, 'Reviewer text not present.');
    $this->assertRegExp('#Editor#', $view->field['participant_role_field']->last_render_text, 'Editor text not present.');
    $this->assertNotRegExp('#Author#', $view->field['participant_role_field']->last_render_text, 'Author text should not be present.');
  }

}
