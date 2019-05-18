<?php

namespace Drupal\media_entity_slideshare\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\media_entity_slideshare\Plugin\media\Source\SlideShare;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SlideShareEmbedCode constraint.
 */
class SlideShareEmbedCodeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value instanceof \Traversable) {
      foreach ($value as $item) {
        $this->doValidate($item, $constraint);
      }
    }
    else {
      $this->doValidate($value, $constraint);
    }
  }

  /**
   * Performs the actual validation logic.
   *
   * @param mixed $value
   *   The value to validate; can be a field item object, in which case the
   *   main property will be validated.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The validation constraint.
   */
  protected function doValidate($value, Constraint $constraint) {
    if ($value instanceof FieldItemInterface) {
      $class = get_class($value);
      $property = $class::mainPropertyName();
      if ($property) {
        $value = $value->$property;
      }
    }
    if (!isset($value)) {
      return;
    }

    $matches = [];
    foreach (SlideShare::$validationRegexp as $pattern => $key) {
      if (preg_match($pattern, $value, $item_matches)) {
        $matches[] = $item_matches;
      }
    }

    if (empty($matches)) {
      $this->context->addViolation($constraint->message);
    }
  }

}
