<?php

namespace Drupal\ics_field;

use Drupal\ics_field\Normalizer\UrlNormalizerInterface;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Component\Timezone;
use Html2Text\Html2Text;
use Symfony\Component\HttpFoundation\Request;

/**
 * Utility class for generating calendars.
 */
class ICalFactory {

  /**
   * The calendar array of properties.
   *
   * @var array
   */
  protected $calendarProperties;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The user's timezone.
   *
   * @var \DateTimeZone
   */
  protected $userDatetimezone;

  /**
   * @var \Drupal\ics_field\Normalizer\UrlNormalizerInterface
   */
  protected $urlNormalizer;

  /**
   * Constructs a new CalendarDownloadUtil.
   *
   * @param \Drupal\ics_field\Normalizer\UrlNormalizerInterface $normalizer
   *
   * @internal param \string[] $calendarProperties An array of calendar
   *           properties.*   An array of calendar properties.
   * @internal param \Symfony\Component\HttpFoundation\Request $request The
   *           request stack used to retrieve the current request.*   The
   *           request stack used to retrieve the current request.
   * @codeCoverageIgnore
   */
  public function __construct(UrlNormalizerInterface $normalizer) {

    $this->urlNormalizer = $normalizer;
  }

  /**
   * Returns a named property from the calendarProperties array.
   *
   * @param string $propertyName
   *   The key of a property in the calendarProperties array.
   *
   * @return string|array|null
   *   The value of that property or NULL if not found.
   */
  protected function getCalendarProperty($propertyName) {
    return isset($this->calendarProperties[$propertyName]) ?
      $this->calendarProperties[$propertyName] : NULL;
  }

  /**
   * Generates an .ics file as a string.
   *
   * @return string The generated ical file as a string.
   *
   * @throws \Drupal\ics_field\Exception\IcalTimezoneInvalidTimestampException
   * @throws \InvalidArgumentException
   * @throws \UnexpectedValueException
   */
  public function generate(array $calendarProperties,
                           Request $request,
                           $timeStampFormat = NULL) {

    $this->calendarProperties = $calendarProperties;
    $this->request = $request;
    $this->userDatetimezone = new \DateTimeZone($this->getCalendarProperty('timezone'));

    // The provided 'product_identifier' will be used for iCal's PRODID.
    $iCalendar = new Calendar($this->getCalendarProperty('product_identifier'));
    $iCalendarTimezone = new Timezone($this->getCalendarProperty('timezone'));

    $tg = new ICalTimezoneGenerator();
    // Overwrite the default value of timeStampFormat property.
    if ($timeStampFormat) {
      $tg->setTimestampFormat($timeStampFormat);
    }

    /** @var Timezone $trans */
    $trans = $tg->applyTimezoneTransitions($iCalendarTimezone,
                                           $this->getCalendarProperty('dates_list'));

    $iCalendar->setTimezone($trans);

    $iCalendar = $this->addEvents($this->getCalendarProperty('dates_list'),
                                  $iCalendar);

    return $iCalendar->render();
  }

  /**
   * Adds an event for each date in the provided datesList.
   *
   * @param string[] $datesList
   *   An array of date strings, i.e. 1970-01-01 01:00:00 Europe/Zurich.
   * @param Calendar $iCalendar
   *   The iCal object to which event components will be added.
   *
   * @return Calendar
   *   The modified calendar object.
   */
  private function addEvents(array $datesList, Calendar $iCalendar) {
    // Using html2text to convert markup into reasonable ASCII text.
    $html2Text = new Html2Text($this->getCalendarProperty('description'));
    $eventUrl = $this->getCalendarProperty('url') ?
      $this->urlNormalizer->normalize($this->getCalendarProperty('url'),
                                      $this->request->getScheme(),
                                      $this->request->getSchemeAndHttpHost()) :
      '';
    foreach ($datesList as $dateIdx => $date) {
      // We need this eventUniqId to be the same on
      // following versions of the generated file, in
      // order to be able to update existing events,
      // e.g. by reimporting the ics file into our calendar.
      $eventUniqId = md5($this->getCalendarProperty('uuid') . $dateIdx);
      $iCalendarEvent = new Event($eventUniqId);
      // Create a datetime object from the stored date value
      // using UTC as timezone.
      $datetime = new \DateTime($date, new \DateTimeZone('UTC'));
      // Set the datetime object using user's timezone.
      // This way the correct time offset will be applied.
      $datetime->setTimezone($this->userDatetimezone);
      $iCalendarEvent
        ->setDtStart($datetime)
        ->setSummary($this->getCalendarProperty('summary'))
        ->setDescription($html2Text->getText())
        ->setDescriptionHTML($this->getCalendarProperty('description'));
      if (!empty($eventUrl)) {
        $iCalendarEvent->setUrl($eventUrl);
      }
      $iCalendarEvent->setUseTimezone(TRUE);
      $iCalendar->addComponent($iCalendarEvent);
    }
    return $iCalendar;
  }

}
