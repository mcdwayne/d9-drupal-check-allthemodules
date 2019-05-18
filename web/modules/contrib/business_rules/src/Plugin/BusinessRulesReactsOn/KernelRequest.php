<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KernelRequest.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "kernel_request",
 *   label = @Translation("Kernel request"),
 *   description = @Translation("Reacts on every kernel request. Use carefully."),
 *   group = @Translation("System"),
 *   eventName = "business_rules.kernel_request",
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   priority = 1000,
 * )
 */
class KernelRequest extends BusinessRulesReactsOnPlugin {

  /**
   * {@inheritdoc}
   */
  public function processForm(array &$form, FormStateInterface $form_state) {
    parent::processForm($form, $form_state);

    unset($form['entity']['context']);

  }

}
