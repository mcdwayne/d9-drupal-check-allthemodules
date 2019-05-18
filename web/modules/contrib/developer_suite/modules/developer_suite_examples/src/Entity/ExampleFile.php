<?php

namespace Drupal\developer_suite_examples\Entity;

use Drupal\file\Entity\File;

/**
 * Class ExampleFile.
 *
 * When overriding a core entity type class please bear in mind that extending
 * the core entity type class is recommended. By extending the core entity type
 * class in your custom entity class you automatically have access to all the
 * core methods.
 *
 * @package Drupal\developer_suite_examples\Entity
 */
class ExampleFile extends File {

  /**
   * Perform some operation.
   */
  public function someOperation() {

  }

}
