<?php

namespace Drupal\webform_scheduled_tasks\Iterator;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * The WebformIteratorAggregate class.
 */
class WebformIteratorAggregate implements \IteratorAggregate, \Countable {

  /**
   * An array of webform submission IDs.
   *
   * @var array
   */
  protected $submissionIds;

  /**
   * The chunk size.
   *
   * @var int
   */
  protected $chunkSize;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $submissionStorage;

  /**
   * WebformIteratorAggregate constructor.
   */
  public function __construct($submissionIds, $chunkSize, ContentEntityStorageInterface $submissionStorage) {
    $this->submissionIds = $submissionIds;
    $this->chunkSize = $chunkSize;
    $this->submissionStorage = $submissionStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->submissionIds);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    foreach (array_chunk($this->submissionIds, $this->chunkSize) as $ids_chunk) {
      foreach ($this->submissionStorage->loadMultiple($ids_chunk) as $entity) {
        yield $entity;
      }
    }
  }

  /**
   * Create an iterator aggregate from an entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   An un-executed entity query for webform submissions.
   *
   * @return static
   */
  public static function createFromQuery(QueryInterface $query) {
    return new static(
      $query->execute(),
      50,
      \Drupal::service('entity_type.manager')->getStorage('webform_submission')
    );
  }

}
