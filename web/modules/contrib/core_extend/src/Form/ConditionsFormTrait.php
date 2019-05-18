<?php

namespace Drupal\core_extend\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;

/**
 * Provides a trait for a form with plugin conditions.
 */
trait ConditionsFormTrait {

  /**
   * Returns the condition manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition manager.
   */
  abstract protected function conditionManager();

  /**
   * Returns the array of condition definitions to use.
   *
   * @return array
   *   The array of condition definitions to use.
   */
  abstract protected function getConditionDefinitions();

  /**
   * Returns the condition previous configuration via condition collection.
   *
   * @return \Drupal\Core\Condition\ConditionPluginCollection
   *   The condition previous configuration via condition collection.
   */
  abstract protected function getConditions();

  /**
   * Helper function for building the conditions UI form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $parent_field_id
   *   The parent field id.
   *
   * @return array
   *   The form array with the conditions UI added in.
   */
  protected function formConditions(array $form, FormStateInterface $form_state, $parent_field_id = 'conditions') {
    $form['conditions_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Conditions'),
      '#parents' => ['conditions_tabs'],
    ];

    $conditions = $this->getConditions();
    foreach ($this->getConditionDefinitions() as $condition_id => $definition) {
      $condition = $this->conditionManager()->createInstance($condition_id, $conditions->has($condition_id) ? $conditions->get($condition_id)->getConfiguration() : []);
      $form_state->set([$parent_field_id, $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'conditions_tabs';
      $form[$condition_id] = $condition_form;
    }
    return $form;
  }

  /**
   * Helper function to independently validate the conditions UI.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $parent_field_id
   *   The parent field id.
   */
  protected function validateConditions(array $form, FormStateInterface $form_state, $parent_field_id = 'conditions') {
    // Validate conditions condition settings.
    foreach ($form_state->getValue($parent_field_id, []) as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue([$parent_field_id, $condition_id, 'negate'], (bool) $values['negate']);
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get([$parent_field_id, $condition_id]);
      $condition->validateConfigurationForm($form[$parent_field_id][$condition_id], SubformState::createForSubform($form[$parent_field_id][$condition_id], $form, $form_state));
    }
  }

  /**
   * Helper function to independently submit the conditions UI.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $parent_field_id
   *   The parent field id.
   */
  protected function submitConditions(array $form, FormStateInterface $form_state, $parent_field_id = 'conditions') {
    foreach ($form_state->getValue($parent_field_id, []) as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get([$parent_field_id, $condition_id]);
      $condition->submitConfigurationForm($form[$parent_field_id][$condition_id], SubformState::createForSubform($form[$parent_field_id][$condition_id], $form, $form_state));
      $condition_configuration = $condition->getConfiguration();
      // Update the conditions conditions on the block.
      $this->getConditions()->addInstanceId($condition_id, $condition_configuration);
    }
  }

}
