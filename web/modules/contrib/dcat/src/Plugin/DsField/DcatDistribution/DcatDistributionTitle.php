<?php

namespace Drupal\dcat\Plugin\DsField\DcatDistribution;

use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the title of a DCAT distribution.
 *
 * @DsField(
 *   id = "dcat_distribution_title",
 *   title = @Translation("Title"),
 *   entity_type = "dcat_distribution",
 *   provider = "dcat"
 * )
 */
class DcatDistributionTitle extends Title {

  /**
   * {@inheritdoc}
   */
  public function entityRenderKey() {
    return 'name';
  }

}
