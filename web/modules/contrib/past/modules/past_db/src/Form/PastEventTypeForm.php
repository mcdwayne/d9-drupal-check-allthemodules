<?php

namespace Drupal\past_db\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit form for a Past Event type.
 */
class PastEventTypeForm extends EntityForm {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'past_event_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

//    @todo Entity API remnant? How to do in D8?
//    if ($op == 'clone') {
//      $past_event_type->label .= ' (cloned)';
//      $past_event_type->type = '';
//    }

    $form['label'] = [
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->label(),
      '#description' => t('The human-readable name of this past_event type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    // Machine-readable type name.
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#maxlength' => 32,
      // '#disabled' => $this->entity->isLocked() && $op != 'clone', @todo Obsolete?
      '#machine_name' => [
        'exists' => 'past_event_get_types',
        'source' => ['label'],
      ],
      '#description' => t('A unique machine-readable name for this past_event type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    // @todo do we need this?
    $form['data']['#tree'] = TRUE;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save past event type'),
      '#weight' => 40,
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#default_value' => $this->entity->weight,
      '#description' => t('When showing past_events, those with lighter (smaller) weights get listed before past_events with heavier (larger) weights.'),
      '#weight' => 10,
    ];

    if (!$this->entity->isLocked() /* @todo && $op != 'add' && $op != 'clone' */) {
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => t('Delete past_event type'),
        '#weight' => 45,
        '#limit_validation_errors' => [],
        '#submit' => ['::submitDelete'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save and go back.
    parent::submitForm($form, $form_state);
    $this->entity->save();
    $form_state->setRedirect('past_db.event_type.list');
  }

  /**
   * Submit handler for the Delete button.
   *
   * @param array $form
   *   The form structure.
   * @param FormStateInterface $form_state
   *   The form state.
   */
  public function submitDelete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('past_db.event_type.delete', ['past_event_type' => $this->entity->id()]);
  }

}
