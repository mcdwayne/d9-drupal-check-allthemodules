<?php

declare(strict_types = 1);

namespace Drupal\field_autovalue_test\Plugin\FieldAutovalue;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_autovalue\Plugin\FieldAutovalueBase;

/**
 * Provides a test Field Autovalue plugin.
 *
 * @FieldAutovalue(
 *   id = "field_autovalue_test",
 *   label = @Translation("Field Autovalue test"),
 *   field_types = {
 *     "text"
 *   }
 * )
 */
class TestAutovalue extends FieldAutovalueBase {

  /**
   * {@inheritdoc}
   */
  public function setAutovalue(FieldItemListInterface $field): void {
    // There are two boolean condition fields that rule the value generation.
    // If condition 1 is checked, we set the value: "Condition 1 met"
    // regardless.
    // If condition 2 is checked for the first time, we append
    // "and Condition 2 met.". This will only stay there until the next edit
    // but we can prove that we can inspect changing values.
    $entity = $this->getEntity($field);
    $original = $this->getEntity($field, TRUE);
    if ((bool) $entity->get('field_condition_1') === TRUE) {
      $field->setValue('Condition 1 met');
    }

    if ($original && (bool) $original->get('field_condition_2')->value === FALSE && (bool) $entity->get('field_condition_2')->value === TRUE) {
      $value = $field->first()->value;
      $value .= '. Condition 2 met.';
      $field->setValue($value);
    }
  }

}
