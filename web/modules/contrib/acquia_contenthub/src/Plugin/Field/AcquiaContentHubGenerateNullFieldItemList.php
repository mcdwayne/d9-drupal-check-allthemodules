<?php

namespace Drupal\acquia_contenthub\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

class AcquiaContentHubGenerateNullFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function generateSampleItems($count = 1) {
    $values = array_fill(0, $count, NULL);
    $this->setValue($values);
  }

}
