<?php

namespace Drupal\contact_scheduler;

use Drupal\contact\ContactFormInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class ContactSchedulerContactFormHandler {

  use StringTranslationTrait;
  use ContactSchedulerSettingsTrait;

  /**
   * Alters the contact form to add scheduler elements.
   *
   * @param $form
   *  The contact form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *  The form state.
   */
  public function alter(array &$form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();

    // Only alter the edit and add forms.
    if (!in_array($form_object->getOperation(), ['edit', 'add'], TRUE)) {
      return;
    }

    // Get the contact form object.
    $contact_form = $form_object->getEntity();

    // Add the contact scheduler form elements.
    $form['contact_options'] = [
      '#type' => 'vertical_tabs',
      '#weight' => (isset($form['actions']['#weight']) ? $form['actions']['#weight'] - 1 : 99),
    ];

    $form['contact_scheduler'] = [
      '#title' => $this->t('Schedule'),
      '#type' => 'details',
      '#description' => $this->t('Set the start and end date for this contact form. The form will be available within this date range.'),
      '#group' => 'contact_options',
    ];

    $form['contact_scheduler']['dates'] = [
      '#title' => $this->t('Dates'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $start_date = static::getThirdPartySetting($contact_form, 'start_date');
    $form['contact_scheduler']['dates']['start_date'] = [
      '#title' => $this->t('Start date'),
      '#type' => 'datetime',
      '#description' => $this->t('The date when this form will be available.'),
      '#default_value' => $start_date ? DrupalDateTime::createFromTimestamp($start_date) : NULL,
    ];

    $end_date = static::getThirdPartySetting($contact_form, 'end_date');
    $form['contact_scheduler']['dates']['end_date'] = [
      '#title' => $this->t('End date'),
      '#type' => 'datetime',
      '#description' => $this->t('The date when this form will <strong>not</strong> be available.'),
      '#default_value' => $end_date ? DrupalDateTime::createFromTimestamp($end_date) : NULL,
    ];

    $form['contact_scheduler']['messages'] = [
      '#title' => $this->t('Messages'),
      '#description' => $this->t('Messages to display when the form is not available. '),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['contact_scheduler']['messages']['start_message'] = [
      '#title' => $this->t('Message to display before start date'),
      '#type' => 'textarea',
      '#default_value' => static::getThirdPartySetting($contact_form, 'start_message'),
    ];

    $form['contact_scheduler']['messages']['end_message'] = [
      '#title' => $this->t('Message to display after end date'),
      '#type' => 'textarea',
      '#default_value' => static::getThirdPartySetting($contact_form, 'end_message'),
    ];

    $form['#validate'][] = static::class . '::validate';
    $form['#entity_builders'][] = static::class . '::buildEntity';
  }

  /**
   * Callback for the form validation handler.
   *
   * @param $form
   *  The form array.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *  The form state.
   */
  public static function validate(array &$form, FormStateInterface &$form_state) {
    $start_date = $form_state->getValue('start_date');
    $end_date = $form_state->getValue('end_date');

    // Validate the end date.
    if ($start_date && $end_date && $end_date < $start_date) {
      $form_state->setError($form['contact_scheduler']['end_date'], 'The end date is invalid. It should be after the start date.');
    }
  }

  /**
   * Callback for the entity builder.
   *
   * @param $entity_type
   *  The entity type.
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *  The contact form object.
   * @param $form
   *  The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *  The form state.
   */
  public static function buildEntity($entity_type, ContactFormInterface $contact_form, array &$form, FormStateInterface $form_state) {
    // Set the start date.
    $start_date = $form_state->getValue('start_date');
    $start_date = $start_date instanceof DrupalDateTime ? $start_date->getTimestamp() : NULL;
    static::setThirdPartySetting($contact_form, 'start_date', $start_date);

    // Set the end date.
    $end_date = $form_state->getValue('end_date');
    $end_date = $end_date instanceof DrupalDateTime ? $end_date->getTimestamp() : NULL;
    static::setThirdPartySetting($contact_form, 'end_date', $end_date);

    // Set the start and end messages.
    static::setThirdPartySetting($contact_form, 'start_message', $form_state->getValue('start_message'));
    static::setThirdPartySetting($contact_form, 'end_message', $form_state->getValue('end_message'));
  }
}
