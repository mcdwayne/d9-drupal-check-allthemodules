<?php

namespace Drupal\question_field\Plugin\Field\FieldFormatter;

use Drupal\question_field\Plugin\Field\FieldType\QuestionItem;

/**
 * Class QuestionFormatterItemData.
 */
class QuestionFormatterItemData {

  /**
   * The item.
   *
   * @var \Drupal\question_field\Plugin\Field\FieldType\QuestionItem
   */
  protected $item;

  /**
   * The item value.
   *
   * @var array|string
   */
  protected $value;

  /**
   * Array of original id's that this item is a followup to.
   *
   * @var array
   */
  protected $followupFromOriginal;

  /**
   * QuestionFormatterItemData constructor.
   *
   * @param \Drupal\question_field\Plugin\Field\FieldType\QuestionItem $item
   *   The question item.
   * @param string $value
   *   Serialized value.
   */
  public function __construct(QuestionItem $item, $value) {
    $this->value = $value;
    $this->item = $item;
  }

  /**
   * Return the question item.
   *
   * @return \Drupal\question_field\Plugin\Field\FieldType\QuestionItem
   *   The question item.
   */
  public function getItem() {
    return $this->item;
  }

  /**
   * Return the answer value.
   *
   * @return array|string
   *   The answer value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Mark this question as a followup to the original question.
   *
   * @param int $original
   *   The original id.
   */
  public function addAsFollowupFromOriginal($original) {
    $this->followupFromOriginal[] = $original;
  }

  /**
   * Return array of followup ids.
   *
   * @return array
   *   The followup ids.
   */
  public function getFollowupsFromOriginal() {
    return $this->followupFromOriginal;
  }

}
