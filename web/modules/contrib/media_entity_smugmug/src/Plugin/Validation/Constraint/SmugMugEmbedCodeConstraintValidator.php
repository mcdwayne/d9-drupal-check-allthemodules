<?php

namespace Drupal\media_entity_smugmug\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\media_entity_smugmug\Plugin\media\Source\SmugMug;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SmugMugEmbedCode constraint.
 */
class SmugMugEmbedCodeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (is_string($value)) {
      $data = $value;
    }

    if ($value instanceof FieldItemInterface) {
      $class = get_class($value);
      $property = $class::mainPropertyName();

      if ($property) {
        $data = $value->$property;
      }
    }

    if (empty($data)) {
      return;
    }

    $post_url = SmugMug::parseSmugMugEmbedField($value);

    if ($post_url === FALSE) {
      $this->context->addViolation($constraint->message);
    }
  }

}
