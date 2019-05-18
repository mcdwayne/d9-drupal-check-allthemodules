<?php

namespace Drupal\client_config_care\Exception;


class ExistingConfigBlockerException extends \Exception {

  /**
   * @var string
   */
  private $configName;

  public function __construct(string $configName)
  {
    $message = "Config blocker for config named $configName is existing unexpectedly.";

    parent::__construct($message, $code = 0, $previous = null);
    $this->configName = $configName;
  }

  public function getNoticeMessage() {
    return "You might check and delete the config blocker entity with name $this->configName to allow the intended operation.";
  }

}
