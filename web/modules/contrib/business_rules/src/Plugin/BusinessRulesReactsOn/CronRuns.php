<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class CronRuns.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "cron_runs",
 *   label = @Translation("On Cron Runs"),
 *   description = @Translation("Reacts every time cron runs."),
 *   group = @Translation("System"),
 *   eventName = "business_rules.cron_runs",
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   priority = 1000,
 * )
 */
class CronRuns extends BusinessRulesReactsOnPlugin {

}
