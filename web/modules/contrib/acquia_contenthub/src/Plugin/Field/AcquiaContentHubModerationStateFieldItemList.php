<?php

namespace Drupal\acquia_contenthub\Plugin\Field;

use Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList;

/**
 * Override core's moderation state field item for sane sample data.
 *
 * @todo remove once https://www.drupal.org/project/drupal/issues/3048962 lands.
 */
class AcquiaContentHubModerationStateFieldItemList extends ModerationStateFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function generateSampleItems($count = 1) {
    $values = array_fill(0, $count, $this->getModerationStateId());
    $this->setValue($values);
  }

}
