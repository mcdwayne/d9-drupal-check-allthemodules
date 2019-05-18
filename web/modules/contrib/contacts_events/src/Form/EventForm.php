<?php

namespace Drupal\contacts_events\Form;

use Drupal\contacts_events\Entity\Event;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Form controller for Event edit forms.
 *
 * @ingroup contacts_events
 */
class EventForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\contacts_events\Entity\Event */
    $form = parent::buildForm($form, $form_state);

    $form['booking_status']['widget']['#process'] = \Drupal::service('element_info')->getInfoProperty($form['booking_status']['widget']['#type'], '#process', []);
    $form['booking_status']['widget']['#process'][] = '::processBookingStatus';

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
        drupal_set_message($this->t('Created the %label Event.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Event.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.contacts_event.canonical', ['contacts_event' => $entity->id()]);
  }

  /**
   * Process callback for the booking status field.
   *
   * @param array $element
   *   The booking status field container.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The update element.
   */
  public function processBookingStatus(array &$element, FormStateInterface $form_state) {
    $element[Event::STATUS_OPEN]['#description'] = new TranslatableMarkup('Allow users with permission to book for this event.');
    $element[Event::STATUS_CLOSED]['#description'] = new TranslatableMarkup('Allow users who can manage bookings for this event.');
    $element[Event::STATUS_DISABLED]['#description'] = new TranslatableMarkup('Disable all booking features for this event.');
    return $element;
  }

}
