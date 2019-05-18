<?php

namespace Drupal\Tests\gathercontent\Kernel;

use Drupal\gathercontent_test\MockData;
use Drupal\gathercontent_test\MockDrupalGatherContentClient;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Base class for import kernel tests.
 */
class GcMigrateTestBase extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node', 'text', 'field', 'user', 'image', 'file', 'taxonomy', 'language',
    'content_translation', 'paragraphs', 'entity_reference_revisions', 'system',
    'metatag', 'menu_ui', 'menu_link_content', 'link', 'gathercontent', 'gathercontent_test',
    'migrate', 'migrate_plus', 'migrate_tools',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');
    $this->installConfig(['gathercontent_test']);
    $this->installSchema('system', ['key_value_expire']);
    MockData::$drupalRoot = $this->getDrupalRoot();
    /** @var \Drupal\taxonomy\Entity\Term[] $terms */
    $terms = MockData::createTaxonomyTerms();
    foreach ($terms as $term) {
      $term->save();
    }
  }

  /**
   * Get singleton Mock client.
   */
  public static function getClient() {
    static $client = NULL;
    if ($client === NULL) {
      $client = new MockDrupalGatherContentClient(
        \Drupal::service('http_client')
      );
    }
    return $client;
  }

}
