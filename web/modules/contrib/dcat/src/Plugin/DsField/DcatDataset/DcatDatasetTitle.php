<?php

namespace Drupal\dcat\Plugin\DsField\DcatDataset;

use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the title of a DCAT dataset.
 *
 * @DsField(
 *   id = "dcat_dataset_title",
 *   title = @Translation("Title"),
 *   entity_type = "dcat_dataset",
 *   provider = "dcat"
 * )
 */
class DcatDatasetTitle extends Title {

  /**
   * {@inheritdoc}
   */
  public function entityRenderKey() {
    return 'name';
  }

}
