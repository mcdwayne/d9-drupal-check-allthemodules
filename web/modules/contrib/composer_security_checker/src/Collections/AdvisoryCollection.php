<?php

namespace Drupal\composer_security_checker\Collections;

use Drupal\composer_security_checker\Models\Advisory;

/**
 * Class AdvisoryCollection.
 *
 * @package Drupal\composer_security_checker\Collections
 */
class AdvisoryCollection implements \Countable, \IteratorAggregate {

  /**
   * An array of Advisories.
   *
   * @var array
   */
  protected $advisories = [];

  /**
   * Add a security advisory to the collection.
   *
   * @param Advisory $advisory
   *   An Advisory instance.
   */
  public function add(Advisory $advisory) {
    $this->advisories[] = $advisory;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->advisories);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->advisories);
  }

  /**
   * Get the current list of assigned advisories.
   *
   * @return array
   *   An array containing the contents of the collection.
   */
  public function getAdvisories() {
    return $this->advisories;
  }

  /**
   * Ingest another collection into this one.
   *
   * @param \Drupal\composer_security_checker\Collections\AdvisoryCollection $collection
   *   A collection item to be merged.
   */
  public function ingest(AdvisoryCollection $collection) {
    foreach ($collection as $collection_item) {
      $this->add($collection_item);
    }
  }

}
