<?php

namespace Drupal\media_entity_d500px\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media_entity_d500px\Plugin\media\Source\D500px;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the D500pxEmbedCode constraint.
 */
class D500pxEmbedCodeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $data = [];
    if (is_string($value)) {
      $data[] = $value;
    }
    elseif ($value instanceof FieldItemInterface) {
      $class = get_class($value);
      $property = $class::mainPropertyName();
      if ($property) {
        $data[] = $value->{$property};
      }
    }
    elseif ($value instanceof FieldItemListInterface) {
      foreach ($value as $item_value) {
        $class = get_class($item_value);

        if (method_exists($class, 'mainPropertyName')) {
          $property = $class::mainPropertyName();
          if ($property) {
            $data[] = $item_value->{$property};
          }
        }
      }
    }

    if ($data) {
      foreach ($data as $item_data) {
        $value = str_replace(["\r", "\n"],'', $item_data);

        if (!isset($value)) {
          continue;
        }

        $matches = [];
        foreach (D500px::$validationRegexp as $pattern => $key) {
          if (preg_match($pattern, $item_data, $item_matches)) {
            $matches[] = $item_matches;
          }
        }

        if (empty($matches)) {
          $this->context->addViolation($constraint->message);
        }
      }
    }

  }

}
