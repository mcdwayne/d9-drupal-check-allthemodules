<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class EntityPreSave.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "entity_presave",
 *   label = @Translation("Before saving entity"),
 *   description = @Translation("Reacts before save the entity."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.entity_presave",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class EntityPreSave extends BusinessRulesReactsOnPlugin {

}
