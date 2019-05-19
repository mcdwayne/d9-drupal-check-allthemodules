<?php

namespace Drupal\views_rss_events\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RssFields;

/**
 * Renders an Event RSS item based on fields.
 *
 * @ViewsRow(
 *   id = "rss_event_fields",
 *   title = @Translation("Event fields"),
 *   help = @Translation("Display fields as Event RSS items."),
 *   theme = "views_view_row_rss",
 *   display_types = {"feed"}
 * )
 */
class RssEventFields extends RssFields {

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['event_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Event type field'),
      '#description' => $this->t('The field that is going to be used as the RSS item event type for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['event_type'],
      '#required' => FALSE,
    ];
    $form['event_organizer'] = [
      '#type' => 'select',
      '#title' => $this->t('Event organizer field'),
      '#description' => $this->t('The field that is going to be used as the RSS item event organizer for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['event_organizer'],
      '#required' => FALSE,
    ];
    $form['event_location'] = [
      '#type' => 'select',
      '#title' => $this->t('Event location field'),
      '#description' => $this->t('The field that is going to be used as the RSS item event location for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['event_location'],
      '#required' => FALSE,
    ];
    $form['event_start_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event start date field'),
      '#description' => $this->t('The field that is going to be used as the RSS item event start date for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['event_start_date'],
      '#required' => FALSE,
    ];
    $form['event_end_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event end date field'),
      '#description' => $this->t('The field that is going to be used as the RSS item event end date for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['event_end_date'],
      '#required' => FALSE,
    ];

    // Push inherited GUID field options down.
    $form['guid_field_options']['#weight'] = 99;
  }

  public function validate() {
    return parent::validate();
  }

  public function render($row) {
    $build = parent::render($row);

    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }

    // Declare the event module namespace.
    $this->view->style_plugin->namespaces += ['xmlns:ev' => 'http://purl.org/rss/1.0/modules/event/'];

    // Render event fields.
    if ($type = $this->getField($row_index, $this->options['event_type'])) {
      $build['#row']->elements[] = ['key' => 'ev:type', 'value' => $type];
    }
    if ($organizer = $this->getField($row_index, $this->options['event_organizer'])) {
      $build['#row']->elements[] = ['key' => 'ev:organizer', 'value' => $organizer];
    }
    if ($location = $this->getField($row_index, $this->options['event_location'])) {
      $build['#row']->elements[] = ['key' => 'ev:location', 'value' => $location];
    }
    if ($start_date = $this->getField($row_index, $this->options['event_start_date'])) {
      $build['#row']->elements[] = ['key' => 'ev:startdate', 'value' => $start_date];
    }
    if ($end_date = $this->getField($row_index, $this->options['event_end_date'])) {
      $build['#row']->elements[] = ['key' => 'ev:enddate', 'value' => $end_date];
    }

    $row_index++;

    return $build;
  }

}
