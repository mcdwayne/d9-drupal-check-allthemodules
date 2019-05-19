<?php

namespace Drupal\term_split;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\TermInterface;
use Drupal\term_reference_change\ReferenceMigrator;

/**
 * Splits a term into two target terms.
 */
class TermSplitter implements TermSplitterInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  private $termStorage;

  /**
   * The term reference migration service.
   *
   * @var \Drupal\term_reference_change\ReferenceMigrator
   */
  private $migrator;

  /**
   * TermSplitter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\term_reference_change\ReferenceMigrator $migrator
   *   The reference migration service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ReferenceMigrator $migrator) {
    $this->entityTypeManager = $entityTypeManager;
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $this->migrator = $migrator;
  }

  /**
   * {@inheritdoc}
   */
  public function splitInTo(TermInterface $sourceTerm, $target1, $target2, array $target1Nids, array $target2Nids) {
    $term1 = $this->createTermIfNotExists($target1, $sourceTerm->bundle());
    $term2 = $this->createTermIfNotExists($target2, $sourceTerm->bundle());

    $this->migrateNodes($sourceTerm, $term1, $target1Nids);
    $this->migrateNodes($sourceTerm, $term2, $target2Nids);

    $this->deleteTerm($sourceTerm);

    $args = [
      '%source' => $sourceTerm->label(),
      '%a' => $term1->label(),
      '%b' => $term2->label(),
    ];
    drupal_set_message($this->t('%source has been split into %a and %b', $args));
  }

  /**
   * Loads a term if it exists or creates a new one if it doesn't.
   *
   * @param string $termName
   *   The term name.
   * @param string $vocabularyId
   *   The vocabulary id.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created or loaded term.
   */
  private function createTermIfNotExists($termName, $vocabularyId) {
    try {
      $term = $this->loadTerm($termName, $vocabularyId);
    }
    catch (EntityStorageException $e) {
      $term = $this->termStorage->create([
        'name' => $termName,
        'vid' => $vocabularyId,
      ]);
      $term->save();
    }

    return $term;
  }

  /**
   * Delete a taxonomy term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term to delete.
   */
  private function deleteTerm(TermInterface $term) {
    $this->termStorage->delete([$term]);
  }

  /**
   * Loads a taxonomy term by name and vocabulary id.
   *
   * @param string $name
   *   The term name.
   * @param string $vocabularyId
   *   The vocabulary id.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The loaded or created taxonomy term.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function loadTerm($name, $vocabularyId) {
    $result = $this->termStorage->loadByProperties([
      'vid' => $vocabularyId,
      'name' => $name,
    ]);

    if (empty($result)) {
      throw new EntityStorageException("{$vocabularyId} does not contain any terms with the name {$name}");
    }

    return reset($result);
  }

  /**
   * Migrates node references from source term to the target term.
   *
   * @param \Drupal\taxonomy\TermInterface $sourceTerm
   *   The term to migrate from.
   * @param \Drupal\taxonomy\TermInterface $targetTerm
   *   The term to migrate to.
   * @param int[] $limitByNids
   *   Only change nodes with an id in this list.
   */
  private function migrateNodes(TermInterface $sourceTerm, TermInterface $targetTerm, array $limitByNids) {
    $limit['node'] = $limitByNids;
    $this->migrator->migrateReference($sourceTerm, $targetTerm, $limit);
  }

}
