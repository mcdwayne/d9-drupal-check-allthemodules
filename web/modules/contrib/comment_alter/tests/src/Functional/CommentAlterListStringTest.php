<?php

namespace Drupal\Tests\comment_alter\Functional;

use Drupal\Tests\comment_alter\Functional\CommentAlterTestBase;

/**
 * Tests the comment alter module functions for List (string) fields.
 *
 * @group comment_alter
 */
class CommentAlterListStringTest extends CommentAlterTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['options'];

  /**
   * Adds an Option Field to the parent enity.
   *
   * @param string $widget_type
   *   The widget type (Eg. options_select).
   * @param int $cardinality
   *   Cardinality of the field.
   *
   * @return string
   *   The name of the field which was created.
   */
  protected function addOptionField($widget_type, $cardinality) {
    return $this->addField('list_string', $widget_type, [
      'settings' => [
        'allowed_values' => [1 => 'One', 2 => 'Two']
      ],
      'cardinality' => $cardinality,
    ]);
  }

  /**
   * Tests for single valued List (string) fields comment altering.
   */
  public function testOptionsSelectSingle() {
    $field_name = $this->addOptionField('options_select', 1);

    $this->createEntityObject([
      $field_name => [
        'value' => 1
      ]
    ]);

    $this->assertAlterableField($field_name);
    $this->postComment(["comment_alter_fields[{$field_name}]" => 2]);

    $this->assertCommentDiff([
      $field_name => [
        [1, 2]
      ],
    ]);
    $this->assertCommentSettings($field_name);
    $this->assertRevisionDelete();
  }

  /**
   * Tests for multi-valued List (string) fields comment altering.
   */
  public function testOptionsSelectMultiple() {
    $field_name = $this->addOptionField('options_select', -1);

    $this->createEntityObject([
      $field_name => [
        0 => ['value' => 1]
      ]
    ]);

    $this->assertAlterableField($field_name);
    // The alterable fields on comment form have a wrapper of alterable_fields
    // over them because of the #parent property specified in the
    // comment_form_alter.
    $this->postComment([
      "comment_alter_fields[{$field_name}][]" => [1, 2]
    ]);

    $this->assertCommentDiff([
      $field_name => [
        [1, 1],
        [NULL, 2],
      ],
    ]);
    $this->assertCommentSettings($field_name);
    $this->assertRevisionDelete();
  }

  /**
   * Tests for single valued List (string) fields comment altering.
   */
  public function testOptionsButtonSingle() {
    $field_name = $this->addOptionField('options_buttons', 1);

    $this->createEntityObject([
      $field_name => [
        'value' => 1
      ]
    ]);

    $this->postComment([
      "comment_alter_fields[{$field_name}]" => 2
    ]);

    $this->assertCommentDiff([
      $field_name => [
        [1, 2]
      ],
    ]);
    $this->assertRevisionDelete();
  }

  /**
   * Tests for multi-valued List (string) fields comment altering.
   */
  public function testOptionsButtonMultiple() {
    $field_name = $this->addOptionField('options_buttons', -1);
    $this->createEntityObject([
      $field_name => [
        0 => ['value' => 1]
      ]
    ]);

    $this->postComment([
      "comment_alter_fields[{$field_name}][2]" => TRUE
    ]);

    $this->assertCommentDiff([
      $field_name => [
        [1, 1],
        [NULL, 2],
      ],
    ]);
    $this->assertRevisionDelete();
  }

}
