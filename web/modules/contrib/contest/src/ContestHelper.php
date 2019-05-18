<?php

namespace Drupal\contest;

/**
 * Helper class for contests.
 */
class ContestHelper {

  /**
   * The CSV header.
   *
   * @return string
   *   The CSV header string.
   */
  public static function csvHeader() {
    return '"email","name","address","city","state","zip","phone"' . "\n";
  }

  /**
   * Return the data to the browser as a file.
   *
   * @param string $file
   *   The CSV file name.
   * @param string $csv
   *   The CSV string.
   * @param string $type
   *   The content type.
   */
  public static function downloadFile($file = 'contest.csv', $csv = '', $type = 'text/csv') {
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private');
    header('Content-Description: File Transfer');
    header("Content-Disposition: attachment; filename=\"$file\"");
    header('Content-Length: ' . strlen($csv));
    header('Content-Transfer-Encoding: binary');
    header("Content-Type: $type");
    header('Expires: 0');
    header('Pragma: no-cache');
    header('Pragma: private');

    $fh = fopen('php://output', 'w');
    fwrite($fh, $csv);
    fclose($fh);

    exit();
  }

  /**
   * Retrieve all the data for a contest.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return object
   *   All the data for a contest including:
   *   - id (int) The contest's ID.
   *   - uid (int) The User's ID.
   *   - title (string) The contest's title.
   *   - body (string) The body fo the contest.
   *   - sponsor_uid (int) The contest sponsor's ID.
   *   - sponsor_url (string) The contest sponsor's URL.
   *   - start (int) The start date of the contest.
   *   - end (int) The end date of the contest.
   *   - now (int) The current timestamp.
   *   - period (int) The frequency a user can enter, (daily, weekly, etc.).
   *   - places (int) The number of places in a contest.
   *   - publish_winners (int) The contest published flag.
   *   - config (object) The contest config data.
   *   - host (ContestUser) The host's ContestUser object.
   *   - sponsor (ContestUser) The sponsor's ContestUser object.
   *   - contestants (array) An array of contestant data.
   *   - entrants (int) The number of contest entrants.
   *   - entries (int) The number of contest entries.
   *   - winners (array) An array of user's IDs and respective winning places.
   */
  public static function getContestData($id) {
    $contest = ContestStorage::getContest($id);
    $contestants = [];
    $entrants = 0;
    $entries = 0;
    $host = (object) \Drupal::config('contest.host')->get();

    if (empty($contest)) {
      return self::prototype(NULL);
    }
    $winners = array_flip(self::getWinners($id));

    foreach (ContestStorage::getContestants($id) as $row) {
      if (!$row->mail) {
        continue;
      }
      $contestants[$row->uid] = $row;
      $entrants++;
      $entries += $row->qty;

      if (!empty($winners[$row->uid])) {
        $row->winner = $winners[$row->uid];
      }
    }
    $contest->http_host = $_SERVER['HTTP_HOST'];
    $contest->http_server = $_SERVER['SERVER_NAME'];
    $contest->country = \Drupal::config('system.date')->get('country.default');
    $contest->timezone = \Drupal::config('system.date')->get('timezone.default');
    $contest->now = REQUEST_TIME;
    $contest->config = (object) \Drupal::config('contest.config')->get();
    $contest->host = new ContestUser($host->uid, ['title' => $host->title]);
    $contest->sponsor = new ContestUser($contest->sponsor_uid, ['url' => $contest->sponsor_url]);
    $contest->contestants = $contestants;
    $contest->entrants = $entrants;
    $contest->entries = $entries;
    $contest->winners = $winners;

    $contest->config->notify = $contest->end + ($contest->config->notify * ContestStorage::DAY);

    return $contest;
  }

  /**
   * Get a default format.
   *
   * @return string
   *   The best default input format we can find.
   */
  public static function getDefaultFormat() {
    $formats = filter_formats();
    $preferences = [
      'full_html',
      'restricted_html',
      'basic_html',
      'plain_text',
    ];
    foreach ($preferences as $format) {
      if (!empty($formats[$format])) {
        break;
      }
    }
    return $format;
  }

