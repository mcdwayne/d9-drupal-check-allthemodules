<?php

namespace Drupal\json_ld_schema_test_sources\Plugin\JsonLdSource;

use Drupal\json_ld_schema\Source\JsonLdSourceBase;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\Type;

/**
 * Always hidden source.
 *
 * @JsonLdSource(
 *   label = "Always Hidden Source",
 *   id = "always_hidden",
 * )
 */
class AlwaysHiddenSource extends JsonLdSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getData(): Type {
    return Schema::thing()->name('Baz');
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return FALSE;
  }

}
