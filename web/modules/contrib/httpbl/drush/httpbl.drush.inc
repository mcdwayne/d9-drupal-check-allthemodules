<?php

/**
 * @file
 * Drush integration for the httpbl module.
 */

use Drupal\Component\Uuid\Php;
use Drupal\httpbl\Entity\Host;
use Drupal\httpbl\HostQuery;

/**
 * Implements hook_drush_command().
 */
function httpbl_drush_command() {
  $items['httpbl-sos'] = array(
    'description' => 'Unbans and white-lists an IP address, with an option to stop http:BL checking/blocking of all page requests. Use in emergencies if an admin locks out localhost while testing httpbl module. Easily used with only the options to control httpbl checking.',
    'arguments' => array(
      'ip' => 'IP address to unban (e.g. "127.0.0.1" for localhost).  Localhost is the default.',
    ),
    'options' => array(
      'stop' => 'Stops http:BL checking/blocking of all page requests.',
      'start' => 'Start http:BL checking/blocking of all page requests (no harm done if it\'s already running).',
      'comment' => 'Start http:BL checking/blocking on comment submissions only.'
   ),
    'examples' => array(
      'httpbl-sos (or drush sos)'  => 'Unban and white-list localhost by default.',
      'httpbl-sos --stop'     => 'Unban and white-list localhost by default, and stop checking/blocking of all page requests.',
      'httpbl-sos 127.0.0.1'  => 'Unban and white-list (any) specified ip address.',
      'httpbl-sos 127.0.0.1 --stop'   => 'Same as above and stop checking/blocking of all page requests.',
      'httpbl-sos 127.0.0.1 --start'  => 'Unban and white-list (any) specified ip address and start checking/blocking of all page requests.',
      'drush sos --comment'  => 'Start checking on comment submissions only, and unban/white-list localhost if needed.',
   ),
    'required-arguments' => 0,
    'aliases' => ['sos'],
  );

  $items['httpbl-mho'] = array(
    'description' => 'Quickly make up to 255 evaluated hosts of a certain status and expire time. (Hint: use this to test httpbl cron)',
    'arguments' => array(
      'max' => 'The number of hosts to be generated. Default is 255.',
      'status' => 'The status (0=safe, 1=blackisted, 2=grey-listed). Default is 0.',
      'offset' => 'The time from now the host should expire. Default is 300.',
      'pattern' => 'The pattern used for IP addresses.  Default is "127.0.1.".',
    ),
    'examples' => array(
      'httpbl-mho (or drush mho)'  => 'Use defaults and create 255 safe hosts that expire in 5 minutes.',
      'drush mho 50 2 60'     => 'Make 50 grey-listed hosts that expire in 1 minute.',
      'drush mho 25 1 60 129.0.1.'     => 'Make 25 blacklisted hosts that expire in 1 minute, starting with 129.0.1.1.',
   ),
    'required-arguments' => 0,
    'aliases' => ['mho'],
  );

  $items['httpbl-mbb'] = array(
    'description' => 'Quickly make up to 255 blacklisted and banned hosts of a certain expire time (Hint: use this to test httpbl cron)',
    'arguments' => array(
      'max' => 'The number of hosts to be generated. Default is 255.',
      'offset' => 'The time from now the host should expire. Default is 300.',
      'pattern' => 'The pattern used for IP addresses.  Default is "127.1.8.".',
    ),
    'examples' => array(
      'httpbl-mbb (or drush mbb)'  => 'Use defaults and create 255 blacklisted and banned hosts that expire in 5 minutes.',
      'drush mbb 50 60'     => 'Make 50 blacklisted and banned hosts that expire in 1 minute.',
      'drush mbb 25 60 129.0.1.'     => 'Make 25 blacklisted and banned hosts that expire in 1 minute, starting with 129.0.1.1.',
   ),
    'required-arguments' => 0,
    'aliases' => ['mbb'],
  );

  $items['httpbl-sol'] = array(
    'description' => 'Remove localhost or another testing ip from hosts.',
    'arguments' => array(
      'ip' => 'IP address to remove (e.g. "127.0.0.1" for localhost).  Localhost is the default.',
    ),
    'examples' => array(
      'httpbl-sol (or drush sol)'  => 'Remove localhost from host.',
      'drush sol 127.0.0.1'     => 'Remove specific ip from host.',
   ),
    'required-arguments' => 0,
    'aliases' => ['sol'],
  );

  return $items;
}

/**
 * Command handler. Unban IP address and stop/stop checking/blocking of all page
 * requests or on comment submissions only.
 */
