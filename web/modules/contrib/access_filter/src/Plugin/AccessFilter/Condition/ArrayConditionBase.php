<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Drupal\Component\Utility\Html;

/**
 * Base class for condition plugins that checks arrays.
 */
abstract class ArrayConditionBase extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $regex = !empty($this->configuration['regex']) ? '<span class="regex">[Regex]</span>' : '';
    return Html::escape($this->configuration['key'] . ' = ' . $this->configuration['value']) . $regex;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfiguration(array $configuration) {
    $errors = [];

    if (!isset($configuration['key']) || !strlen($configuration['key'])) {
      $errors[] = $this->t("'@property' is required.", ['@property' => 'key']);
    }

    return $errors;
  }

  /**
   * Checks matching using specific array.
   *
   * @param array $array
   *   The array containing comparison.
   *
   * @return bool
   *   Boolean TRUE if condition is matched or FALSE otherwise.
   */
  protected function isMatchedWithArray(array $array) {
    $array_value = '';
    if (isset($array[$this->configuration['key']])) {
      $array_value = $array[$this->configuration['key']];
    }

    $comparison = '';
    if (isset($this->configuration['value'])) {
      $comparison = $this->configuration['value'];
    }

    if (empty($this->configuration['regex'])) {
      return ($array_value == $comparison);
    }
    else {
      return (bool) preg_match($array_value, $comparison);
    }
  }

}
