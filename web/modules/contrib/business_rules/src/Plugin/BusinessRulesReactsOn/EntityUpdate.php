<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class EntityUpdate.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "entity_update",
 *   label = @Translation("After updating Entity"),
 *   description = @Translation("Reacts after entity has been updated."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.entity_update",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class EntityUpdate extends BusinessRulesReactsOnPlugin {

}
