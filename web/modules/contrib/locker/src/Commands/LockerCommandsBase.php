<?php

namespace Drupal\locker\Commands;

use Drush\Commands\DrushCommands;

/**
 *
 */
abstract class LockerCommandsBase extends DrushCommands {

  /**
   * The locker CLI service.
   *
   * @var \Drupal\locker\Commands\LockerCliServiceInterface
   */
  protected $cliService;

  /**
   * LockerCommandsBase constructor.
   *
   * @param \Drupal\locker\Commands\LockerCliServiceInterface $cli_service
   */
  public function __construct(LockerCliServiceInterface $cli_service) {
    $this->cliService = $cli_service;
    $this->cliService->setCommand($this);
  }

}
