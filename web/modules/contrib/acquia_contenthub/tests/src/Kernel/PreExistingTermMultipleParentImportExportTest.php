<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

/**
 * Class PreExistingTermMultipleParentImportExportTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class PreExistingTermMultipleParentImportExportTest extends ImportExportTestBase {

  protected $fixtures = [
    [
      'cdf' => 'taxonomy_term/taxonomy_term-multiple_parent.json',
      'expectations' => 'expectations/node/node_term_page.php',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'taxonomy',
    'user',
    'node',
    'field',
    'depcalc',
    'acquia_contenthub',
    'acquia_contenthub_subscriber',
  ];

  /**
   * The values for terms that will be pre-created.
   *
   * @var array
   */
  protected $termValues;

  /**
   * The pre-created terms we are matching.
   *
   * @var \Drupal\taxonomy\Entity\Term[]
   */
  protected $terms;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('acquia_contenthub_subscriber', 'acquia_contenthub_subscriber_import_tracking');

    $values00 = [
      'langcode' => 'en',
      'status' => TRUE,
      'name' => 'Category',
      'vid' => 'category',
      'description' => 'Category',
      'hierarchy' => 1,
      'weight' => 0,
    ];
    $vocab = $this->entityManager->getStorage('taxonomy_vocabulary')->create($values00);
    $vocab->save();

    $values0 = [
      'name' => 'Category 1',
      'vid' => $vocab->id(),
      'parent' => [],
    ];
    $values1 = [
      'name' => 'Category 1 - 1',
      'vid' => $vocab->id(),
      'parent' => [0],
    ];
    $values2 = [
      'name' => 'Category 2',
      'vid' => $vocab->id(),
      'parent' => [],
    ];
    $values3 = [
      'name' => 'Category 2 - 1',
      'vid' => $vocab->id(),
      'parent' => [2],
    ];
    $values4 = [
      'name' => 'Category Mixed',
      'vid' => $vocab->id(),
      'parent' => [
        1,
        3,
      ],
    ];

    $this->termValues = [
      0 => $values0,
      1 => $values1,
      2 => $values2,
      3 => $values3,
      4 => $values4,
    ];
  }

  /**
   * Creates a particular set of terms.
   *
   * @param array $keys
   *   An array of keys.
   */
  public function createTerms(array $keys) {
    foreach ($keys as $key) {
      $values = $this->termValues[$key];
      $parents = $values['parent'];
      if (!empty($parents)) {
        $term_parents = [];
        foreach ($parents as $parent) {
          // Load parent term.
          $parent_name = $this->termValues[$parent]['name'];
          $term_parent = $this->entityManager->getStorage('taxonomy_term')->loadByProperties([
            'name' => $parent_name,
          ]);
          $term_parent = reset($term_parent);
          if (!empty($term_parent)) {
            $term_parents[] = $term_parent->id();
          }
        }
        $values['parent'] = $term_parents;
      }
      $term = $this->entityManager->getStorage('taxonomy_term')->create($values);
      $term->save();
      $this->terms[$key] = $term;
    }
  }

  /**
   * Verifies data for a taxonomy term given its key in the termValues array.
   *
   * @param string $key
   *   The Taxonomy term key in the $this->termValues array.
   * @param array $imported_uuids
   *   Provides a list of imported UUIDs (coming from Content Hub).
   * @param bool $imported
   *   TRUE if this is an imported term, FALSE if local term. Defaults to FALSE.
   */
  public function verifyTaxonomyTerm($key, array $imported_uuids, $imported = FALSE) {
    $name = $this->termValues[$key]['name'];
    $terms = $this->entityManager->getStorage('taxonomy_term')->loadByProperties([
      'name' => $name,
    ]);
    // Assert there is only a single term with this name.
    $this->assertEquals(1, count($terms));
    $term = reset($terms);

    // Verify the uuid of this term is either imported or local.
    if ($imported) {
      $this->assertTrue(in_array($term->uuid(), $imported_uuids), "Failed to assert that UUID for term '{$term->label()}' ({$term->uuid()}) was found in list of imported UUIDs.");
    }
    else {
      $this->assertFalse(in_array($term->uuid(), $imported_uuids), "Failed to assert that UUID for term '{$term->label()}' ({$term->uuid()}) was not found in list of imported UUIDs.");
    }

    // Verify parents.
    $expected_parents = [];
    $parent_keys = $this->termValues[$key]['parent'];
    foreach ($parent_keys as $parent_key) {
      $expected_parents[] = $this->termValues[$parent_key]['name'];
    }
    $parents = $this->entityManager->getStorage('taxonomy_term')->loadParents($term->id());
    $actual_parents = [];
    foreach ($parents as $parent) {
      $actual_parents[] = $parent->label();
    }
    $this->assertEquals($expected_parents, $actual_parents);
  }

  /**
   * Performs tests with taxonomy term with multiple parents.
   *
   * This test uses 3 local terms and 2 imported terms. One parent is local and
   * the other one is imported.
   */
  public function testTermImportExport1() {
    // Create local terms: 0, 1, 4. Others should be imported.
    $terms = [0, 1, 4];
    $this->createTerms($terms);
    $this->assertEquals(3, count($this->terms), 'Created 3 local taxonomy terms.');
    // We're not going to use this expectation.
    $this->importFixture(0);
    $document = $this->createCdfDocumentFromFixture(0);
    $imported_uuids = array_keys($document->getEntities());

    // Verify every taxonomy term.
    $this->verifyTaxonomyTerm(0, $imported_uuids, FALSE);
    $this->verifyTaxonomyTerm(1, $imported_uuids, FALSE);
    $this->verifyTaxonomyTerm(2, $imported_uuids, TRUE);
    $this->verifyTaxonomyTerm(3, $imported_uuids, TRUE);
    $this->verifyTaxonomyTerm(4, $imported_uuids, FALSE);
  }

  /**
   * Performs tests with taxonomy term with multiple parents.
   *
   * This test uses 5 local terms and none of them imported. All the parents
   * are local entities.
   */
  public function testTermImportExport2() {
    // All local terms exist. None are imported.
    $terms = [0, 1, 2, 3, 4];
    $this->createTerms($terms);
    $this->assertEquals(5, count($this->terms), 'Created 5 local taxonomy terms.');
    // We're not going to use this expectation.
    $this->importFixture(0);
    $document = $this->createCdfDocumentFromFixture(0);
    $imported_uuids = array_keys($document->getEntities());

    // Verify every taxonomy term.
    $this->verifyTaxonomyTerm(0, $imported_uuids, FALSE);
    $this->verifyTaxonomyTerm(1, $imported_uuids, FALSE);
    $this->verifyTaxonomyTerm(2, $imported_uuids, FALSE);
    $this->verifyTaxonomyTerm(3, $imported_uuids, FALSE);
    $this->verifyTaxonomyTerm(4, $imported_uuids, FALSE);
  }

}
