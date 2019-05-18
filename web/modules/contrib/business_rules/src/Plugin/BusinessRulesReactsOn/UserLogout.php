<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserLogout.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "user_logout",
 *   label = @Translation("User has logged out"),
 *   description = @Translation("Reacts after the user has logged out."),
 *   group = @Translation("User"),
 *   eventName = "business_rules.user_logout",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class UserLogout extends BusinessRulesReactsOnPlugin {

  /**
   * {@inheritdoc}
   */
  public function processForm(array &$form, FormStateInterface $form_state) {
    parent::processForm($form, $form_state);

    $form['entity']['context']['target_entity_type']['#required'] = FALSE;
    $form['entity']['context']['target_entity_type']['#value']    = 'user';
    $form['entity']['context']['target_entity_type']['#disabled'] = TRUE;
    $form['entity']['context']['target_entity_type']['#options']  = [
      'user' => $form['entity']['context']['target_entity_type']['#options']['user'],
    ];

    $form['entity']['context']['target_bundle']['#options']  = ['user' => t('User')];
    $form['entity']['context']['target_bundle']['#required'] = FALSE;
    $form['entity']['context']['target_bundle']['#value']    = 'user';
    $form['entity']['context']['target_bundle']['#disabled'] = TRUE;

  }

}
