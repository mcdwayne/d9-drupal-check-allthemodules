<?php

namespace Drupal\strava_athletes\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Athlete edit forms.
 *
 * @ingroup strava
 */
class AthleteForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\strava_athletes\Entity\Athlete */
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
        $this->messenger()
          ->addMessage(t('Created the %label athlete.', ['%label' => $entity->label()]));
        break;

      default:
        $this->messenger()
          ->addMessage(t('Saved the %label athlete.', ['%label' => $entity->label()]));
    }
    $form_state->setRedirect('entity.athlete.canonical', ['athlete' => $entity->id()]);
  }

}
