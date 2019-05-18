<?php

/**
 * @file
 * Contains BasicUsageObserver.php.
 */

namespace Drupal\changed_fields_basic_usage;

use Drupal\changed_fields\ObserverInterface;
use SplSubject;

/**
 * Class BasicUsageObserver.
 */
class BasicUsageObserver implements ObserverInterface {

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
