<?php

namespace Drupal\points\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Point edit forms.
 *
 * @ingroup points
 */
class PointForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\points\Entity\Point */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    $user_inputs = $form_state->getUserInput();
    $form['state']['#type'] = 'hidden';
    if (!$user_inputs) {
      $form['state']['#value'] = $entity->getPoints();
    }
    else {
      $form['state']['#value'] = $user_inputs['state'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Point.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Point.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.point.canonical', ['point' => $entity->id()]);
  }

}
