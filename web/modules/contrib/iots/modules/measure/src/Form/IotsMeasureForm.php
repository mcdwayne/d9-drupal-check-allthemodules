<?php

namespace Drupal\iots_measure\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Iots Measure edit forms.
 *
 * @ingroup iots_measure
 */
class IotsMeasureForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\iots_measure\Entity\IotsMeasure */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Iots Measure.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Iots Measure.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.iots_measure.canonical', ['iots_measure' => $entity->id()]);
  }

}
