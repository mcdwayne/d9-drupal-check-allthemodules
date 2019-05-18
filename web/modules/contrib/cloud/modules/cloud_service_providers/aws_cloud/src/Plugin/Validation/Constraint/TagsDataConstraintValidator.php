<?php

namespace Drupal\aws_cloud\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates tags field.
 */
class TagsDataConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $key_map = [];
    foreach ($items as $item) {
      $key = $item->getTagKey();
      if (!isset($key_map[$key])) {
        $key_map[$key] = TRUE;
      }
      else {
        $this->context->addViolation(
          $constraint->keyExists,
          ['%value' => $key]);

        break;
      }

    }
  }

}
