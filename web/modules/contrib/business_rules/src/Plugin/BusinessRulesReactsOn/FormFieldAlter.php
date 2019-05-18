<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class FormAlter.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "form_field_alter",
 *   label = @Translation("Entity field form alter"),
 *   description = @Translation("Reacts when entity form field is being prepared."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.form_field_alter",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class FormFieldAlter extends BusinessRulesReactsOnPlugin {

}
