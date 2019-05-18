<?php

namespace Drupal\client_config_care;

use Drupal\Core\Site\Settings;

class SettingsFactory {

  public function create(): SettingsModel {
    $settingsArray = Settings::get('client_config_care');

    return new SettingsModel($settingsArray['deactivated']);
  }

}
