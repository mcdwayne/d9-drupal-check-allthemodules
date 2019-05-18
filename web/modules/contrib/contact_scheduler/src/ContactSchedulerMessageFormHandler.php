<?php

namespace Drupal\contact_scheduler;

use DateTimeZone;
use Drupal\contact\ContactFormInterface;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ContactSchedulerMessageFormHandler {

  use ContactSchedulerSettingsTrait;

  /**
   * The form element key for the schedule message.
   */
  const SCHEDULE_MESSAGE_FORM_KEY = 'contact_scheduler_message';

  /**
   * Alters the contact message form to add scheduler elements.
   *
   * @param $form
   *  The contact form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *  The form state.
   */
  public function alter(array &$form, FormStateInterface $form_state) {
    $contact_message = $form_state->getFormObject()->getEntity();
    $contact_form = ContactForm::load($contact_message->bundle());

    // Display the start message.
    if ($this->shouldDisplayStartMessage($contact_form)) {
      $message = static::getThirdPartySetting($contact_form, 'start_message');
      $this->displayMessageInForm($message, $form);
      return;
    }

    // Display the end message.
    if ($this->shouldDisplayEndMessage($contact_form)) {
      $message = static::getThirdPartySetting($contact_form, 'end_message');
      $this->displayMessageInForm($message, $form);
      return;
    }
  }

  /**
   * Checks if we should display the start message.
   *
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *  The contact form entity.
   *
   * @return bool
   *  TRUE is we should display the start message.
   */
  public function shouldDisplayStartMessage(ContactFormInterface $contact_form) {
    // Get the start date for the form.
    $start_date = static::getThirdPartySetting($contact_form, 'start_date');

    // Do not display if no start date is set.
    if (!$start_date) {
      return FALSE;
    }

    // Get the start time in the user timezone.
    $timezone = $this->getUserTimezone();
    $start_date = DrupalDateTime::createFromTimestamp($start_date, $timezone);

    // Calculate the difference between the two dates.
    $diff = $start_date->diff($this->getCurrentDateTime($timezone));
    return ($diff->invert === 1);
  }

  /**
   * Checks if we should display the end message.
   *
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *  The contact form entity.
   *
   * @return bool
   *  TRUE is we should display the end message.
   */
  public function shouldDisplayEndMessage(ContactFormInterface $contact_form) {
    // Get the end date for the form.
    $end_date = static::getThirdPartySetting($contact_form, 'end_date');

    // Do not display if no end date is set.
    if (!$end_date) {
      return FALSE;
    }

    // Get the end date in the user timezone.
    $timezone = $this->getUserTimezone();
    $end_date = DrupalDateTime::createFromTimestamp($end_date, $timezone);

    // Calculate the difference between the two dates.
    $diff = $end_date->diff($this->getCurrentDateTime($timezone));
    return ($diff->invert === 0);
  }

  /**
   * Display a message in a form.
   *
   * @param string $message
   *  The message.
   * @param array $form
   *  The form array.
   */
  public function displayMessageInForm($message, array &$form) {
    // Hide all form elements.
    $this->hideFormElements($form);

    // Display the start message.
    $form[static::SCHEDULE_MESSAGE_FORM_KEY] = [
      '#theme' => 'contact_scheduler_message',
      '#message' => $message,
    ];
  }

  /**
   * Returns the user timezone.
   *
   * @return \DateTimeZone
   */
  protected function getUserTimezone() {
    return timezone_open(drupal_get_user_timezone());
  }

  /**
   * Returns the current time in a timezone.
   *
   * @param DateTimeZone $timezone
   *   The timezone.
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  protected function getCurrentDateTime(DateTimeZone $timezone) {
    return new DrupalDateTime('now', $timezone);
  }

  /**
   * Wrapper to hide all form elements.
   *
   * @param $form
   *  The form array.
   */
  protected function hideFormElements(&$form) {
    $form['#after_build'][] = array($this, 'hideVisibleFormChildren');
  }

  /**
   * Hides all visible elements for a form.
   *
   * @param $form
   *  The form array.
   *
   * @return array
   *  The form array.
   */
  public static function hideVisibleFormChildren($form) {
    // Find all visible children for form and hide them except for the schedule message.
    foreach (Element::getVisibleChildren($form) as $key) {
      $form[$key]['#access'] = $key === static::SCHEDULE_MESSAGE_FORM_KEY;
    }
    return $form;
  }
}
