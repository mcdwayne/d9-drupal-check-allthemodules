<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class PageLoad.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "page_load",
 *   label = @Translation("Page Load"),
 *   description = @Translation("Reacts during the page load."),
 *   group = @Translation("System"),
 *   eventName = "business_rules.page_load",
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   priority = 1000,
 * )
 */
class PageLoad extends BusinessRulesReactsOnPlugin {

}
