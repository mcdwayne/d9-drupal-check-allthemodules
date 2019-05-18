<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_calendar\Entity\GoogleCalendar;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\google_calendar\GoogleCalendarImport;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ImportCalendarForm.
 *
 * @package Drupal\google_calendar\Form
 */
class GoogleCalendarImportEventsForm extends FormBase {

  /**
   * Drupal\google_calendar\GoogleCalendarImport definition.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImport
   */
  protected $calendarService;

  /**
   * EntityTypeManager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  public function __construct(GoogleCalendarImport $google_calendar_service, EntityTypeManagerInterface $entityTypeManager) {
    $this->calendarService = $google_calendar_service;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_calendar.import_events'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_calendar_import_events_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Import Events'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Batch API Callback function to import events for a calendar.
   */
  public static function handleBatchProcess($calendar, $total, &$context) {
    $name = $calendar->label();
    $context['message'] = "Imported Calendar: $name";
    \Drupal::service('google_calendar.import_events')->import($calendar);
  }

  public static function batchProcessCallback($success, $results, $operations) {
    if ($success) {
      $message = t("Successfully imported calendar events");
    }
    else {
      $message = t('Failed to import all calendar events.');
    }
    drupal_set_message($message);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $query = $this->entityTypeManager
      ->getStorage('google_calendar')
      ->getQuery()
      ->condition('status', 1);

    $calendarIds = $query->execute();

    $foundCalendars = GoogleCalendar::loadMultiple($calendarIds);
    $operations = [];
    $total = count($foundCalendars);
    foreach ($foundCalendars as $calendar) {
      $operations[] = [
        '\Drupal\google_calendar\Form\GoogleCalendarImportEventsForm::handleBatchProcess', [$calendar, $total]
      ];
    }

    $batch = array(
      'title' => t('Importing Calendars'),
      'operations' => $operations,
      'finished' => '\Drupal\google_calendar\Form\GoogleCalendarImportEventsForm::batchProcessCallback',
    );

    return batch_set($batch);
  }
}
