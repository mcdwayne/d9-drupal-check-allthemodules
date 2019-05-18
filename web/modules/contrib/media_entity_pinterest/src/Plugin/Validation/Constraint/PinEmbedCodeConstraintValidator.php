<?php

namespace Drupal\media_entity_pinterest\Plugin\Validation\Constraint;

use Drupal\media_entity_pinterest\Plugin\media\Source\Pinterest;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PinEmbedCode constraint.
 */
class PinEmbedCodeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $value = $this->getEmbedCode($value);
    if (!isset($value)) {
      return;
    }

    $matches = [];
    foreach (Pinterest::$validationRegexp as $pattern => $key) {
      // URLs will sometimes have urlencoding, so we decode for safety.
      if (preg_match($pattern, urldecode($value), $item_matches)) {
        $matches[] = $item_matches;
      }
    }

    if (empty($matches)) {
      $this->context->addViolation($constraint->message);
    }
  }

  /**
   * Extracts the raw embed code from input which may or may not be wrapped.
   *
   * @param mixed $value
   *   The input value. Can be a normal string or a value wrapped by the
   *   Typed Data API.
   *
   * @return string|null
   *   The raw embed code.
   */
  protected function getEmbedCode($value) {
    if (is_string($value)) {
      return $value;
    }
    elseif ($value instanceof FieldItemInterface) {
      $class = get_class($value);
      $property = $class::mainPropertyName();
      if ($property) {
        return $value->$property;
      }
    }
  }

}
