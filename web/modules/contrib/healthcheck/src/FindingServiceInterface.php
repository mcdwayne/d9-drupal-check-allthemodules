<?php

namespace Drupal\healthcheck;

/**
 * Interface FindingServiceInterface.
 */
interface FindingServiceInterface {

  /**
   * Builds a new finding with the given key and status.
   *
   * @param $check
   *   The Healthcheck plugin that produced this Finding.
   * @param $key
   *   The unique key of the finding.
   * @param $status
   *   The status of the finding.
   * @param array $data
   *   A key-value array that can be used for replacements. Optional.
   *
   * @return \Drupal\healthcheck\Finding\FindingInterface
   *   A new finding with the given key.
   */
  public function build($check, $key, $status, $data = []);

  /**
   * Gets the finding label.
   *
   * @param $key
   *   The unique key of the finding.
   * @param $status
   *   The status of the finding.
   * @param array $data
   *   A key-value array that can be used for replacements. Optional.
   *
   * @return string|bool
   *   The human readable label of the finding if found, FALSE otherwise.
   */
  public function getLabel($key, $status, $data = []);

  /**
   * Gets the finding message.
   *
   * @param $key
   *   The unique key of the finding.
   * @param $status
   *   The status of the finding.
   * @param array $data
   *   A key-value array that can be used for replacements. Optional.
   *
   * @return string|bool
   *   The message of the finding if found, FALSE otherwise.
   */
  public function getMessage($key, $status, $data = []);
}
