<?php

namespace Drupal\strava_activities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Activity edit forms.
 *
 * @ingroup strava
 */
class ActivityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\strava_activities\Entity\Activity */
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
          ->addMessage(t('Created the <em>%label</em> activity.', ['%label' => $entity->label()]));
        break;

      default:
        $this->messenger()
          ->addMessage(t('Saved the <em>%label</em> activity.', ['%label' => $entity->label()]));
    }
    $form_state->setRedirect('entity.activity.canonical', ['activity' => $entity->id()]);
  }

}
