<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class EntityLoaded.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "entity_is_loaded",
 *   label = @Translation("Entity is loaded"),
 *   description = @Translation("Reacts when entity is loaded."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.entity_is_loaded",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1,
 * )
 */
class EntityLoaded extends BusinessRulesReactsOnPlugin {

}
