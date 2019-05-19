<?php

namespace Drupal\yaml_content;

/**
 * A base implementation of a content import processor.
 *
 * This class should be extended by all import processors.
 *
 * @see \Drupal\yaml_content\ImportProcessorInterface
 */
abstract class ImportProcessorBase extends ContentProcessorBase implements ImportProcessorInterface {

  /**
   * Indicate that this plugin supports import operations.
   *
   * @var bool
   */
  public $import = TRUE;

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$import_data) {}

  /**
   * {@inheritdoc}
   */
  public function postprocess(array &$import_data, &$imported_content) {}

}
