<?php

namespace Drupal\entity_counter_webform\Plugin\EntityCounterCondition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_counter\Plugin\EntityCounterConditionBase;

/**
 * Provides the has value condition for webform submissions.
 *
 * @EntityCounterCondition(
 *   id = "webform_submission_with_value",
 *   label = @Translation("Webform submission with value"),
 *   category = @Translation("Webform submission"),
 *   entity_type = "webform_submission",
 * )
 */
class WebformSubmissionWithValue extends EntityCounterConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field_name' => NULL,
      'operator' => NULL,
      'field_value' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field'),
      '#default_value' => empty($this->configuration['field_name']) ? NULL : $this->configuration['field_name'],
      '#required' => TRUE,
    ];
    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Comparison operator'),
      '#options' => $this->getComparisonOperators(),
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => empty($this->configuration['operator']) ? NULL : $this->configuration['operator'],
      '#required' => TRUE,
    ];
    $form['field_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => isset($this->configuration['field_value']) ? $this->configuration['field_value'] : NULL,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['field_name'] = $values['field_name'];
    $this->configuration['operator'] = $values['operator'];
    $this->configuration['field_value'] = $values['field_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $this->assertEntity($entity);

    $values = $entity->getData();
    if (array_key_exists($this->configuration['field_name'], $values)) {
      $value = $values[$this->configuration['field_name']];
      switch ($this->configuration['operator']) {
        case '<':
          return $value < $this->configuration['field_value'];

        case '<=':
          return $value <= $this->configuration['field_value'];

        case '>=':
          return $value >= $this->configuration['field_value'];

        case '>':
          return $value > $this->configuration['field_value'];

        case '==':
          return $value == $this->configuration['field_value'];
      }
    }

    return FALSE;
  }

}
