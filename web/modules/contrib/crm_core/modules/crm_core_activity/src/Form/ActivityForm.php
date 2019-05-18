<?php

namespace Drupal\crm_core_activity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ActivityForm.
 */
class ActivityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $activity = $this->entity;

    $status = $activity->save();

    $t_args = ['%title' => $activity->label(), 'link' => $activity->url()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Activity %title edited.', $t_args));
      if ($activity->access('view')) {
        $form_state->setRedirect('entity.crm_core_activity.canonical', ['crm_core_activity' => $activity->id()]);
      }
      else {
        $form_state->setRedirect('entity.crm_core_contact.collection');
      }
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('Activity %title created.', $t_args));
      \Drupal::logger('crm_core_contact')->notice('Activity %title created.', $t_args);
      $form_state->setRedirect('entity.crm_core_contact.collection');
    }

    if ($activity->access('view')) {
      $form_state->setRedirect('entity.crm_core_activity.canonical', ['crm_core_activity' => $activity->id()]);
    }
    else {
      $form_state->setRedirect('entity.crm_core_activity.collection');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save Activity');
    return $actions;
  }

}
