<?php

namespace Drupal\impression\Plugin\QueueWorker;

/**
 * A house keeping program that clean old impression data on CRON run.
 *
 * @QueueWorker(
 *   id = "cron_house_keeper",
 *   title = @Translation("Cron Impression House Keeping"),
 *   cron = {"time" = 30}
 * )
 */
class CronHouseKeeper extends HouseKeeperBase {}
