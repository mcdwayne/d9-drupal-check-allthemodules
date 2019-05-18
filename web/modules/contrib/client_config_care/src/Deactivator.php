<?php

namespace Drupal\client_config_care;

class Deactivator {

  /**
   * @var SettingsModel
   */
  private $settings;

  public function __construct(SettingsFactory $settingsFactory)
  {
    $this->settings = $settingsFactory->create();
  }

  public function isNotDeactivated(): bool {
    if ($this->settings->isDeactivated()) {
      return FALSE;
    }

    return TRUE;
  }

  public function isDeactivated(): bool {
    if ($this->settings->isDeactivated()) {
      return TRUE;
    }

    return FALSE;
  }

}
