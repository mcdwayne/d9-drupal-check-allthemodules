<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class FormDisplayModeAlter.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "form_display_mode_alter",
 *   label = @Translation("Entity Form display mode alter"),
 *   description = @Translation("Perform alterations on form display mode."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.form_display_mode_alter",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = FALSE,
 *   priority = 1000,
 * )
 */
class FormDisplayModeAlter extends BusinessRulesReactsOnPlugin {

}
