<?php

namespace Drupal\entity_access_audit;

use Drupal\entity_access_audit\Dimensions\EntityTypeDimension;

/**
 * A collection of audit results.
 */
class AccessAuditResultCollection {

  /**
   * The dimensions of the audit result collection.
   *
   * @var \Drupal\entity_access_audit\AccessDimensionInterface[]
   */
  protected $dimensions;

  /**
   * The access results, the cartesian product of all dimensions.
   *
   * @var \Drupal\entity_access_audit\AccessAuditResult[]
   */
  protected $results;

  /**
   * Create an instance of an AccessAuditResultCollection.
   *
   * @param \Drupal\entity_access_audit\AccessDimensionInterface[] $dimensions
   *   The dimensions used to create the collection of access checks.
   */
  public function __construct(array $dimensions) {
    $this->dimensions = $dimensions;
  }

  /**
   * @param \Drupal\entity_access_audit\AccessAuditResult $result
   */
  public function addAuditResult(AccessAuditResult $result) {
    $this->results[$this->getDimensionsLookupKey($result->getDimensions())] = $result;
  }

  /**
   * Get the classes which make up the dimensions of this collection.
   *
   * @return array
   *   An array of static class references for the dimensions of the colleciton.
   */
  public function getDimensionClasses() {
    return array_keys($this->dimensions);
  }

  /**
   * Get all dimensions in this collection matching a type.
   *
   * @param string $type
   *   The dimension type.
   *
   * @return \Drupal\entity_access_audit\AccessDimensionInterface[]
   *   Dimensions.
   */
  public function getDimensionsOfType($type) {
    return isset($this->dimensions[$type]) ? $this->dimensions[$type] : [];
  }

  /**
   * Check if the collection has a type of dimension.
   *
   * @param string $type
   *   The dimension type.
   *
   * @return bool
   *   If the dimension type exists.
   */
  public function hasDimensionType($type) {
    return isset($this->dimensions[$type]);
  }

  /**
   * The total number of access results checked.
   *
   * @return int
   *   The count.
   */
  public function count() {
    return count($this->results);
  }

  /**
   * Get a lookup key for some given dimensions.
   *
   * @param \Drupal\entity_access_audit\AccessDimensionInterface[] $dimensions
   *   The dimensions to get a key for.
   *
   * @return string
   *   A key.
   */
  protected function getDimensionsLookupKey($dimensions) {
    $dimension_keys = [];
    foreach ($dimensions as $dimension) {
      $class_key = substr(md5(get_class($dimension)), 0, 5);
      $dimension_keys[$class_key] = $class_key . '-' . $dimension->id();
    }
    ksort($dimension_keys);
    return implode(':', $dimension_keys);
  }

  /**
   * Get the access results matching the specific dimensions.
   *
   * @param \Drupal\entity_access_audit\AccessDimensionInterface[]
   *   The dimensions to lookup access results for.
   *
   * @return \Drupal\entity_access_audit\AccessAuditResult
   *   An access audit result.
   *
   * @throws \Exception
   *   Must get access results with exact matching dimensions.
   */
  public function getAuditResultMatchingDimensions($dimensions) {
    // Automatically add the entity type dimension class. If we create
    // collections with multiple entity types, we could remove this.
    if (isset($this->dimensions[EntityTypeDimension::class][0])) {
      $dimensions[] = $this->dimensions[EntityTypeDimension::class][0];
    }

    $key = $this->getDimensionsLookupKey($dimensions);
    if (!isset($this->results[$key])) {
      throw new \Exception('Could not audit result matching key: ' . $key);
    }
    return $this->results[$key];
  }

}
