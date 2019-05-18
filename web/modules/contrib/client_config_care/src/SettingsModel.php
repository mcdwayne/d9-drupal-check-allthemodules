<?php

namespace Drupal\client_config_care;

class SettingsModel {

  /**
   * @var bool|null
   */
  private $deactivated;

  public function __construct($deactivated = null)
  {
    $this->deactivated = $deactivated ?? FALSE;
  }

  /**
   * @return bool
   */
  public function isDeactivated(): bool
  {
    return $this->deactivated;
  }

  /**
   * @param bool $deactivated
   */
  public function setDeactivated(bool $deactivated): void
  {
    $this->deactivated = $deactivated;
  }

}
