<?php

/**
 * @file
 * Contains ExtendedFieldComparatorObserver.php.
 */

namespace Drupal\changed_fields_extended_field_comparator;

use Drupal\changed_fields\ObserverInterface;
use SplSubject;

/**
 * Class ExtendedFieldComparatorObserver.
 */
class ExtendedFieldComparatorObserver implements ObserverInterface {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      'article' => [
        'title',
        'body',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function update(SplSubject $nodeSubject) {
    $node = $nodeSubject->getNode();
    $changedFields = $nodeSubject->getChangedFields();

    // Do something with $node depends on $changedFields.
  }

}
