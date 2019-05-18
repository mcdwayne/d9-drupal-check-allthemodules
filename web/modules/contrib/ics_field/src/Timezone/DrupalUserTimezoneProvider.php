<?php

namespace Drupal\ics_field\Timezone;

class DrupalUserTimezoneProvider implements TimezoneProviderInterface {

  /**
   * Effectively duplicates drupal_get_user_timezone()
   *
   * This sucks as it uses a lot of hidden dependencies,
   * but altering it will significantly change the achitecture of the module
   * so we will live with it for now. This is definitely something that could
   * be improved though.
   *
   * @inheritDoc
   */
  public function getTimezoneString() {
    $user = \Drupal::currentUser();
    $config = \Drupal::config('system.date');

    if ($user && $config->get('timezone.user.configurable') &&
        $user->isAuthenticated() && $user->getTimeZone()
    ) {
      return $user->getTimeZone();
    } else {
      // Ignore PHP strict notice if time zone has not yet been set in the php.ini
      // configuration.
      $config_data_default_timezone = $config->get('timezone.default');
      return !empty($config_data_default_timezone) ?
        $config_data_default_timezone : @date_default_timezone_get();
    }
  }

}
