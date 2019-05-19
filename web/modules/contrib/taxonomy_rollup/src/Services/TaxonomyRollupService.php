<?php

namespace Drupal\taxonomy_rollup\Services;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\taxonomy\Entity\Term;
use Psr\Log\LoggerInterface;

/**
 * Class for TaxonomyRollupService.
 *
 * Allows the roll up of taxonomy terms.
 */
class TaxonomyRollupService implements TaxonomyRollupServiceInterface {
  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $entityStorage;

  /**
   * Constructs a new TaxonomyRollupServiceInterface.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(LoggerInterface $logger, EntityTypeManager $entityTypeManager) {
    $this->logger = $logger;
    $this->entityStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function getTidFromName($vid, $term_name) {
    $terms = taxonomy_term_load_multiple_by_name($term_name, $vid);
    // Get the matched tids.
    $term_keys = array_keys($terms);
    switch (count($term_keys)) {
      case 1:
        // One and only one match, perfect! Return the tid.
        return $term_keys[0];

      case 0:
        // No matches, return -1.
        $this->logger->warning(t('Term name @name could not be found in vocabulary @vid.',
          [
            '@name' => $term_name,
            '@vid' => $vid,
          ]));
        return -1;

      default:
        // Multiple matches, return -1.
        $this->logger->warning(t('Term name @name was ambiguous and did therefore not resolve to a parent.',
          ['@name' => $term_name]));
        return -1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRollupTermByTermName($vid, $term_name, $maxHeight = -1) {
    $tid = $this->getTidFromName($vid, $term_name);
    if ($tid > -1) {
      return $this->getRollupTermByTid($tid, $maxHeight);
    }
    else {
      // Couldn't determine the base tid.
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRollupTermNameByTermName($vid, $term_name, $maxHeight = -1) {
    $term = $this->getRollupTermByTermName($vid, $term_name, $maxHeight);
    if (empty($term)) {
      // Couldn't find anything to roll up to.
      $this->logger->info(t('Returning default term name'));
      return $term_name;
    }
    // Rolled up OK, return the name of the rollup.
    return $term->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getRollupNameByTid($tid, $maxHeight = -1) {
    $rollup_term = $this->getRollupTermByTid($tid, $maxHeight);
    if (empty($rollup_term)) {
      // Couldn't find anything to roll up to.
      return NULL;
    }
    // Rolled up OK, return the name of the rollup.
    return $rollup_term->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getRollupTidByTid($tid, $maxHeight = -1) {
    // Get all parents which we can roll up to,.
    $parents = $this->entityStorage->loadAllParents($tid);
    // Array of parents, each consecutive element is one 'family' higher.
    $parent_tids = array_keys($parents);
    $rollup_tid = NULL;
    // Specified max height, move up to it if possible.
    if (array_key_exists($maxHeight, $parent_tids) && $maxHeight != -1) {
      $rollup_tid = $parent_tids[$maxHeight];
    }
    else {
      // No max height, return the highest element.
      $rollup_tid = $parent_tids[count($parent_tids) - 1];
    }
    if (empty($rollup_tid)) {
      $this->logger->warning(t('Could not roll up from term @tid.',
        ['@tid' => $tid]));
      return $tid;
    }
    return $rollup_tid;
  }

  /**
   * {@inheritdoc}
   */
  public function getRollupTermByTid($tid, $maxHeight = -1) {
    $tid = $this->getRollupTidByTid($tid, $maxHeight);
    if (empty($tid)) {
      // Could not roll up.
      return NULL;
    }
    return Term::load($tid);
  }

}
