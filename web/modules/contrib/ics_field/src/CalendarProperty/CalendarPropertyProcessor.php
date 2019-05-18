<?php

namespace Drupal\ics_field\CalendarProperty;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Utility\Token;
use Drupal\ics_field\Exception\CalendarDownloadInvalidPropertiesException;
use Drupal\ics_field\Timezone\TimezoneProviderInterface;

/**
 * Class CalendarPropertyProcessor
 *
 * @package Drupal\ics_field
 */
class CalendarPropertyProcessor {

  use DependencySerializationTrait;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * @var string
   */
  protected $dateFieldReference;

  /**
   * @var string
   */
  protected $dateFieldUuid;

  /**
   * @var \Drupal\ics_field\Timezone\TimezoneProviderInterface
   */
  protected $timezoneProvider;

  /**
   * @var array
   */
  protected $essentialProperties = [
    'timezone',
    'product_identifier',
    'summary',
    'uuid',
  ];

  /**
   * @return array
   */
  public function getEssentialProperties() {
    return $this->essentialProperties;
  }

  /**
   * @param array $essentialProperties
   */
  public function setEssentialProperties(array $essentialProperties) {
    $this->essentialProperties = $essentialProperties;
  }

  /**
   * CalendarPropertyProcessor constructor.
   *
   * @param \Drupal\Core\Utility\Token $tokenService
   * @param TimezoneProviderInterface  $timezoneProvider
   * @param string                     $dateFieldReference
   * @param string                     $dateFieldUuid
   */
  public function __construct(Token $tokenService,
                              TimezoneProviderInterface $timezoneProvider,
                              $dateFieldReference,
                              $dateFieldUuid) {
    $this->tokenService = $tokenService;
    $this->timezoneProvider = $timezoneProvider;
    $this->dateFieldReference = $dateFieldReference;
    $this->dateFieldUuid = $dateFieldUuid;
  }

  /**
   * @param array                                      $tokens
   * @param \Drupal\Core\Entity\ContentEntityInterface $contentEntity
   * @param string                                     $host
   *
   * @return array
   */
  public function getCalendarProperties(array $tokens,
                                        ContentEntityInterface $contentEntity,
                                        $host = 'http') {
    $calendarProperties = [];
    // Set default timezone
    // Note: Use the following if we want to use the site's timezone.
    // $calendarProperties['timezone'] = \Drupal::config('system.date')->get('timezone.default');
    $calendarProperties['timezone'] = $this->timezoneProvider->getTimezoneString();
    // Use the hostname to set the 'product_identifier' value.
    $calendarProperties['product_identifier'] = $host;
    //TODO - should uuid contain a separator ie :
    $calendarProperties['uuid'] = $contentEntity->uuid() . $this->dateFieldUuid;

    // Uses token replacement to interpolate tokens in the field's fields that support them.
    $data = [$contentEntity->getEntityTypeId() => $contentEntity];

    $replaced = [];
    foreach ($tokens as $id => $token) {
      $replaced[$id] = $this->tokenService->replace($token, $data);
    }

    $calendarProperties = array_merge($calendarProperties, $replaced);

    //Validate before the date list, as we don't actually care if that field is empty as it should
    //be possible to save a node without creating a ics file
    $this->validate($calendarProperties);

    $calendarProperties['dates_list'] = $this->processDateList($contentEntity);

    return $calendarProperties;
  }

  /**
   * @return array
   * @throws \Drupal\ics_field\Exception\CalendarDownloadInvalidPropertiesException
   * @throws \InvalidArgumentException
   */
  private function processDateList(ContentEntityInterface $contentEntity) {

    $calendarProperties = [];
    if (!empty($this->dateFieldReference)) {
      foreach ($contentEntity->get($this->dateFieldReference)
                             ->getValue() as $dateVal) {
        // TODO: Remove the check for DrupalDateTime,should be a string-ed date.
        if ($dateVal['value'] instanceof DrupalDateTime) {
          $calendarProperties[] = $dateVal['value']->render();
        }
        // Add only in 'value' is not empty.
        elseif ($dateVal['value']) {
          $calendarProperties[] = $dateVal['value'];
        }
      }
    }
    return $calendarProperties;
  }

  /**
   * Check that the calendar properties are valid.
   *
   * @param string[] $calendarProperties
   *   An array of calendar properties.
   *
   * @return bool
   *
   * @throws \Drupal\ics_field\Exception\CalendarDownloadInvalidPropertiesException
   *   True if the check was successful, otherwise false.
   *
   * @throws CalendarDownloadInvalidPropertiesException
   *   An invalid (empty) calendar property exception.
   */
  protected function validate(array $calendarProperties) {
    // N.B.: There could be more complex validation taking place here.
    foreach ($this->essentialProperties as $essentialProperty) {
      if (!array_key_exists($essentialProperty, $calendarProperties) ||
          empty($calendarProperties[$essentialProperty])
      ) {
        //We do not string translate exceptions, as its possible in an exception that the translation service will also throw
        throw new CalendarDownloadInvalidPropertiesException('Missing needed property ' .
                                                             $essentialProperty
        );
      }
    }
    return TRUE;
  }

}