  /**
   * Try to get the site's email address in several ways.
   *
   * @return string
   *   The site's email address.
   */
  public static function getSiteMail() {
    global $base_url;
    $site_mail = \Drupal::config('system.site')->get('mail_notification');

    if (empty($site_mail)) {
      $site_mail = \Drupal::config('system.site')->get('mail');
    }
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }
    return $site_mail ? $site_mail : 'webmaster@' . preg_replace('/^https?:\/\//', '', strtolower($base_url));
  }

  /**
   * Gets the states for the selected country.
   *
   * @param string $country
   *   The ISO country code.
   *
   * @return array
   *   An ISO code to country name hash.
   */
  public static function getStates($country = 'US') {
    $countries = \Drupal::config('contest.country')->get('countries');

    return !empty($countries[$country]) ? $countries[$country] : [];
  }

  /**
   * Retrieve the contest winners in order.
   *
   * @param int $cid
   *   The contest's ID.
   *
   * @return array
   *   An ordered array of user IDs to winning place.
   */
  public static function getWinners($cid) {
    $winners = \Drupal::entityManager()->getStorage('contest')->getWinners($cid);

    return count($winners) ? array_combine(range(1, count($winners)), array_values($winners)) : [];
  }

  /**
   * Determine if the minimum age requirement has been met.
   *
   * @param int|array $age
   *   The birthdate in either UNIX time or an array with year, month, day.
   *
   * @return bool
   *   True if the minimum age requirement is met, otherwise false.
   */
  public static function minAge($age = NULL) {
    $min_date = mktime(0, 0, 0, intval(date('n')), intval(date('j')), (intval(date('Y')) - variable_get('contest_min_age', 18)));

    if (is_int($age)) {
      $birthday = $age;
    }
    elseif (isset($age['day']) && isset($age['month']) && isset($age['year'])) {
      $birthday = mktime(0, 0, 0, intval($age['month']), intval($age['day']), intval($age['year']));
    }
    elseif (is_string($age) && strtotime($age) !== FALSE) {
      $birthday = strtotime($age);
    }
    else {
      return FALSE;
    }
    return ($birthday <= $min_date) ? TRUE : FALSE;
  }

  /**
   * Insert the options in the correct position.
   *
   * @param array $options
   *   The initial set of options.
   * @param array $xtras
   *   Extra options to add.
   *
   * @return bool
   *   True if the minimum age requirement is met, otherwise false.
   */
  public static function optionInsert(array $options = [], array $xtras = []) {
    $options += $xtras;
    ksort($options);

    return $options;
  }

  /**
   * Return a single csv line for a contest.
   *
   * @param object $usr
   *   A ContestUser object.
   *
   * @return string
   *   A comma separated list of users.
   */
  public static function toCsv($usr) {
    $csv = '"' . preg_replace('/"/', '\"', $usr->mail) . '"';
    $csv .= ',"' . preg_replace('/"/', '\"', $usr->fullName) . '"';
    $csv .= ',"' . preg_replace('/"/', '\"', $usr->address) . '"';
    $csv .= ',"' . preg_replace('/"/', '\"', $usr->city) . '"';
    $csv .= ',"' . preg_replace('/"/', '\"', $usr->state) . '"';
    $csv .= ',"' . preg_replace('/"/', '\"', $usr->zip) . '"';
    $csv .= ',"' . preg_replace('/"/', '\"', $usr->phone) . '"';

    return "$csv\n";
  }

  /**
   * Create a summary from the provided text.
   *
   * @param string $string
   *   The string to trim.
   * @param int $max
   *   The target length of the string.
   *
   * @return string
   *   The provided text truncated to the requested length.
   */
  public static function trimTxt($string, $max = 150) {
    $txt = '';

    foreach (preg_split('/\s+/', $string) as $atom) {
      $length = strlen($atom);
      if (($length + strlen($txt) + 1) > $max) {
        return preg_match('/<\/p>$/', $txt) ? preg_replace('/<\/p>$/', '&hellip;</p>', $txt) : "$txt&hellip;</p>";
      }
      $txt .= $txt ? " $atom" : $atom;
    }
    return $txt;
  }

  /**
   * Build a contest prototype.
   *
   * @param object $config
   *   The contest configuration data.
   *
   * @return object
   *   A dummy object that should prevent throwing errors.
   */
  protected static function prototype($config) {
    return (object) [
      'id'              => 0,
      'uid'             => 0,
      'title'           => '',
      'body'            => '',
      'sponsor_uid'     => 1,
      'sponsor_url'     => '',
      'start'           => REQUEST_TIME,
      'end'             => REQUEST_TIME,
      'now'             => REQUEST_TIME,
      'period'          => 0,
      'places'          => 0,
      'publish_winners' => 0,
      'created'         => REQUEST_TIME,
      'config'          => $config,
      'http_host'       => $_SERVER['HTTP_HOST'],
      'http_server'     => $_SERVER['SERVER_NAME'],
      'country'         => \Drupal::config('system.date')->get('country.default'),
      'timezone'        => \Drupal::config('system.date')->get('timezone.default'),
      'host'            => new ContestUser(1),
      'sponsor'         => new ContestUser(1),
      'contestants'     => [],
      'entrants'        => 0,
      'entries'         => 0,
      'winners'         => [],
    ];
  }

}
