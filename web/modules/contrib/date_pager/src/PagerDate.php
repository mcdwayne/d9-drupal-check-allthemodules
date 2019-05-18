<?php

namespace Drupal\date_pager;

use Drupal\Core\Link;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Extends the DateTime PHP-Class.
 *
 * Stores and applies pager granularity in a date format and
 * implents the link generation with the given granularity.
 *
 * @author Kate Heinlein
 */
class PagerDate extends \DateTime {

  public $granularity;

  /**
   * Constructs the PagerDate.
   *
   * @param string $time
   *   Any date string.
   * @param string $granularity
   *   Date format, eg. 'Y-m-d'.
   */
  public function __construct($time, $granularity = NULL) {

    // Sometimes the given parameter doesn't match the granularity wanted.
    // We need to adjust the granularity to the given value.
    // Doesn't work with timestring like 'NOW' etc, but doesn't have to.
    if (is_null($granularity)) {
      $this->granularity = $this->findGranularity($time);
    }
    else {
      $this->granularity = $granularity;
    }

    $this->granularityId = $this->findGranularityId();

    // We have to expand the timestring, otherwise 2011 as a year
    // wouldn't pe properly recognized.
    // Also we don't want to use the current date
    // as defaults for the missing parts.

    $full_time_String = '2000-01-01T00:00:00';
    if (strlen($full_time_String) > strlen($time)) {
      $fill_up_timestring = substr($full_time_String, - (strlen($full_time_String) - strlen($time)));
      $time .= $fill_up_timestring;
    }

    parent::__construct($time);
  }

  /**
   * Find the longest working format string.
   *
   * Checks if any part of Y-m-dTH:i:s works.
   * So it doesn't work for TEXT date strings like NOW or year+1
   * (but also it's not supposed to).
   *
   * @param string $dateString
   *   Any date string.
   *
   * @return STRING
   *   The longest working partt of the DateTime format string.
   */
  private function findGranularity($dateString) {
    $datelength = ['YYYY', '-MM', '-DD', 'T00', ':00', ':00'];
    $dateparts = ['Y', '-m', '-d', '\TH', ':i', ':s'];
    // Check if what form at works, going longest to shortest.
    for ($i = 0; $i <= 6; $i++) {
      $format = implode('', array_slice($datelength, 0, $i + 1));
      if (strlen($format) == strlen($dateString)) {
        return implode('', array_slice($dateparts, 0, $i + 1));
      }
    }
    return FALSE;
  }

  private function findGranularityId() {
    $dateparts = ['Y', '-m', '-d', '\TH', ':i', ':s'];
    // Check if what form at works, going longest to shortest.
    for ($i = 5; $i >= 0; $i--) {
      if ($this->granularity == implode('', array_slice($dateparts, 0, $i + 1))) {
        return $i;
      }
    }
    return FALSE;
  }

  /**
   * Return date formatted with the granularity.
   *
   * @return string
   *   Formatted date
   */
  public function __toString() {
    return $this->format($this->granularity);
  }

  /**
   * Return unix seconds with granuarity applied.
   *
   * @parameter string $format
   *   If given, it applies a format other then the own granularity.
   *
   * @return int
   *   unix seconds
   */
  public function toTime($format = NULL) {
    if (is_null($format)) {
      $format = $this->granularity;
    }
    $formatted_date = date($format, $this->format('U'));
    return \DateTime::createFromFormat($format, $formatted_date)->format('U');
  }

  /**
   * Checks if a date is between two others.
   *
   * It's important to apply the given granularity here.
   *
   * @parameter PagerDate $startdate
   * @parameter PagerDate $enddate
   *
   * @return bool
   *   Returns TRUE if in between.
   */
  public function between(PagerDate $startdate, PagerDate $enddate) {
    return (($this->toTime() >= $startdate->toTime($this->granularity)) && ($this->toTime() <= $enddate->toTime($this->granularity)));
  }

  /**
   * Generates pager link object.
   *
   * @param string $route_name
   *   Route to link to.
   * @param PagerDate $active_date
   *   Currently active Pager Date for comparison.
   *
   * @return \Drupal\Core\Link
   *   Link Object
   */
  public function toLink($route_name, PagerDate $active_date) {

    $classes = [];

    $time = $this->toTime();

    // Link text and class only the last part of the format.
    $linkFormat = substr($this->granularity, -1, 1);

    $text = SafeMarkup::format(
            '<time datetime="@datetime">@linktext</time>', [
          '@datetime' => date($this->granularity, $time),
          '@linktext' => date($linkFormat, $time),
            ]
    );

    $classes[] = $linkFormat;

    // Link query parameter.
    $args['date'] = date($this->granularity, $time);

    if ($this->toTime() > time()) {
      $classes[] = 'future';
    }

    if ($this->toTime() == $active_date->toTime($this->granularity)) {
      $classes[] = 'active';
    }
    $options['query'] = $args;
    // Generate & return Link Object.
    return [
      'title' => $text,
      'attributes' => [
        'class' => implode(' ', $classes),
        'title' => date($linkFormat, $time)
      ],
      'url' => Link::createFromRoute($text, $route_name, [], $options)->getUrl(),
      '#theme' => 'link'
    ];
  }

}
