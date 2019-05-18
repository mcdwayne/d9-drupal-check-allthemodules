<?php

namespace Drupal\httpbl;

use Drupal\httpbl\Entity\Host;
use Drupal\ban\BanIpManagerInterface;
use Drupal\httpbl\Logger\HttpblLogTrapperInterface;

/**
 * Checking/Blocking Options
 */

// "Off" No checking or blocking is enabled.
define('HTTPBL_CHECK_NONE',     0);

// Checking/blocking only comment submissions.
define('HTTPBL_CHECK_COMMENTS', 1);

// Checking/blocking all page requests.
define('HTTPBL_CHECK_ALL',      2);

/**
 * Threat Level Threshold Defaults
 */
// Threat level threshold above which a user is grey-listed.
define('HTTPBL_THRESHOLD_GREY',   1);

// Threat level threshold above which a user is blacklisted.
define('HTTPBL_THRESHOLD_BLACK', 50);

/**
 * Host Status Values
 */
// White-listed locally: no (or low) threat score at Project Honeypot.
define('HTTPBL_LIST_SAFE',  0);

// Grey-listed locally: medium threat score, session-based white-listing
// accepted on challenge.
define('HTTPBL_LIST_GREY',  2);

// Blacklisted locally: high threat, considered entirely undesirable.
define('HTTPBL_LIST_BLACK', 1);

/**
 * Storage Options
 */
// "Off" No storage enabled.
define('HTTPBL_DB_OFF',       0);

// Store results as host entities.
define('HTTPBL_DB_HH',        1);

// "Auto-banning": Store host entities and add blacklisted IPs to Drupal Ban.
define('HTTPBL_DB_HH_DRUPAL', 2);

/**
 * Log Volume
 */
// Quiet: passes error, critical, alert and emergency.
define('HTTPBL_LOG_QUIET',      0);

// Minimal logging (warning & notice) for positive lookups and admin actions.
define('HTTPBL_LOG_MIN',        1);

// Verbose Logging - Everything! Very verbose, including debug and info.
// Recommended only for testing !!!
define('HTTPBL_LOG_VERBOSE',    2);

/**
 * Source Stamps  (source of evaluation)
 */
// Used only when an evaluation is orignally sourced and has 
// not been managed by a local admin.
define('HTTPBL_ORIGINAL_SOURCE', 'Project Honeypot');

// Used when a host has been edited.
define('HTTPBL_ADMIN_SOURCE', 'Admin Managed');

// Used when a grey-listed host converts to blacklisted after failing a
// white-list challenge.
define('HTTPBL_CHALLENGE_FAILURE', 'Http:BL Challenged');

// Used (only in log) when a grey-listed host is session white-listed after
// successful white-list challenge.
define('HTTPBL_CHALLENGE_SUCCESS', 'Http:BL Session White-listed');

// Used when a host has been rescued with drush.
define('HTTPBL_DRUSH_SOS_SOURCE', 'Http:BL Drush SOS');

// Used when a host has been created with drush.
define('HTTPBL_DRUSH_CREATED', 'Http:BL Drush makeHosts()');

// Used when a host has been created as banned, with drush.
define('HTTPBL_DRUSH_CREATED_BANNED', 'Http:BL Drush makeBannedHosts()');

/**
 * HttpblEvaluator evaluates visitor/host page requests.
 */
class HttpblEvaluator implements HttpblEvaluatorInterface {

  /**
   * The ban IP manager.
   *
   * @var \Drupal\ban\BanIpManagerInterface
   */
  protected $banManager;

  /**
   * A logger arbitration instance.
   *
   * @var \Drupal\httpbl\Logger\HttpblLogTrapperInterface
   */
  protected $logTrapper;

