<?php

namespace Drupal\crm_core_contact\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for the Individual entity.
 */
class IndividualForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $individual = $this->entity;

    $status = $individual->save();

    $t_args = ['%name' => $individual->label(), 'link' => $individual->url()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The individual %name has been updated.', $t_args));
      if ($individual->access('view')) {
        $form_state->setRedirect('entity.crm_core_individual.canonical', ['crm_core_individual' => $individual->id()]);
      }
      else {
        $form_state->setRedirect('entity.crm_core_individual.collection');
      }
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The individual %name has been added.', $t_args));
      \Drupal::logger('crm_core_individual')->notice('Added individual %name.', $t_args);
      $form_state->setRedirect('entity.crm_core_individual.collection');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save @individual_type', [
      '@individual_type' => $this->entity->get('type')->entity->label(),
    ]);
    return $actions;
  }

}
