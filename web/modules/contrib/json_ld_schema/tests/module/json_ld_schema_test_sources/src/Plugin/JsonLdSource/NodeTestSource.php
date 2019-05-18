<?php

namespace Drupal\json_ld_schema_test_sources\Plugin\JsonLdSource;

use Drupal\json_ld_schema_test_sources\EntityJsonLdSourceBase;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\Type;

/**
 * Node test source.
 *
 * @JsonLdSource(
 *   label = "Node Test Source",
 *   id = "node_test_source",
 * )
 */
class NodeTestSource extends EntityJsonLdSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getData(): Type {
    return Schema::aboutPage()
      ->name($this->getEntity()->label())
      ->commentCount(\Drupal::state()->get('json_ld_schema_test_sources_node_comment_count', 0));
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return $this->currentEntityIsOfType('node');
  }

}
