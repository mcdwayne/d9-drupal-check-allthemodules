<?php

namespace Drupal\ad_entity_test\Plugin\ad_entity\AdView;

use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdViewBase;

/**
 * Test view plugin.
 *
 * @AdView(
 *   id = "test_view",
 *   label = "Test View",
 *   library = "ad_entity_test/test_view",
 *   requiresDomready = false,
 *   container = "html",
 *   allowedTypes = {
 *     "test_type"
 *   }
 * )
 */
class TestView extends AdViewBase {

  /**
   * {@inheritdoc}
   */
  public function build(AdEntityInterface $entity) {
    return ['#markup' => '<div class="ad-entity-test-view"></div>'];
  }

}
