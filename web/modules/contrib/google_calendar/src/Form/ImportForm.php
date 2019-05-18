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
class ImportForm extends FormBase {

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
      $container->get('google_calendar.import'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_calendar_import_form';
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

  public static function handleBatchProcess($calendar, $total, &$context){
    $name = $calendar->label();
    $context['message'] = "Imported Calendar: $name";
    \Drupal::service('google_calendar.import')->import($calendar);
  }

  public static function batchProcessCallback($success, $results, $operations){
    if ($success) {
      $message = t("Finished importing calendar events");
    }
    else {
      $message = t('Finished with an error.');
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
        '\Drupal\google_calendar\Form\ImportForm::handleBatchProcess', [$calendar, $total]
      ];
    }

    $batch = [
      'title' => t('Importing Calendars'),
      'operations' => $operations,
      'finished' => '\Drupal\google_calendar\Form\ImportForm::batchProcessCallback',
    ];

    return batch_set($batch);
  }
}
