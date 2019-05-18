<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class EntityInsert.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "entity_insert",
 *   label = @Translation("Entity insert"),
 *   description = @Translation("Reacts after a new entity has been inserted."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.entity_insert",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class EntityInsert extends BusinessRulesReactsOnPlugin {

}
