<?php

namespace Drupal\healthcheck\Event;

final class HealthcheckEvents {

  const CHECK_RUN = 'healthcheck.run';

  const CHECK_CRITICAL = 'healthcheck.critical';

  const CHECK_CRON = 'healthcheck.cron';

}
