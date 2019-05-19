<?php

namespace Drupal\test_output_viewer;

/**
 * OutputParser service.
 */
interface OutputProcessorInterface {

  /**
   * Processes test output.
   */
  public function process();

}
