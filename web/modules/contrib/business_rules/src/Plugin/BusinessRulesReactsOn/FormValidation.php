<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormBuild.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "form_validation",
 *   label = @Translation("Entity form validation"),
 *   description = @Translation("Reacts when entity form is being validated."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.form_validation",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class FormValidation extends BusinessRulesReactsOnPlugin {

  /**
   * Performs the BusinessRule form validation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function validateForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\business_rules\Util\BusinessRulesProcessor $processor */
    /** @var \Drupal\business_rules\Events\BusinessRulesEvent $event */
    /** @var \Drupal\Core\Entity\Entity $entity */

    $event = $form_state->get('business_rules_event');
    $event->setArgument('form_state', $form_state);

    // Set a new entity to be the form_state entity values. The comparison will
    // gonna be against this dummy entity.
    $entityManager         = \Drupal::entityTypeManager();
    $entity                = $form_state->getFormObject()->getEntity();
    $entity_values         = $form_state->getValues();
    $entity_values['type'] = $entity->bundle();

    // For some reason that I'm lazy to look for, the Comment entity does not
    // use the key "type" to specify the bundle. It uses "comment_type" instead.
    if ($entity->getEntityTypeId() == 'comment') {
      $entity_values['comment_type'] = $entity->bundle();
    }

    try {
      $new_entity = $entityManager->getStorage($entity->getEntityTypeId())
        ->create($entity_values);
    }
    catch (\Exception $e) {
      // The dummy entity could not be created. Let's try force field values.
      $new_entity = clone $entity;
      $array      = $new_entity->toArray();
      foreach ($entity_values as $key => $entity_value) {
        try {
          if (in_array($key, array_keys($array))) {
            $new_entity->$key->setValue($entity_value);
          }
        }
        catch (\Exception $e) {
          // Field not exists.
        }
      }
    }

    $event->setArgument('entity', $new_entity);
    $event->setArgument('form_state', $form_state);

    // The BusinessRulesProcessor process the items after form submission.
    // To form validation we need to process the rule's items before it.
    // In this case we need to call the processor right now.
    $processor = \Drupal::getContainer()->get('business_rules.processor');
    $processor->process($event);
  }

}
