<?php

namespace Drupal\Tests\commerce_migrate_csv_example\Kernel\Migrate;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Upgrade taxonomy terms.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate_csv_example
 */
class TaxonomyTermTest extends CsvTestBase {

  use CommerceMigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_migrate_csv_example',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $fixtures = [__DIR__ . '/../../../fixtures/csv/example-products.csv'];

  /**
   * The cached taxonomy tree items, keyed by vid and tid.
   *
   * @var array
   */
  protected $treeData = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->createVocabularies(['Category', 'Season']);
    $this->executeMigration('csv_example_taxonomy_term');
  }

  /**
   * Validate a migrated term contains the expected values.
   *
   * @param string $id
   *   Entity ID to load and check.
   * @param string $expected_label
   *   The label the migrated entity should have.
   * @param string $expected_vid
   *   The parent vocabulary the migrated entity should have.
   * @param string $expected_description
   *   The description the migrated entity should have.
   * @param string $expected_format
   *   The format the migrated entity should have.
   * @param int $expected_weight
   *   The weight the migrated entity should have.
   * @param array $expected_parents
   *   The parent terms the migrated entity should have.
   * @param int $expected_field_integer_value
   *   The value the migrated entity field should have.
   * @param int $expected_term_reference_tid
   *   The term reference id the migrated entity field should have.
   * @param bool $expected_container_flag
   *   The term should be a container entity.
   */
  protected function assertEntity($id, $expected_label, $expected_vid, $expected_description = '', $expected_format = NULL, $expected_weight = 0, array $expected_parents = [], $expected_field_integer_value = NULL, $expected_term_reference_tid = NULL, $expected_container_flag = FALSE) {
    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity = Term::load($id);
    $this->assertInstanceOf(TermInterface::class, $entity);
    $this->assertEquals($expected_label, $entity->label());
    $this->assertEquals($expected_vid, $entity->bundle());
    $this->assertEquals($expected_description, $entity->getDescription());
    $this->assertEquals($expected_format, $entity->getFormat());
    $this->assertEquals($expected_weight, $entity->getWeight());
    $this->assertEquals($expected_parents, $this->getParentIDs($id));
    $this->assertHierarchy($expected_vid, $id, $expected_parents);
    if (!is_null($expected_field_integer_value)) {
      $this->assertTrue($entity->hasField('field_integer'));
      $this->assertEquals($expected_field_integer_value, $entity->field_integer->value);
    }
    if (!is_null($expected_term_reference_tid)) {
      $this->assertTrue($entity->hasField('field_integer'));
      $this->assertEquals($expected_term_reference_tid, $entity->field_term_reference->target_id);
    }
    if ($entity->hasField('forum_container')) {
      $this->assertEquals($expected_container_flag, $entity->forum_container->value);
    }
  }

  /**
   * Tests the taxonomy term migration.
   */
  public function testTaxonomyTerms() {
    $this->assertEntity(1, 'Wetsuit', 'category', '', NULL, 0);
    $this->assertEntity(2, 'Summer', 'season', '', NULL, 0);
    $this->assertEntity(3, 'Winter', 'season', '', NULL, 0);
    $this->assertEntity(4, 'Misc', 'category', '', NULL, 0);
    $this->assertEntity(5, 'Camera', 'category', '', NULL, 0);
    $this->assertEntity(6, 'All', 'season', '', NULL, 0);
  }

  /**
   * Retrieves the parent term IDs for a given term.
   *
   * @param int $tid
   *   ID of the term to check.
   *
   * @return array
   *   List of parent term IDs.
   */
  protected function getParentIds($tid) {
    return array_keys(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($tid));
  }

  /**
   * Assert that a term is present in the tree storage, with the right parents.
   *
   * @param string $vid
   *   Vocabulary ID.
   * @param int $tid
   *   ID of the term to check.
   * @param array $parent_ids
   *   The expected parent term IDs.
   */
  protected function assertHierarchy($vid, $tid, array $parent_ids) {
    if (!isset($this->treeData[$vid])) {
      $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
      $this->treeData[$vid] = [];
      foreach ($tree as $item) {
        $this->treeData[$vid][$item->tid] = $item;
      }
    }

    $this->assertArrayHasKey($tid, $this->treeData[$vid], "Term $tid exists in taxonomy tree");
    $term = $this->treeData[$vid][$tid];
    $this->assertEquals($parent_ids, array_filter($term->parents), "Term $tid has correct parents in taxonomy tree");
  }

}
