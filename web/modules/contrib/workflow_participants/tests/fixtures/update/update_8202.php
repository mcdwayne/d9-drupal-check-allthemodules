<?php

/**
 * @file
 * Contains database additions for testing the upgrade path 8202 and forward.
 *
 * This script is run from drupal root after installing the testing profile of
 * Drupal 8.4.x on 8.x-2.x branch of workflow_participants. For more details
 * see https://www.drupal.org/project/workflow_participants/issues/2931805.
 */

use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\workflows\Entity\Workflow;
use Drupal\user\Entity\User;

\Drupal::service('module_installer')->install([
  'content_moderation',
  'dynamic_entity_reference',
  'workflows',
  'workflow_participants',
]);

// Create new content type.
$values = [
  'type' => 'basic_page',
  'name' => 'Basic page',
];
$type = NodeType::create($values);
$type->save();

// Create a workflow and add new content type.
$workflow = new Workflow(['id' => 'test1', 'type' => 'content_moderation'], 'workflow');
$workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'basic_page');
$workflow->save();

// Create two test nodes.
$node_values = [
  'type'      => 'basic_page',
  'uid'       => 1,
];
$nodes = [];
foreach (['Node one', 'Node two'] as $title) {
  $node_values['title'] = $title;
  $node = Node::create($node_values);
  $node->save();
  $nodes[] = $node;
}

// Create two test users.
$user_values = [];
$user_values['name'] = "user_one";
$user_values['mail'] = $user_values['name'] . '@example.com';
$user_values['pass'] = user_password();
$user_values['status'] = 1;
$user_values['roles'] = ['can be workflow participant'];

$account1 = User::create($user_values);
$account1->save();

$user_values['name'] = "user_two";
$user_values['mail'] = $user_values['name'] . '@example.com';
$account2 = User::create($user_values);
$account2->save();

// Create workflow_participants entities.
$storage = \Drupal::entityTypeManager()->getStorage('workflow_participants');
$participant_values = [
  'moderated_entity' => [
    'target_id' => '',
    'target_type' => 'node',
  ],
  'editors' => [],
  'reviewers' => [],
];

foreach (['editors', 'reviewers'] as $index => $participant_type) {
  $participant_values['moderated_entity']['target_id'] = $nodes[$index]->id();
  $participant_values[$participant_type] = ['target_id' => $account1->id()];
  $participant1 = $storage->create($participant_values);
  $storage->save($participant1);

  $participant_values[$participant_type] = ['target_id' => $account2->id()];
  $participant2 = $storage->create($participant_values);
  $storage->save($participant2);

  // Clear out values.
  $participant_values['editors'] = [];
  $participant_values['reviewers'] = [];
}
