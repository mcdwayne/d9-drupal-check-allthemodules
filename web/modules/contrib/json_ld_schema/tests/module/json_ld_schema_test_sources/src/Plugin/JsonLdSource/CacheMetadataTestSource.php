<?php

namespace Drupal\json_ld_schema_test_sources\Plugin\JsonLdSource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\json_ld_schema\Source\JsonLdSourceBase;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\Type;

/**
 * Test the cacheability metadata.
 *
 * @JsonLdSource(
 *   label = "Cacheability Metadata Test Source",
 *   id = "cache_metadata_test_source",
 * )
 */
class CacheMetadataTestSource extends JsonLdSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getData(): Type {
    return Schema::thing()->name('Foo');
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    $metadata = new CacheableMetadata();
    $metadata->addCacheContexts(['url']);
    return $metadata;
  }

}
