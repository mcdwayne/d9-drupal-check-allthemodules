<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class EntityIsViewed.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "entity_is_viewed",
 *   label = @Translation("Entity is viewed"),
 *   description = @Translation("Reacts when entity is viewed."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.entity_is_viewed",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class EntityIsViewed extends BusinessRulesReactsOnPlugin {

}
