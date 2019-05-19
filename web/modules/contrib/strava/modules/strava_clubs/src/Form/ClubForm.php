<?php

namespace Drupal\strava_clubs\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Club edit forms.
 *
 * @ingroup strava
 */
class ClubForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\strava_clubs\Entity\Club */
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
          ->addMessage(t('Created the %label club.', ['%label' => $entity->label()]));
        break;

      default:
        $this->messenger()
          ->addMessage(t('Saved the %label club.', ['%label' => $entity->label()]));
    }
    $form_state->setRedirect('entity.club.canonical', ['club' => $entity->id()]);
  }

}