function drush_httpbl_sos($ip = '127.0.0.1') {
  // Get Ban manager service and unBan if banned.
  $banManager = \Drupal::service('ban.ip_manager');
  
  if ($banManager->isBanned($ip)) {
    $banManager->unbanIp($ip);
    $logMessage = t('Drush SOS unbanned @ip.', ['@ip' => $ip]);
    drush_log(dt($logMessage), 'ok');
    \Drupal::logger('httpbl')->notice($logMessage);
  }
  else {
    drush_log(dt('@ip was not banned.', ['@ip' => $ip]), 'ok');
  }

  // Get Evaluator service and whitelist this IP.
  $httpblEvaluator = \Drupal::service('httpbl.evaluator');
  $status = $httpblEvaluator->getIpLocalStatus($ip);
  
  if ($status != 0) {
    $httpblEvaluator->drushWhitelist($ip);
    $logMessage = t('Drush SOS whitelisted @ip.', ['@ip' => $ip]);
    drush_log(dt($logMessage), 'ok');
    \Drupal::logger('httpbl')->notice($logMessage);
  }
  else {
    drush_log(dt('@ip was already white-listed or was not found.', ['@ip' => $ip]), 'ok');
  }
 
  // Stop option:
  if (drush_get_option('stop', FALSE)) {
    $command = drush_state_set($key = 'httpbl.check', $value = 0);
    $logMessage = t('Drush SOS stopped Httpbl page checking/blocking.');
    \Drupal::logger('httpbl')->warning($logMessage);
    return $command;
  }
  // Start option:
  elseif (drush_get_option('start', FALSE)) {
    $command = drush_state_set($key = 'httpbl.check', $value = 2);
    $logMessage = t('Drush SOS started or restarted Httpbl page checking/blocking.');
    drush_log(dt($logMessage), 'ok');
     \Drupal::logger('httpbl')->notice($logMessage);
    return $command;
  }
  // Comment option:
  elseif (drush_get_option('comment', FALSE)) {
    $command = drush_state_set($key = 'httpbl.check', $value = 1);
    $logMessage = t('Drush SOS started checking/blocking on comment submissions only.');
    // Drush log only needed for feedback when the drupal logger is below warning level.
    //drush_log(dt($logMessage), 'ok');
     \Drupal::logger('httpbl')->warning($logMessage);
    return $command;
  }
  else {
    return;
  }
}

/**
 * Command handler. Make dummy evalutated hosts (for testing).
 *
 */
function drush_httpbl_mho($max = 255, $status = 0, $offset = 300, $pattern = '127.0.1.') {
  $limit = 255;
  $max <= $limit ?: $max = $limit;
  $logMessage = t('Making @max hosts of status @stat to expire in @time seconds, with pattern @pat.', ['@max' => $max, '@stat' => $status, '@time' => $offset, '@pat' => $pattern]);
  // Get Evaluator service and whitelist this IP.
  $httpblEvaluator = \Drupal::service('httpbl.evaluator');
  $command = $httpblEvaluator->makeHosts($max, $status, $offset, $pattern);
  drush_log(dt($logMessage), 'ok');
  return $command;
}

/**
 * Command handler. Make dummy blacklisted and banned hosts (for testing).
 *
 */
function drush_httpbl_mbb($max = 255, $offset = 300, $pattern = '127.1.8.' ) {
  $limit = 255;
  $max <= $limit ?: $max = $limit;
  $logMessage = t('Making @max blacklisted AND banned hosts to expire in @time seconds, with pattern @pat.', ['@max' => $max, '@time' => $offset, '@pat' => $pattern]);
  // Get Evaluator service and whitelist this IP.
  $httpblEvaluator = \Drupal::service('httpbl.evaluator');
  $command = $httpblEvaluator->makeBannedHosts($max, $offset, $pattern);
  drush_log(dt($logMessage), 'ok');
  return $command;
}

/**
 * Command handler. Quickly remove localhost (or any given IP) from ban & httpbl
 * tabled.  Useful for testing.
 */
function drush_httpbl_sol($ip = '127.0.0.1') {
  // Get Ban manager service and unBan if banned.
  $banManager = \Drupal::service('ban.ip_manager');

  if ($banManager->isBanned($ip)) {
    $banManager->unbanIp($ip);
    $logMessage = t('Drush SOL unbanned @ip.', ['@ip' => $ip]);
    drush_log(dt($logMessage), 'ok');
    \Drupal::logger('httpbl')->notice($logMessage);
  }
  else {
    drush_log(dt('@ip was not banned.', ['@ip' => $ip]), 'ok');
  }

  // Get Evaluator service and delete this IP.
  $httpblEvaluator = \Drupal::service('httpbl.evaluator');
  $status = $httpblEvaluator->getIpLocalStatus($ip);

  if ($status != NULL) {
   // Gather all hosts with this IP.
    $hosts = HostQuery::loadHostsByIp($ip);

    // If we have some, count them.
    if (isset($hosts) && !empty($hosts)) {
      $count = count($hosts);

      // As long as there's more than one...
      while ($count > 0) {
        // Sort them in order by index.
        ksort($hosts);
        // Get that host and delete it.
        $id = key($hosts);
        $host = Host::load($id);
        $host->delete();
        // Reverse sort the array and remove the last one.
        arsort($hosts);
        array_pop($hosts);
        // Rinse and repeat.
        $count --;
      }
    drush_log(dt('@ip was removed from evaluated hosts.', ['@ip' => $ip]), 'ok');
    }
    else {
      drush_log(dt('@ip was not an evaluated host.', ['@ip' => $ip]), 'ok');
    }
  }
  else {
    drush_log(dt('@ip was not an evaluated host.', ['@ip' => $ip]), 'ok');
  }

  return;
}

