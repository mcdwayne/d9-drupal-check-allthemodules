<?php

namespace Drupal\taxonomy_moderator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInteger constraint.
 */
class TaxonomyModeratorFieldConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $cur_item_val = $items->getProperties()['value']->getParent()->value;
    $cur_item_status = $items->getProperties()['value']->getParent()->status;
    $tempArray = [];
    foreach ($items->getParent()->getValue() as $value) {
      $tempArray[] = $value['value'];
    }
    $valCnt = array_count_values($tempArray);
    $properties = [
      'name' => $cur_item_val,
      'vid' => 'keywords_tags',
    ];
    if ($cur_item_status == 0) {
      $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties($properties);
      $term = reset($terms);
      $term_id = !empty($term) ? $term->id() : 0;
      if ($term_id != 0) {
        $this->context->addViolation($constraint->termExist, ['%value' => $cur_item_val]);
      }
      if ($valCnt[$cur_item_val] > 1) {
        $this->context->addViolation($constraint->termDuplicate, ['%value' => $cur_item_val]);
      }
    }
  }

}
