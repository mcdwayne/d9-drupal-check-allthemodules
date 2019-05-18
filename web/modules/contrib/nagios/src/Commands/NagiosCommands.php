<?php

namespace Drupal\nagios\Commands;

use Drupal\nagios\Controller\StatuspageController;
use Drush\Commands\DrushCommands;

/**
 * Drush commandfile for Nagios
 */
class NagiosCommands extends DrushCommands {

  /**
   * Allows to query Drupal's health. Useful for NRPE.
   *
   * @param string $check
   *   Optionally specify which check to run, e.g. cron
   *
   * @command nagios
   * @return int
   *   Defaults:
   *   NAGIOS_STATUS_OK: 0
   *   NAGIOS_STATUS_WARNING: 1
   *   NAGIOS_STATUS_CRITICAL: 2
   *   NAGIOS_STATUS_UNKNOWN: 3
   */
  public function nagios($check = '') {
    if ($check) {
      $moduleHandler = \Drupal::moduleHandler();
      if (array_key_exists($check, nagios_functions())) {
        // A specific module has been requested.
        $func = 'nagios_check_' . $check;
        $result = $func();
        $nagios_data['nagios'][$result['key']] = $result['data'];
      }
      elseif ($moduleHandler->moduleExists($check)) {
        $result = $moduleHandler->invoke($check, 'nagios');
        $nagios_data[$check] = $result;
      }
      else {
        $this->logger()->error($check . ' is not a valid nagios check.');
        $this->displayValidChecks();
        return 1;
      }
    }
    else {
      $nagios_data = nagios_invoke_all('nagios');
    }

    list($output, $severity) = (new StatuspageController)->getStringFromNagiosData($nagios_data);
    echo trim($output) . "\n";
    return $severity;
  }

  private function displayValidChecks() {
    $valid_functions = array_keys(nagios_functions());
    $moduleHandler = \Drupal::moduleHandler();
    $module_names = $moduleHandler->getImplementations('nagios');
    $valid_checks = array_merge($valid_functions, $module_names);
    $text = join(', ', $valid_checks);
    echo "Valid checks are $text.\n";
    echo "
      To implement your own check within a Drupal module, please read 
      the section 'API' section within README.txt.\n";
  }
}
