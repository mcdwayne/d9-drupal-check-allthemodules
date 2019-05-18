<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Google Calendar Event edit forms.
 *
 * @ingroup google_calendar
 */
class GoogleCalendarEventForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\google_calendar\Entity\GoogleCalendarEvent */
    $form = parent::buildForm($form, $form_state);

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
        drupal_set_message($this->t('Created the %label Google Calendar Event.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Google Calendar Event.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.google_calendar_event.canonical', ['google_calendar_event' => $entity->id()]);
  }

}
