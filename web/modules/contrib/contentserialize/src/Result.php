<?php

namespace Drupal\contentserialize;

/**
 * Result class for the Content Serialization import/export process.
 */
class Result {

  protected $completed;
  protected $failed;

  /**
   * Create a new Result object.
   *
   * @param array $completed
   *   Details of successfully completed imports/exports, with the structure
   * @code
   * [
   *   'node' => [
   *     'article' => [$uuid1, $uuid2],
   *   ],
   * ]
   * @endcode
   * @param array $failed
   *   Details of failed entity imports/exports, with the structure
   * @code
   * [
   *   'node' => [
   *     'article' => [
   *        $uuid1 => "Error message 1",
   *        $uuid2 => "Error message 2",
   *     ],
   *   ],
   * ]
   * @endcode
   */
  public function __construct(array $completed, array $failed) {
    $this->completed = $completed;
    $this->failed = $failed;
  }

  public function getFailures() {
    return $this->failed;
  }

  public function getImportDetails() {
    return $this->completed;
  }

  public function getImportedUuids() {
    $uuids = [];
    foreach ($this->iterateImportDetails() as $details) {
      $uuids[] = $details['uuid'];
    }
    return $uuids;
  }

  public function getImportCount() {
    $count = 0;
    foreach ($this->iterateImportDetails() as $details) {
      $count++;
    }
    return $count;
  }

  protected function iterateImportDetails() {
    foreach ($this->completed as $entity_type_id => $entity_type) {
      foreach ($entity_type as $bundle => $uuids) {
        // @todo: PHP 7.x: Use yield from.
        foreach ($uuids as $uuid) {
          yield [
            'entity_type_id' => $entity_type_id,
            'bundle' => $bundle,
            'uuid' => $uuid,
          ];
        }
      }
    }
  }

}