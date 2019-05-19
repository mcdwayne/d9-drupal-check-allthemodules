<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Sorter\NumericSorter.
 */

namespace Drupal\views_xml_backend\Sorter;

use Drupal\views\ResultRow;

/**
 * Provides sorting for numbers.
 */
class NumericSorter extends StringSorter {

  /**
   * {@inheritdoc}
   */
  public function __invoke(array &$result) {
    // Notice the order of the subtraction.
    switch ($this->direction) {
      case 'ASC':
        usort($result, function (ResultRow $a, ResultRow $b) {
          $compare = reset($a->{$this->field}) - reset($b->{$this->field});

          if ($compare === 0) {
            return $a->index < $b->index ? -1 : 1;
          }

          return $compare;
        });
        break;

      case 'DESC':
        usort($result, function (ResultRow $a, ResultRow $b) {
          $compare = reset($b->{$this->field}) - reset($a->{$this->field});

          if ($compare === 0) {
            return $a->index < $b->index ? -1 : 1;
          }

          return $compare;
        });
        break;
    }
  }

}
