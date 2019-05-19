<?php
/**
 * @file
 * Defines XHProf sample abstract class.
 */

namespace Drupal\xhprof_sample\XHProfSample;

abstract class Run {
  /**
   * {@inheritdoc}
   */
  public function setData($sample_data) {
    $this->data = $sample_data;
  }

  /**
   * {@inheritdoc}
   */
  public function setMetadata($run_metadata) {
    $this->metadata = $run_metadata;
  }
}
