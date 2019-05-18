<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class EntityDelete.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "entity_delete",
 *   label = @Translation("Entity delete"),
 *   description = @Translation("Reacts after a new entity has been deleted."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.entity_delete",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class EntityDelete extends BusinessRulesReactsOnPlugin {

}
