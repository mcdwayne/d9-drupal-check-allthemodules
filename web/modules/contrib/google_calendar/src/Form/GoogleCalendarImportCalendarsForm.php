<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for Google Calendar import forms.
 *
 * @ingroup google_calendar
 */
class GoogleCalendarImportCalendarsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'google_calendar_import_calendars_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /** @var GoogleClientFactory $client_factory */
    $client_factory = \Drupal::service('google_calendar.google_client.factory');
    /** @var \Google_Client $client */
    $client = $client_factory->get();

    $service = new \Google_Service_Calendar($client);

    $index = [];
    $entities = \Drupal::entityTypeManager()
      ->getStorage('google_calendar')
      ->loadByProperties(['status' => 1]);
    foreach ($entities as $entity) {
      $index[$entity->getGoogleCalendarId()] = $entity;
    }
    $imported = [];
    $toimport = [];
    $orphaned = [];

    $list = $service->calendarList->listCalendarList();

    $items = $list->getItems();

    /** @var \Google_Service_Calendar_CalendarListEntry $calendar */
    foreach ($items as $calendar) {
      if (array_key_exists($calendar->getId(), $index)) {
        $imported[] = $calendar->getId();
      }
      else {
        $toimport[] = $calendar->getId();
      }
    }

    // Check to see if any current entities are no longer visible in
    // the calendar api (e.g. they have been unshared).
    foreach ($entities as $entity) {
      $eid = $entity->getGoogleCalendarId();
      $found = FALSE;
      foreach ($items as $calendar) {
        if ($eid === $calendar->getId()) {
          $found = TRUE;
        }
      }
      if (!$found) {
        $orphaned[] = $eid;
      }
    }

    $rows = [];
    foreach ($items as $calendar) {
      $id = $calendar->getId();
      /* Build Status */
      if (in_array($id, $imported, TRUE)) {
        $status = $this->t('Imported as @name', ['@name' => $index[$id]->link()]);
      }
      elseif (in_array($id, $toimport, TRUE)) {
        $status = $this->t('Not Imported');
      }
      elseif (in_array($id, $orphaned, TRUE)) {
        $status = $this->t('Not Longer Available');
      }
      else {
        $status = $this->t('Unknown');
      }

      /* Build links */
      $links = [];
      if (in_array($id, $toimport, TRUE)) {
        $links['import'] = [
          'title' => $this->t('Import Calendar'),
          'url' => Url::fromRoute('google_calendar.import_calendar',
                                  ['calendar_id' => $id]),
        ];
      }
      elseif (in_array($id, $imported, TRUE)) {
        $links['sync'] = [
          'title' => $this->t('Sync Events'),
          'url' => Url::fromRoute('google_calendar.import_events',
                                  ['google_calendar' => $index[$id]->id()]),
        ];
      }

      // Build the table row.
      $row = [];

      /* Cell: Name */
      $row['name']['data'] = [
        '#type' => 'markup',
        '#markup' => $calendar->getSummary()
      ];

      /* Cell: Description */
      $row['desc']['data'] = [
        '#type' => 'markup',
        '#markup' => mb_strimwidth($calendar->getDescription(),0,40, '...')
      ];

      /* Cell: Status */
      $row['status']['data'] = [
        '#type' => 'markup',
        '#markup' => $status
      ];

      /* Cell: Operations */
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
      $rows[] = $row;
    }
    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Status'),
        $this->t('Operations'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No calendars are available.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $form_state->setRedirect('entity.google_calendar.collection');
  }
}
