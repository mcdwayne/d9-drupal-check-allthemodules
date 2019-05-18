<?php

namespace Drupal\json_ld_schema_test_sources\Plugin\JsonLdSource;

use Drupal\json_ld_schema\Source\JsonLdSourceBase;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\Type;

/**
 * Organization source.
 *
 * @JsonLdSource(
 *   label = "Organization Source",
 *   id = "organization_source",
 * )
 */
class OrganizationSource extends JsonLdSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getData(): Type {
    return Schema::organization()
      ->url('http://www.example.com')
      ->logo('http://www.example.com/logo.jpg');
  }

}
