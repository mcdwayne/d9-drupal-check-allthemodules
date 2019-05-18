<?php

namespace Drupal\developer_suite_examples\Plugin\EntityTypeClass;

use Drupal\developer_suite\Storage\FileStorage;
use Drupal\developer_suite_examples\Entity\ExampleFile;

/**
 * Class ExampleFileTypeClass.
 *
 * @package Drupal\developer_suite_examples\EntityStorage
 *
 * @EntityTypeClass(
 *   id = "example_file_type_class",
 *   entity = "file",
 *   label = @Translation("Example file type class"),
 * )
 */
class ExampleFileTypeClass extends FileStorage {

  /**
   * Returns the entity class.
   *
   * @return mixed
   *   The entity class.
   */
  public function getEntityTypeClass() {
    return ExampleFile::class;
  }

}
