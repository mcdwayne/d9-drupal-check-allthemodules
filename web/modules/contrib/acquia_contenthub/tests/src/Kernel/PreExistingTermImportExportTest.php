<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

/**
 * Class PreExistingTermImportExportTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class PreExistingTermImportExportTest extends ImportExportTestBase {

  protected $fixtures = [
    [
      'cdf' => 'node/node_term_page.json',
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
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('acquia_contenthub_subscriber', 'acquia_contenthub_subscriber_import_tracking');

    $values0 = [
      'langcode' => 'en',
      'status' => TRUE,
      'name' => 'Category',
      'vid' => 'category',
      'description' => 'Category',
      'hierarchy' => 1,
      'weight' => 0,
    ];
    $vocab = $this->entityManager->getStorage('taxonomy_vocabulary')->create($values0);
    $vocab->save();

    $values1 = [
      'name' => 'Category 1',
      'vid' => $vocab->id(),
    ];
    $term1 = $this->entityManager->getStorage('taxonomy_term')->create($values1);
    $term1->save();

    $values2 = [
      'name' => 'Category 1 - 1',
      'vid' => $vocab->id(),
      'parent' => $term1->id(),
    ];
    $term2 = $this->entityManager->getStorage('taxonomy_term')->create($values2);
    $term2->save();

    $values3 = [
      'name' => 'Category 1 - 1 - 1',
      'vid' => $vocab->id(),
      'parent' => $term2->id(),
    ];
    $term3 = $this->entityManager->getStorage('taxonomy_term')->create($values3);
    $term3->save();
    $this->terms = [
      $term1,
      $term2,
      $term3,
    ];
  }

  /**
   * Performs taxonomy terms import and runs assertions.
   */
  public function testTermImportExport() {
    // We're not going to use this expectation.
    $this->importFixture(0);
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
    $repository = $this->container->get('entity.repository');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $node */
    $node = $repository->loadEntityByUuid('node', '1264093e-bdad-41a7-a059-1904a5e6d8d6');
    $values = $this->handleFieldValues($node->get('field_custom_category'));
    $this->assertEquals($values[0]['target_id'], $this->terms[2]->uuid());
    // Make sure there is only one single term with the same name in the system.
    $term_name = $this->terms[2]->label();
    $terms = $this->entityManager->getStorage('taxonomy_term')->loadByProperties([
      'name' => $term_name,
    ]);
    $this->assertEquals(1, count($terms));
  }

}
