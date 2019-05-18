<?php

namespace Drupal\config_actions\Plugin\ConfigActionsSource;

use Drupal\config_actions\ConfigActionsSourceBase;

/**
 * Plugin for handling a list of source locations.
 *
 * @ConfigActionsSource(
 *   id = "list",
 *   description = @Translation("List of sources."),
 * )
 */
class ConfigActionsList extends ConfigActionsSourceBase {

  /**
   * Return True if array is Sequential
   * @param array $arr
   * @return bool
   */
  protected function isSequential($arr)
  {
    if (array() === $arr) return false;
    return is_array($arr) && (array_keys($arr) == range(0, count($arr) - 1));
  }

  /**
   * {@inheritdoc}
   */
  public function detect($source) {
    return $this->isSequential($source);
  }

  /**
   * {@inheritdoc}
   */
  public function doLoad() {
    $data = [];
    // Loop through sources and pick first success
    foreach ($this->sourceId as $source) {
      $data = $this->actionService->loadSource($source, '', $this->sourceBase);
      if (!empty($data)) {
        break;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function doSave($data) {
    $result = FALSE;
    // Loop through sources and pick first success
    foreach ($this->sourceId as $source) {
      $result = $this->actionService->saveSource($data, $source, '', $this->sourceBase);
      if ($result) {
        break;
      }
    }
    return $result;
  }

}
