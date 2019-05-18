<?php

namespace Drupal\edstep\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EdstepCourseAddButtonForm.
 */
class EdstepCourseAddButtonForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    return $this->actions($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = [];

    if ($this->entity->isNew()) {
      $actions['save'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#submit' => ['::submitForm', '::save'],
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $edstep_course = $this->getEntity();
    drupal_set_message($this->t('The EdStep course %title has been added to this site.', [
      '%title' => $edstep_course->label(),
    ]));
  }

}
