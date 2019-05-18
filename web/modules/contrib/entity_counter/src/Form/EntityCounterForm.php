<?php

namespace Drupal\entity_counter\Form;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Number as NumberUtility;
use Drupal\entity_counter\EntityCounterStatus;

/**
 * Provides form for entity counter instance forms.
 */
class EntityCounterForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter */
    $entity_counter = $this->entity;

    $form['#tree'] = TRUE;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_counter->label(),
      '#description' => $this->t('Label for the entity counter.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH,
      '#description' => $this->t('A unique name for this entity counter instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => !$entity_counter->isNew() ? $entity_counter->id() : NULL,
      '#machine_name' => [
        'exists' => '\Drupal\entity_counter\Entity\EntityCounter::load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => ['label'],
      ],
      '#required' => TRUE,
      '#disabled' => !$entity_counter->isNew(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity_counter->getDescription(),
    ];
    $form['min'] = [
      '#type' => 'number',
      '#step' => '0.01',
      '#title' => $this->t('Minimum value'),
      '#default_value' => $entity_counter->getMin(),
    ];
    $form['max'] = [
      '#type' => 'number',
      '#step' => '0.01',
      '#title' => $this->t('Maximum value'),
      '#default_value' => $entity_counter->getMax(),
    ];
    $form['initial_value'] = [
      '#type' => 'number',
      '#step' => '0.01',
      '#title' => $this->t('Initial value'),
      '#default_value' => empty($entity_counter->getInitialValue()) ? 0 : $entity_counter->getInitialValue(),
      '#required' => TRUE,
      // @TODO: Disable if entity counter has transactions.
      '#disabled' => FALSE,
    ];
    $form['step'] = [
      '#type' => 'number',
      '#step' => '0.01',
      '#title' => $this->t('Step'),
      '#default_value' => empty($entity_counter->getStep()) ? 1 : $entity_counter->getStep(),
      '#description' => $this->t('Ensures that the counter value is an even multiple of step, offset by minimum if specified. A minimum of 1 and a step of 2 would allow values of 1, 3, 5, etc.'),
      '#required' => TRUE,
    ];
    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default status'),
      '#default_value' => $entity_counter->getStatus(),
      '#options' => [
        EntityCounterStatus::OPEN => $this->t('Open'),
        EntityCounterStatus::CLOSED => $this->t('Closed'),
        EntityCounterStatus::MAX_UPPER_LIMIT => $this->t('Open until maximum upper limit'),
      ],
      '#options_display' => 'side_by_side',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();

    // Ensure that the maximum value is greater than the minimum value, if set.
    if (!empty($values['min']) && !empty($values['max']) && $values['min'] > $values['max']) {
      $form_state->setErrorByName('max', $this->t('Maximum value must be greater than minimum value.'));
    }

    // Check that the maximum value is an allowed multiple of step.
    $offset = empty($values['min']) ? 0.0 : $values['min'];
    if (!NumberUtility::validStep($values['max'], $values['step'], $offset)) {
      $form_state->setErrorByName('max', t('The maximum value is not a valid number.'));
    }

    // Check that the initial value is an allowed multiple of step.
    $offset = empty($values['min']) ? 0.0 : $values['min'];
    if (!NumberUtility::validStep($values['initial_value'], $values['step'], $offset)) {
      $form_state->setErrorByName('initial_value', t('The initial value is not a valid number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save the settings.
    $this->entity->save();

    $context = [
      '@label' => $this->entity->label(),
      '@operation' => ($this->entity->isNew()) ? $this->t('saved') : $this->t('updated'),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];
    $this->logger('entity_counter')->notice('Entity counter @label @operation.', $context);

    drupal_set_message($this->t('The entity counter configuration has been saved.'));
    $form_state->setRedirect('entity.entity_counter.canonical', ['entity_counter' => $this->entity->id()]);
  }

}
