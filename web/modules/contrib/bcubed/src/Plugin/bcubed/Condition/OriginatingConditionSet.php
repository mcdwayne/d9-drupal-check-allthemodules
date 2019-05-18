<?php

namespace Drupal\bcubed\Plugin\bcubed\Condition;

use Drupal\bcubed\ConditionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides basic condition to restrict condition sets to running on every nth page.
 *
 * @Condition(
 *   id = "originating_condition_set",
 *   label = @Translation("Originating Condition Set"),
 *   description = @Translation("Restrict event to a specific originating condition set."),
 *   settings = {
 *     "condition_set" = "",
 *   },
 *   bcubed_dependencies = {
 *    {
 *      "plugin_type" = "event",
 *      "plugin_id" = "*",
 *      "same_set" = true,
 *      "dependency_type" = "generated_by",
 *    }
 *  }
 * )
 */
class OriginatingConditionSet extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed/restrict_conditionset';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Fetch all existing condition sets.
    $existing_condition_sets = \Drupal::entityTypeManager()->getStorage('condition_set')->loadMultiple();
    // Exclude current condition set from list if it exists.
    $entity = $form_state->getFormObject()->getEntity();
    if (!is_null($entity->id())) {
      unset($existing_condition_sets[$entity->id()]);
    }

    // Create options.
    $options = [];
    foreach ($existing_condition_sets as $condition_set) {
      $options[$condition_set->id()] = $condition_set->label();
    }

    $form['condition_set'] = [
      '#type' => 'select',
      '#title' => 'Originating Condition Set',
      '#description' => 'Resrict to generated events from the selected condition set',
      '#options' => $options,
      '#default_value' => $this->settings['condition_set'],
      '#required' => TRUE,
    ];

    return $form;
  }

}
