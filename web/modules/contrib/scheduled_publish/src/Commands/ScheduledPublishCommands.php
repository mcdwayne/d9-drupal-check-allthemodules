<?php

namespace Drupal\scheduled_publish\Commands;

use Drupal\scheduled_publish\Service\ScheduledPublishCron;
use Drush\Commands\DrushCommands;

class ScheduledPublishCommands extends DrushCommands {

  /**
   * @var \Drupal\scheduled_publish\Service\ScheduledPublishCron
   */
  private $publishCron;

  /**
   * ScheduledPublishCommands constructor.
   *
   * @param \Drupal\scheduled_publish\Service\ScheduledPublishCron $publishCron
   */
  public function __construct(ScheduledPublishCron $publishCron) {
    $this->publishCron = $publishCron;
  }

  /**
   *
   *
   * @command scheduled_publish:doUpdate
   * @aliases schp
   *
   */
  public function doUpdate() {
    $this->publishCron->doUpdate();
    $this->logger()->notice(t('Scheduled publish updates done.'));
  }
}
