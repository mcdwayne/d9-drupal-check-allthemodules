<?php

namespace Drupal\calendar_link\Twig;

use Drupal\calendar_link\CalendarLinkException;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Spatie\CalendarLinks\Exceptions\InvalidLink;
use Spatie\CalendarLinks\Link;

/**
 * Class CalendarLinkTwigExtension.
 *
 * @package Drupal\calendar_link\Twig
 */
class CalendarLinkTwigExtension extends \Twig_Extension {
  use StringTranslationTrait;

  /**
   * Available link types (generators).
   *
   * @var array
   *
   * @see \Spatie\CalendarLinks\Link
   */
  protected static $types = [
    'google' => 'Google',
    'ics' => 'iCal',
    'yahoo' => 'Yahoo!',
    'webOutlook' => 'Outlook.com',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('calendar_link', [$this, 'calendarLink']),
      new \Twig_SimpleFunction('calendar_links', [$this, 'calendarLinks']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'calendar_link';
  }

  /**
   * Create a calendar link.
   *
   * @param string $type
   *   Generator key to use for link building.
   * @param string $title
   *   Calendar entry title.
   * @param \Drupal\Core\Datetime\DrupalDateTime|\DateTime $from
   *   Calendar entry start date and time.
   * @param \Drupal\Core\Datetime\DrupalDateTime|\DateTime $to
   *   Calendar entry end date and time.
   * @param bool $all_day
   *   Indicator for an "all day" calendar entry.
   * @param string $description
   *   Calendar entry description.
   * @param string $address
   *   Calendar entry address.
   *
   * @return string
   *   URL for the specific calendar type.
   */
  public function calendarLink($type, $title, $from, $to, $all_day = FALSE, $description = '', $address = '') {
    if (!isset(self::$types[$type])) {
      throw new CalendarLinkException($this->t('Invalid calendar link type.'));
    }

    try {
      if ($from instanceof DrupalDateTime) {
        $from = $from->getPhpDateTime();
      }
      if ($to instanceof DrupalDateTime) {
        $to = $to->getPhpDateTime();
      }

      $link = Link::create($title, $from, $to, $all_day);
    }
    catch (InvalidLink $e) {
      throw new CalendarLinkException($this->t('Invalid calendar link data.'));
    }

    if ($description) {
      $link->description($description);
    }

    if ($address) {
      $link->address($address);
    }

    return $link->{$type}();
  }

  /**
   * Create links for all calendar types.
   *
   * @param string $title
   *   Calendar entry title.
   * @param \Drupal\Core\Datetime\DrupalDateTime|\DateTime $from
   *   Calendar entry start date and time.
   * @param \Drupal\Core\Datetime\DrupalDateTime|\DateTime $to
   *   Calendar entry end date and time.
   * @param bool $all_day
   *   Indicator for an "all day" calendar entry.
   * @param string $description
   *   Calendar entry description.
   * @param string $address
   *   Calendar entry address.
   *
   * @return array
   *   - type_key: Machine key for the calendar type.
   *   - type_name: Human-readable name for the calendar type.
   *   - url: URL for the specific calendar type.
   */
  public function calendarLinks($title, $from, $to, $all_day = FALSE, $description = '', $address = '') {
    $links = [];

    foreach (self::$types as $type => $name) {
      $links[$type] = [
        'type_key' => $type,
        'type_name' => $name,
        'url' => $this->calendarLink($type, $title, $from, $to, $all_day, $description, $address),
      ];
    }

    return $links;
  }

}
