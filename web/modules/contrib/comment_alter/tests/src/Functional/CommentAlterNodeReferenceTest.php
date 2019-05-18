<?php

namespace Drupal\Tests\comment_alter\Functional;

use Drupal\Tests\comment_alter\Functional\CommentAlterTestBase;

/**
 * Tests the comment alter module functions for node reference fields.
 *
 * @group comment_alter
 */
class CommentAlterNodeReferenceTest extends CommentAlterTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'entity_reference'];

  /**
   * A node object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $targetNode;

  /**
   * Adds a node reference field to the parent enity.
   *
   * @param int $cardinality
   *   Cardinality of the field.
   *
   * @return string
   *   The name of the field which was created.
   */
  protected function addNodeReferenceField($cardinality) {
    $referenced = $this->createContentType();
    $referencedType = $referenced->id();
    $this->targetNode = $this->createNode(['type' => $referenced->id()]);

    return $this->addField('entity_reference', 'entity_reference_autocomplete', [
      'settings' => [
        'target_type' => 'node',
      ],
      'cardinality' => $cardinality,
    ],
    [
      'handler' => 'default',
      'handler_settings' => [
        // Reference a single vocabulary.
        'target_bundles' => [
          $referenced->id(),
        ],
        // Enable auto-create.
        'auto_create' => TRUE,
      ],
    ]);
  }

  /**
   * Tests for single valued node reference field comment altering.
   */
  public function testEntityReferenceFieldSingle() {
    $field_name = $this->addNodeReferenceField(1);
    $this->createEntityObject();

    $new_node = $this->randomMachineName();
    $this->postComment(["comment_alter_fields[{$field_name}][0][target_id]" => $new_node]);

    $this->assertCommentDiff([
      $field_name => [
        [NULL, $new_node]
      ],
    ]);
    $this->assertCommentSettings($field_name);
    $this->assertRevisionDelete();
    $this->assertAlterableField($field_name);

  }

  /**
   * Tests for multi-valued node reference field comment altering.
   */
  public function testEntityReferenceFieldMultiple() {
    $field_name = $this->addNodeReferenceField(-1);
    $this->createEntityObject([$field_name =>['target_id' => $this->targetNode->id()]]);

    $new_node = $this->randomMachineName();
    $this->postComment(["comment_alter_fields[{$field_name}][1][target_id]" => $new_node]);

    $this->assertCommentDiff([
      $field_name => [
        [$this->targetNode->getTitle(), $this->targetNode->getTitle()],
        [NULL, $new_node]
      ],
    ]);
    $this->assertCommentSettings($field_name);
    $this->assertRevisionDelete();
    $this->assertAlterableField($field_name);

  }

}
