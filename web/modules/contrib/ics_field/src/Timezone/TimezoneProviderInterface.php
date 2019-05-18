<?php

namespace Drupal\ics_field\Timezone;

interface TimezoneProviderInterface {

  /**
   * This returns a timezone in the format of
   *
   * @return string The timezone to use for the calendar
   */
  public function getTimezoneString();

}
