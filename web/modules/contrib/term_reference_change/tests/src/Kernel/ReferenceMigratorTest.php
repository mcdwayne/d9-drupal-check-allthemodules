<?php

namespace Drupal\Tests\term_reference_change\Kernel;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\term_reference_change\ReferenceMigrator;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Tests that references are migrated.
 *
 * @group term_reference_change
 */
class ReferenceMigratorTest extends KernelTestBase {

  use TaxonomyTestTrait;
  use NodeCreationTrait;
  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'field',
    'node',
    'taxonomy',
    'term_reference_change',
    'text',
    'user',
    'system',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * A vocabulary used for testing.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  private $vocabulary;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorage
   */
  private $termStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['filter']);
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->setUpContentType('page', 'field_terms');

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->vocabulary = $this->createVocabulary();
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * Tests term references in nodes are migrated.
   *
   * @test
   */
  public function migratesNodes() {
    $sourceTerm = $this->createTerm($this->vocabulary);
    $targetTerm = $this->createTerm($this->vocabulary);
    $node = $this->createNode(['field_terms' => ['target_id' => $sourceTerm->id()]]);

    $sut = new ReferenceMigrator($this->entityTypeManager, \Drupal::service('term_reference_change.reference_finder'));
    $sut->migrateReference($sourceTerm, $targetTerm);

    $this->assertNodeReferencesTerm($node, $targetTerm);
  }

  /**
   * Tests term references in nodes are migrated for selected nodes.
   *
   * @test
   */
  public function onlyMigratesLimitedNodes() {
    $sourceTerm = $this->createTerm($this->vocabulary);
    $targetTerm = $this->createTerm($this->vocabulary);
    $node1 = $this->createNode(['field_terms' => ['target_id' => $sourceTerm->id()]]);
    $node2 = $this->createNode(['field_terms' => ['target_id' => $sourceTerm->id()]]);

    $sut = new ReferenceMigrator($this->entityTypeManager, \Drupal::service('term_reference_change.reference_finder'));
    $sut->migrateReference($sourceTerm, $targetTerm, ['node' => [$node1->id()]]);

    $this->assertNodeReferencesTerm($node1, $targetTerm);
    $this->assertNodeReferencesTerm($node2, $sourceTerm);
  }

  /**
   * Tests term merging does not fail when the target field is missing.
   *
   * @test
   */
  public function doesNotFailWhenReferenceFieldIsMissing() {
    $this->setUpContentType('article', 'field_category');
    $sourceTerm = $this->createTerm($this->vocabulary);
    $targetTerm = $this->createTerm($this->vocabulary);
    $node1 = $this->createNode(['field_terms' => ['target_id' => $sourceTerm->id()]]);

    $sut = new ReferenceMigrator($this->entityTypeManager, \Drupal::service('term_reference_change.reference_finder'));
    $sut->migrateReference($sourceTerm, $targetTerm);

    $this->assertNodeReferencesTerm($node1, $targetTerm);
  }

  /**
   * Tests term merging does not fail when the source field is empty.
   *
   * @test
   */
  public function doesNotFailWhenReferenceFieldIsEmpty() {
    $this->setUpContentType('article', 'field_category');
    $sourceTerm = $this->createTerm($this->vocabulary);
    $targetTerm = $this->createTerm($this->vocabulary);
    $node1 = $this->createNode(['field_terms' => ['target_id' => $sourceTerm->id()]]);

    $sut = new ReferenceMigrator($this->entityTypeManager, \Drupal::service('term_reference_change.reference_finder'));
    $sut->migrateReference($sourceTerm, $targetTerm);

    $this->assertNodeReferencesTerm($node1, $targetTerm);
  }

  /**
   * Tests term merging does not create duplicate values.
   *
   * @test
   */
  public function doesNotCreateDuplicates() {
    $this->setUpContentType('article', 'field_category');
    $sourceTerm = $this->createTerm($this->vocabulary);
    $targetTerm = $this->createTerm($this->vocabulary);
    $node1 = $this->createNode(['field_terms' => ['target_id' => $sourceTerm->id()], ['target_id' => $targetTerm->id()]]);

    $sut = new ReferenceMigrator($this->entityTypeManager, \Drupal::service('term_reference_change.reference_finder'));
    $sut->migrateReference($sourceTerm, $targetTerm);

    $this->assertNodeReferencesTerm($node1, $targetTerm);
  }

  /**
   * Check a taxonomy term is referenced in a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The target node.
   * @param \Drupal\taxonomy\TermInterface $targetTerm
   *   The target taxonomy term.
   */
  private function assertNodeReferencesTerm(NodeInterface $node, TermInterface $targetTerm) {
    /** @var \Drupal\node\Entity\Node $loadedNode */
    $loadedNode = $this->entityTypeManager->getStorage('node')
      ->load($node->id());
    $referencedTerms = $loadedNode->field_terms->getValue();
    self::assertCount(1, $referencedTerms);
    $firstReference = reset($referencedTerms);
    self::assertEquals($targetTerm->id(), $firstReference['target_id']);
  }

  /**
   * Set up a content type for testing purposes.
   */
  private function setUpContentType($bundle, $fieldName) {
    $this->createContentType([
      'type' => $bundle,
      'name' => ucfirst($bundle),
      'display_submitted' => FALSE,
    ]);

    $entityType = 'node';
    $fieldLabel = 'Terms';
    $targetEntityType = 'taxonomy_term';
    $this->createEntityReferenceField($entityType, $bundle, $fieldName, $fieldLabel, $targetEntityType);
  }

}
