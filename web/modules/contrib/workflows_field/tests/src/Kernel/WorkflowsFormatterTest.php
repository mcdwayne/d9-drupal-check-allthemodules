<?php

namespace Drupal\Tests\workflows_field\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\node\Entity\Node;

/**
 * Tests the Workflows Field formatters.
 *
 * @group workflows_field
 */
class WorkflowsFormatterTest extends WorkflowsTestBase {

  /**
   * Test the states list formatter.
   */
  public function testStatesListFormatter() {
    $node = Node::create([
      'title' => 'Foo',
      'type' => 'project',
      'field_status' => 'in_discussion',
    ]);
    $node->save();

    $output = $node->field_status->view(['type' => 'workflows_field_state_list']);
    $this->assertEquals([
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => 'Implementing',
        '#wrapper_attributes' => ['class' => ['implementing', 'before-current']],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => 'Approved',
        '#wrapper_attributes' => ['class' => ['approved', 'before-current']],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => 'Rejected',
        '#wrapper_attributes' => ['class' => ['rejected', 'before-current']],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => 'Planning',
        '#wrapper_attributes' => ['class' => ['planning', 'before-current']],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => 'In Discussion',
        '#wrapper_attributes' => ['class' => ['in_discussion', 'is-current']],
      ],
    ], $output[0]['#items']);

    // Try with settings excluded.
    $output = $node->field_status->view([
      'type' => 'workflows_field_state_list',
      'settings' => [
        'excluded_states' => [
          'in_discussion' => 'in_discussion',
          'planning' => 'planning',
          'rejected' => 'rejected',
          'approved' => 'approved',
        ],
      ],
    ]);
    $this->assertEquals([
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => 'Implementing',
        '#wrapper_attributes' => ['class' => ['implementing', 'before-current']],
      ],
    ], $output[0]['#items']);
  }

  /**
   * Test the default formatter.
   */
  public function testDefaultFormatter() {
    $node = Node::create([
      'title' => 'Foo',
      'type' => 'project',
      'field_status' => 'in_discussion',
    ]);
    $node->save();

    $this->assertEquals([
      '#markup' => 'In Discussion',
      '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
    ], $node->field_status->view()[0]);
  }

}