  /**
   * Construct HttpblEvaluator.
   *
   * @param \Drupal\ban\BanIpManagerInterface $banManager
   *   Core Drupal Ban manager.
   * @param \Drupal\httpbl\Logger\HttpblLogTrapperInterface $logTrapper
   *   A logger arbitration instance.
  */
  public function __construct(BanIpManagerInterface $banManager,  HttpblLogTrapperInterface $logTrapper) {
    $this->banManager = $banManager;
    $this->logTrapper = $logTrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function getPageRequestOption() {
    $check_option = (int) \Drupal::state()->get('httpbl.check');
    if ($check_option == HTTPBL_CHECK_ALL) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Manages remote and local lookups on visiting host IPs, evaluates their
   * remote status as safe or suspicious and determines a locally stored status
   * (safe / white-listed, grey-listed, or blacklisted) which is used (by other
   * functions) to determine an appropriate, subsequent response to a request.
   */
  public function evaluateVisitor($ip, $request, $project_supported) {

    // Evaluated status that was already locally stored or was calculated based on
    // a score retrieved from Project Honeypot.
    /** @var integer $evaluated_status */
    static $evaluated_status;

    // If not a supported lookup, mark "safe" and log notice.
    // This will avoid any further processing or storage.
    if (!$project_supported) {
      $evaluated_status = HTTPBL_LIST_SAFE;
      $this->logTrapper->trapNotice('HttpBL evaluation not supported for IPv6 @ip.', ['@ip' => $ip]);
    }

    // Evaluated status was already calculated -- return.
    if (is_int($evaluated_status)) {
      $evaluated = ['evaluated', $evaluated_status];
      /** @var array $evaluated */
      return $evaluated;
    }

    // Check if visitor already has a white-listed session (granted by the white-list challenge).
    if (self::visitor_whitelisted_session($ip)) {
      $evaluated_status = HTTPBL_LIST_SAFE;
      $this->logTrapper->trapDebug('@ip already session white-listed for request @request.', ['@ip' => $ip, '@request' => $request->getRequestUri()]);

      $evaluated = ['evaluated', $evaluated_status];
      return $evaluated;
    }
    // If any HttpBL Storage is enabled - do a storage lookup of IP
    elseif (\Drupal::state()->get('httpbl.storage') > HTTPBL_DB_OFF ) {
      $evaluated_status = $this->getIpLocalStatus($ip);
      //Humanize the status results for any verbose logging.
      $human = 'Not found';
      $status = $evaluated_status;

      if (is_string($evaluated_status)) {

        switch ($evaluated_status) {
          case '0':
            $human = 'white-listed';
            break;
          case '1':
            $human = 'blacklisted';
            break;
          case '2':
            $human= 'greylisted';
            // Prepare to set up a challenge response.
            $_SESSION['httpbl_ip'] = $ip;
            $_SESSION['httpbl_challenge'] = TRUE;

            break;
        }
      }
      $this->logTrapper->trapDebug('Local query for @ip: @human (status = @status).', ['@ip' => $ip, '@status' => $status, '@human' => $human]);
    }

    // Visitor is not already white listed and not found in Httpbl table, so we'll do a DNS Lookup.
    if (!(is_numeric($evaluated_status))) {

      $this->logTrapper->trapDebug('Honeypot DNS Lookup for IP @ip.', ['@ip' => $ip]);

      // Do a Project Honeypot DNS lookup, and continue if lookup was succesful
      if ($response = $this->httpbl_dnslookup($ip)) {
        $stats = \Drupal::state()->get('httpbl.stats') ?:  TRUE;
        $black_threshold = \Drupal::state()->get('httpbl.black_threshold') ?:  HTTPBL_THRESHOLD_BLACK;
        $grey_threshold = \Drupal::state()->get('httpbl.grey_threshold') ?:  HTTPBL_THRESHOLD_GREY;
        $score = $response['threat'];
        //@todo Someday we'll do something with the 'type' response from P.H.
        //$type = $response['type'];

        // Blacklisted?
        // (Is the threat score at Project Honeypot above our threshold?)
        if (($score > $black_threshold) && $response['type']) {

          $this->logTrapper->trapWarning('@ip ranked: blacklisted (Threat Score = @score).', ['@ip' => $ip, '@score' => $score, 'link' => self::projectLink($ip)]);

          // If settings indicate we are storing results...
          if (\Drupal::state()->get('httpbl.storage') > HTTPBL_DB_OFF) {
            // Store this blacklisted IP.
            $this->setIpLocalStatus($ip, HTTPBL_LIST_BLACK, \Drupal::state()->get('httpbl.blacklist_offset') ?:  31536000);

            // Increment the stats if configured to do so.
            if ($stats) {
              \Drupal::state()->set('httpbl.stat_black', \Drupal::state()->get('httpbl.stat_black')+1);
            }
          }

          $evaluated_status = HTTPBL_LIST_BLACK;
          $evaluated = ['evaluated', $evaluated_status];
          return $evaluated;
        }
        // Grey-listed?
        elseif (($score > $grey_threshold) && $response['type']) {
          // Prepare to set up a challenge response.
          $_SESSION['httpbl_ip'] = $ip;
          $_SESSION['httpbl_challenge'] = TRUE;
          $this->logTrapper->trapNotice('@ip ranked: grey-listed (Threat Score = @score).', ['@ip' => $ip, '@score' => $score, 'link' => self::projectLink($ip)]);

          // Store the results if configured to do so.
          if (\Drupal::state()->get('httpbl.storage') > HTTPBL_DB_OFF) {
            $this->setIpLocalStatus($ip, HTTPBL_LIST_GREY, \Drupal::state()->get('httpbl.greylist_offset') ?:  86400);

            // Increment the stats if configured to do so.
            if ($stats) {
              \Drupal::state()->set('httpbl.stat_grey', \Drupal::state()->get('httpbl.stat_grey')+1);
            }
          }
          $evaluated_status = HTTPBL_LIST_GREY;
          $evaluated = ['evaluated', $evaluated_status];
          return $evaluated;
        }

      }
      else {
        // No result from Project Honeypot, so log and then...
        $this->logTrapper->trapInfo('No Honeypot profile for @ip. ("safe").', ['@ip' => $ip, 'link' => self::projectLink($ip)]);

        // If settings indicate we are storing results,
        if (\Drupal::state()->get('httpbl.storage') > HTTPBL_DB_OFF) {
          // White-list locally - with configured offset settings (default is 3 hours).
          $this->setIpLocalStatus($ip, HTTPBL_LIST_SAFE, \Drupal::state()->get('httpbl.safe_offset') ?:  10800);
        }

        // Evaluated (assumed) Safe.
        $evaluated_status = HTTPBL_LIST_SAFE;
     }

    $evaluated = ['evaluated', $evaluated_status];
    return $evaluated;

    }
    // User was found in httpbl storage, report their status if problematic
    elseif (!($evaluated_status == HTTPBL_LIST_SAFE)) {
      // This line will show when only blocking comment submissions.
      drupal_set_message(t('Your IP address (@ip) is restricted on this site.', ['@ip' => $ip]), 'error', FALSE);
    }

  // Fini!
  $evaluated = ['evaluated', $evaluated_status];
  return $evaluated;
  }

  /**
   * {@inheritdoc}
   *
   * Check if an IP is already white-listed via a session white-list challenge.
   */
  public static function visitor_whitelisted_session($ip) {
    return (isset($_SESSION['httpbl_status']) && $_SESSION['httpbl_status'] == 'session_whitelisted');
  }

  /**
   * Do http:BL DNS lookup at Project Honeypot Org
   *
   * @param string $ip
   *    The IP address to be checked.
   * @param string $key
   *    The administrative access key.
   *
   * @return array $values | FALSE
   *
   * @todo Don't think anything is really capturing the response type
   *  values to store with the hosts.  Use these?
   */
  public function httpbl_dnslookup($ip, $key = NULL) {
    // Thanks to J.Wesley2 at
    // http://www.projecthoneypot.org/board/read.php?f=10&i=1&t=1

    if (!$ip = self::_httpbl_reverse_ip($ip)) {
      return FALSE;
    }

    // Make sure there is a valid access key before we proceed.
    if (!$key && !$key = \Drupal::state()->get('httpbl.accesskey') ?: NULL) {
      return FALSE;
    }

    $query = $key . '.' . $ip . '.dnsbl.httpbl.org.';
    $response = gethostbyname($query);

    if ($response == $query) {
      // if the domain does not resolve then it will be the same thing we passed to gethostbyname.
      return FALSE;
    }

    $values = array();
    $values['raw'] = $response;
    $response = explode('.', $response);

    if ($response[0] != '127') {
      // if the first octet is not 127, the response should be considered invalid
      $this->logTrapper->trapWarning('DNS Lookup failed for @ip, response was @response', array('@ip' => $ip, '@response' => $values['raw']));
      return FALSE;
    }

    // Lookup at Project Honey Pot was successful.
    $this->logTrapper->trapDebug('DNS lookup results for @ip, response was @response', array('@ip' => $ip, '@response' => $values['raw']));

    $values['last_activity'] = $response[1];
    $values['threat'] = $response[2];
    $values['type'] = $response[3];
    if ($response[3] == 0) {
      //if it's 0 then it's only a Search Engine
      $values['search_engine'] = TRUE;
    }

    if ($response[3] & 1) {
      //does it have the same bits as 1 set
      $values['suspicious'] = TRUE;
    }

    if ($response[3] & 2) {
      //does it have the same bits as 2 set
      $values['harvester'] = TRUE;
    }

    if ($response[3] & 4) {
      //does it have the same bits as 4 set
      $values['comment_spammer'] = TRUE;
    }

    return $values;
  }

  /**
   * Reverse IP octets
   *
   * @param string $ip
   * @return string
   */
  public static function _httpbl_reverse_ip($ip) {
    if (!is_numeric(str_replace('.', '', $ip))) {
      return NULL;
    }

    $ip = explode('.', $ip);

    if (count($ip) != 4) {
      return NULL;
    }

    return $ip[3] . '.' . $ip[2] . '.' . $ip[1] . '.' . $ip[0];
  }

  /**
   * {@inheritdoc}
   *
   * Get status of IP in httpbl_host table of stored hosts.
   *
   * (legacy name was "_httpbl_cache_get".)
   */
  public function getIpLocalStatus($ip) {
    // Gather all hosts with this IP.
    $hosts = HostQuery::loadHostsByIp($ip);

    // If we have some, count them.
    if (isset($hosts) && !empty($hosts)) {
      $count = count($hosts);

      // As long as there's more than one...
      while ($count > 1) {
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

      // Get the status of the last IP found.
      $id = key($hosts);
      $host = Host::load($id);
      $status = $host->getHostStatus();
    }
    else {
      $status = NULL;
    }
    return $status;
  }

  /**
   * {@inheritdoc}
   *
   * Create and store new evaluated hosts to httpbl_host table.
   *
   * (legacy name was "_httpbl_cache_set")
   */
  public function setIpLocalStatus($ip, $status, $offset = 0) {
    $hosts = HostQuery::loadHostsByIp($ip);
    if (isset($hosts) && empty($hosts)) {
      $host = Host::create([
        'host_ip' => $ip,
        'host_status' => $status,
        'expire' => \Drupal::time()->getRequestTime() + $offset,
        'source' => HTTPBL_ORIGINAL_SOURCE,
   ]);
      $host->save();
      $project_link = $host->projectLink();
      $source = $host->getSource();
      // If configured to also ban blacklisted IPs via Drupal Core Ban module...
      if ($status == HTTPBL_LIST_BLACK && \Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH_DRUPAL && \Drupal::moduleHandler()->moduleExists('ban')) {
        // Ban this IP!
        $this->banManager->banIp($ip);
        $this->logTrapper->trapNotice('Host: new blacklisted and banned @title. Source: @source.',
          array(
            '@title' => $host->label(),
            '@source' => $source,
            'link' => $project_link,
        ));
      }
      elseif ($status == HTTPBL_LIST_BLACK && \Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH) {
        $this->logTrapper->trapNotice('Host: new blacklisted @title. Source: @source.',
          array(
            '@title' => $host->label(),
            '@source' => $source,
            'link' => $project_link,
        ));
      }
      elseif ($status == HTTPBL_LIST_GREY) {
        $this->logTrapper->trapNotice('Host: new grey-listed @title. Source: @source.',
          array(
            '@title' => $host->label(),
            '@source' => $source,
            'link' => $project_link,
        ));
      }
      elseif ($status == HTTPBL_LIST_SAFE) {
        // Most IPs should be safe, so only log this as Info.
        $this->logTrapper->trapInfo('Host: new white-listed @title. Source: @source.',
          array(
            '@title' => $host->label(),
            '@source' => $source,
            'link' => $project_link,
        ));
      }

      return;
    }
    else {
      $this->logTrapper->trapError('Attempt to add host @ip, but it already exists!', ['@ip' => $ip]);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Emergency White-list Update of Host IP.
   */
  public function drushWhitelist($ip) {
    // Set a default of 48 hours.
    $offset = 172800;
    // Take the higher of the default or the configured offset for safe hosts.
    $offsetConfig = \Drupal::state()->get('httpbl.safe_offset');
    //$max <= $limit ?: $max = $limit;
    $offset >= $offsetConfig ?: $offset = $offsetConfig;


    $hosts = HostQuery::loadHostsByIp($ip);
    if (isset($hosts) && !empty($hosts)) {
      foreach ($hosts as $host) {
        $host->setHostStatus(0);
        $host->setExpiry(\Drupal::time()->getRequestTime() + $offset);
        $host->setSource(HTTPBL_DRUSH_SOS_SOURCE);
        $host->save();
      }
    }
    else {
      // Warning to identify any abuse.
      $this->logTrapper->trapWarning('Drush whitelist did not find IP @ip.', ['@ip' => $ip]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHumanStatus($status){
    switch ($status) {
      case '0':
        $human = t('White-listed');
        break;
      case '1':
        $human = t('Blacklisted');
        break;
      case '2':
        $human= t('Grey-listed');
        break;
    }
    return $human;
  }

  /**
   * Update stored status of Host IP.
   *
   * (legacy name was "_httpbl_cache_update".)
   */
  public static function updateIpLocalStatus($ip, $status, $offset = 0) {
    // Collect needed services.
    $banManager = \Drupal::service('ban.ip_manager');
    $logTrapper = \Drupal::service('httpbl.logtrapper');

    $hosts = HostQuery::loadHostsByIp($ip);
    if (isset($hosts) && !empty($hosts)) {
      foreach ($hosts as $host) {
        $host->setHostStatus($status);
        $host->setExpiry(\Drupal::time()->getRequestTime() + $offset);
        $host->setSource(HTTPBL_CHALLENGE_FAILURE);
        $host->save();
      }
    }
    else {
      // Error.  Something could be broken.
      $logTrapper->trapError('Cannot blacklist non-existing IP (@ip).', ['@ip' => $ip]);
    }

    // If blacklisted host and using "Auto-banning"...
    if ($status == HTTPBL_LIST_BLACK && \Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH_DRUPAL) {

      // Check if host is already banned.
      if ($banManager->isBanned($ip)) {
        // Warning.  This shouldn't be happening.
        $logTrapper->trapWarning('This host (@ip) is already banned', ['@ip' => $ip]);
        // This message should never be seen by anyone who has really been banned.
        drupal_set_message(t('IP address ( @ip ) currently banned from this site.', array('@ip' => $ip)), 'error', FALSE);
      }
      // Not already banned, so ban it!
      else {
        $banManager->banIp($ip);
        // Warning.   Most likely a white-list challenge failure.
        $logTrapper->trapWarning('Host (@ip) has been banned from this site.', ['@ip' => $ip]);
        // Possible to see this message once after failing challenge, but never
        // again after a page refresh.
        drupal_set_message(t('Your IP address ( @ip ) has been banned from this site.', array('@ip' => $ip)), 'error', FALSE);
      }
    }
  }

  /**
   * Quickly make up to 255 evaluated hosts of a certain status and expire time.
   *
   * @param int    $max     The number of hosts to be generated.
   * @param int    $status  The status (0=safe, 1=blackisted, 2=grey-listed)
   * @param int    $offset  The time from now the host should expire.
   * @param string $pattern The pattern used for IP addresses.
   *
   * @internal  $count
   * ---------------------------------------------------------------------------
   *
   * Example uses:
   * Execute in devel/php to make dummy hosts.
   *
   * Also executable in drush as "drush mho".
   *
   * use Drupal\httpbl\Utility\Makehosts;
   * Makehosts::makeHosts(); // Use defaults and create 255 safe hosts that will
   * expire in 5 minutes.  Useful for testing Cron.
   *
   * use Drupal\httpbl\Utility\Makehosts;
   * Makehosts::makeHosts(50,2, 60, '129.0.1.');  Make some grey-listed and edit
   * them.
   *
   * use Drupal\httpbl\Utility\Makehosts;
   * Makehosts::makeHosts(1,0, 120, '127.0.0.' ); Make a localhost that lasts 
   * 2 minutes.  Then delete it (it will come right back, from Project Honeypot!)
   *
   */
  public static function makeHosts($max = 255, $status = 0, $offset = 300, $pattern = '127.0.1.' ) {
    $limit = 255;
    $max <= $limit ?: $max = $limit;
    $count = 1;
    $max = $max + 1;

    while ($count < $max) {
      $ip = $pattern . $count;
      $host = Host::create([
        'host_ip' => $ip,
        'host_status' => $status,
        'expire' => \Drupal::time()->getRequestTime() + $offset,
        'source' => t(HTTPBL_DRUSH_CREATED),
      ]);
      $host->save();
      $logTrapper = \Drupal::service('httpbl.logtrapper');
      $logTrapper->trapDebug('@ip test host created with makeHosts().', ['@ip' => $ip]);
      $count ++;
    }
  }

  /**
   * Quickly make up to 255 evaluated and banned hosts of a certain expire time.
   *
   * @param int    $max     The number of hosts to be generated.
   * @param int    $offset  The time from now the host should expire.
   * @param string $pattern The pattern used for IP addresses.
   *
   * @internal  $count
   * ---------------------------------------------------------------------------
   *
   * Example uses:
   * Execute in devel/php to make dummy hosts.
   *
   * Also executable in drush as "drush mbb".
   *
   * use Drupal\httpbl\Utility\Makehosts;
   * Makehosts::makeBannedHosts(); // Use defaults to create 255 blacklisted and
   * banned hosts that will expire in 5 minutes.  Useful for testing Cron.
   *
   * use Drupal\httpbl\Utility\Makehosts;
   * Makehosts::makeBannedHosts(50,60, '129.0.8.');  Make 50 blacklisted and
   * banned hosts that will last one minute.
   *
   */
  public static function makeBannedHosts($max = 255, $offset = 300, $pattern = '127.1.8.' ) {
    $limit = 255;
    $max <= $limit ?: $max = $limit;
    $count = 1;
    $max = $max + 1;
    $status = 1;

    while ($count < $max) {
      $ip = $pattern . $count;
      $host = Host::create([
        'host_ip' => $ip,
        'host_status' => $status,
        'expire' => \Drupal::time()->getRequestTime() + $offset,
        'source' => t(HTTPBL_DRUSH_CREATED_BANNED),
      ]);
      $host->save();
      $banManager = \Drupal::service('ban.ip_manager');
      $banManager->banIp($host->label());

      $logTrapper = \Drupal::service('httpbl.logtrapper');
      $logTrapper->trapDebug('@ip test banned host created with makeBannedHosts().', ['@ip' => $ip]);
      $count ++;
    }
  }

  /**
   * Creates a link to Project Honey Pot IP Address Inspector.
   *
   * This function is used after a lookup, before a host entity has been
   * created, to enable an operations link in the log entry.
   *
   * @param string $ip
   *   The IP address that was looked up.
   * @param string $text
   *   The link text.
   * @return string
   *   The formatted link.
   */
  public static function projectLink($ip, $text = 'Project Honeypot') {
    $url = \Drupal\Core\Url::fromUri('http://www.projecthoneypot.org/search_ip.php?ip=' . $ip);
    $url_options = [
      'attributes' => [
        'target' => '_blank',
        'title' => t('Project Honey Pot IP Address Inspector.'),
      ]];
    $url->setOptions($url_options);

    // Break this line up for debugging.  
    //$operations = \Drupal\Core\Link::fromTextAndUrl(t($text), $url )->toString();
    $operations = \Drupal\Core\Link::fromTextAndUrl(t($text), $url );
    // Below fails (intermittently) in core url_generator, when page_cache
    // is enabled.
    $operations = $operations->toString();

    return $operations;
  }


}
