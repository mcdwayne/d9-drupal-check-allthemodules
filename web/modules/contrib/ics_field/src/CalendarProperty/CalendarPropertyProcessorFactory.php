<?php

namespace Drupal\ics_field\CalendarProperty;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Utility\Token;
use Drupal\ics_field\Timezone\TimezoneProviderInterface;

/**
 * Class CalendarPropertyProcessorFactory
 *
 * @package Drupal\ics_field\CalendarProperty
 */
class CalendarPropertyProcessorFactory {

  /**
   * @var TimezoneProviderInterface
   */
  private $timezoneProvider;

  /**
   * @var Token
   */
  private $token;

  /**
   * CalendarPropertyProcessorFactory constructor.
   *
   * @param \Drupal\ics_field\Timezone\TimezoneProviderInterface $timezoneProvider
   * @param \Drupal\Core\Utility\Token                                      $token
   */
  public function __construct(TimezoneProviderInterface $timezoneProvider,
                              Token $token) {
    $this->timezoneProvider = $timezoneProvider;
    $this->token = $token;
  }

  /**
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *
   * @return \Drupal\ics_field\CalendarProperty\CalendarPropertyProcessor
   */
  public function create(FieldDefinitionInterface $fieldDefinition) {

    return new CalendarPropertyProcessor($this->token,
                                         $this->timezoneProvider,
                                         $fieldDefinition->getSetting('date_field_reference'),
                                         $fieldDefinition->getConfig($fieldDefinition->getTargetBundle())
                                                         ->uuid());

  }

}
