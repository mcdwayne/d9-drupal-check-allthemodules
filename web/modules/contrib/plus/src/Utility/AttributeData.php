<?php

namespace Drupal\plus\Utility;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * A class that defines a class based Attribute.
 */
class AttributeData extends AttributeArray {

  /**
   * {@inheritdoc}
   */
  public function __construct(array &$value = []) {
    parent::__construct('data', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $output = '';
    /* @var \Drupal\plus\Utility\AttributeDataValue $attribute */
    foreach ($this->value() as $name => $attribute) {
      $output .= $attribute->render();
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitize(...$values) {
    $converter = new CamelCaseToSnakeCaseNameConverter();
    return ArrayObject::create()->merge(...$values)->flatten()
      // Convert each data attribute into a AttributeDataValue object.
      ->map(function ($value, $key) use ($converter) {
        $key = 'data-' . str_replace('_', '-', $converter->normalize($key));
        return new AttributeDataValue($key, $value);
      });
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return $this->__toString();
  }

}
