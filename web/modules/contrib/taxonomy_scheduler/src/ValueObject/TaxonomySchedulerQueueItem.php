<?php

namespace Drupal\taxonomy_scheduler\ValueObject;

use Drupal\taxonomy_scheduler\Exception\TaxonomySchedulerException;

/**
 * Class TaxonomySchedulerQueueItem.
 */
class TaxonomySchedulerQueueItem {

  /**
   * TermId.
   *
   * @var int
   */
  private $termId;

  /**
   * TaxonomySchedulerQueueItem constructor.
   *
   * @param array $queueItem
   *   The queue item.
   */
  public function __construct(array $queueItem) {
    if (!$this->isValid($queueItem)) {
      throw new TaxonomySchedulerException('The given data is not valid for creating a TaxonomySchedulerQueueItem.');
    }

    $this->termId = (int) $queueItem['termId'];
  }

  /**
   * Determines whether $queueItem is valid.
   *
   * @param array $queueItem
   *   The queue item.
   *
   * @return bool
   *   TRUE, or FALSE.
   */
  private function isValid(array $queueItem): bool {
    if (empty($queueItem['termId'])) {
      return FALSE;
    }

    if (!\is_numeric($queueItem['termId'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the term id.
   *
   * @return int
   *   The term id.
   */
  public function getTermId(): int {
    return $this->termId;
  }

}
