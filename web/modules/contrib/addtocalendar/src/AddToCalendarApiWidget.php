<?php

namespace Drupal\addtocalendar;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class AddToCalendarApiWidget.
 */
class AddToCalendarApiWidget {

  /**
   * Declare various setting variables for add to calendar api widget.
   *
   * @var string
   */
  protected $atcDisplayText;
  protected $atcTitle;
  protected $atcDescription;
  protected $atcLocation;
  protected $atcOrganizer;
  protected $atcOrganizerEmail;
  protected $atcDateStart;
  protected $atcDateEnd;
  protected $atcPrivacy;
  protected $timeZone;

  /**
   * Hold the various calendars usable in the widget.
   *
   * @var array
   */
  protected $atcDataCalendars = [];

  /**
   * Constructs a new AddToCalendarApiWidget object.
   */
  public function __construct() {

    $this->atcDisplayText = 'Add to calendar';
    $this->atcTitle = 'Some event title';
    $this->atcDescription = 'Some event description';
    $this->atcLocation = 'Some event location';
    // Fetching site name and site email id.
    $config = \Drupal::config('system.site');
    $site_name = $config->get('name');
    $site_mail = $config->get('mail');

    $this->atcOrganizer = $site_name;
    $this->atcOrganizerEmail = $site_mail;
    $this->atcDateStart = 'now';
    $this->atcDateEnd = 'now';
    $this->timeZone = drupal_get_user_timezone();

  }

  /**
   * Use this function to set values for the widget.
   */
  public function setWidgetValues($config_values = array()) {
    foreach ($config_values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * Use this function to get a particular value from this widget.
   */
  public function getWidgetValue($config_value) {
    return $this->$config_value;
  }

  /**
   * Constructs and returns a renderable array widget for add to calendar.
   */
  public function generateWidget() {

    // Start building the build array.
    $build['addtocalendar'] = [];
    $display_text = t(':text', array(':text' => $this->atcDisplayText));
    $build['addtocalendar'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $display_text->__toString(),
      '#attributes' => [
        'class' => 'addeventatc',
        'title' => 'Add to Calendar',
      ],
    ];
    $timeZone = $this->timeZone;

    // Assuming date and end_date is provide in UTC format.
    $date = DrupalDateTime::createFromTimestamp($this->atcDateStart, 'UTC');
    $end_date = DrupalDateTime::createFromTimestamp($this->atcDateEnd, 'UTC');
    $info = [
      'start' => $date->format('d-m-Y H:i', ['timezone' => $timeZone]),
      'end' => $end_date->format('d-m-Y H:i', ['timezone' => $timeZone]),
      'title' => $this->atcTitle,
      'description' => $this->atcDescription,
      'location' => $this->atcLocation,
      'organizer' => $this->atcOrganizer,
      'organizer_email' => $this->atcOrganizerEmail,
      'timezone' => $timeZone,
    ];

    foreach ($info as $key => $value) {
      $build['addtocalendar'][$key] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $value,
        '#attributes' => [
          'class' => $key,
        ],
      ];
    }

    $build['#attached']['library'][] = 'addtocalendar/base';
    return $build;
  }

}
