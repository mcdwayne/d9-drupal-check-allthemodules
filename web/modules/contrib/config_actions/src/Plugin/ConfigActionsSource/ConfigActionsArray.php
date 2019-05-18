<?php

namespace Drupal\config_actions\Plugin\ConfigActionsSource;

use Drupal\config_actions\ConfigActionsSourceBase;

/**
 * Plugin for storing source data from an array.
 * This runs last to allow any other plugins to detect themselves first.
 *
 * @ConfigActionsSource(
 *   id = "array",
 *   description = @Translation("Default use array storage."),
 *   weight = "99",
 * )
 */
class ConfigActionsArray extends ConfigActionsSourceBase {

  /**
   * {@inheritdoc}
   */
  public function detect($source) {
    return is_array($source);
  }

  /**
   * {@inheritdoc}
   */
  public function doLoad() {
    $this->setMerge(TRUE);
    return $this->sourceId;
  }

  /**
   * {@inheritdoc}
   */
  public function doSave($data) {
    $this->sourceId = $data;
    return TRUE;
  }

}
