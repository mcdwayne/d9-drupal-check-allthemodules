<?php

namespace Drupal\httpbl;

/**
 * Provides an interface defining a HttpblEvaluate manager.
 *
 * @ingroup httpbl_api
 */
interface HttpblEvaluatorInterface {

  /**
   * Examine config option for checking all page requests.
   *
   * @return bool
   *   TRUE if checking all page requests, FALSE otherwise.
   */
  public function getPageRequestOption();

  /**
   * Manages remote and local lookups on visiting host IPs, evaluates their
   * remote status as safe or suspicious and determines a locally stored status
   * (safe / white-listed, grey-listed, or blacklisted) which is used (by other
   * functions) to determine an appropriate, subsequent response to a request.
   *
   * @param string $ip
   *   The IP address being evaluated.
   *
   * @param object $request
   *   The request object being evaluated.
   *
   * @return array
   *   Contains a string ('evaluated') indicating whether or not the evaluation
   *   was completed and integer (0, 1 or 2) representing the scored status of
   *   the evaluated IP.
   */
  public function evaluateVisitor($ip, $request, $project_supported);

  /**
   * Get status of IP in httpbl_host table of stored hosts.
   *
   * (legacy name was "_httpbl_cache_get".)
   *
   * Now with belt AND suspenders!  Also scrubs any duplicates.
   *
   * In previous versions (D5-D7), there were reported experiences of
   * errors due to database attempts to insert duplicate keys; the only
   * "unique" key then was the IP address being inserted.  It's long been
   * "theoretically" impossible for this to happen, since we only insert new
   * records after checking Project Honeypot, and we only check Project
   * Honeypot if no record already exists locally.  Yet, somehow, a rarely
   * occurring condition could exist -- during heavy bot attacks? -- where
   * there were attempts to add a "new" record when one, in fact, already
   * existed for the same IP.
   *
   * Version D8 now stores these records as content entities and uses core
   * entity and storage handlers to deal with the database, agnostically.
   * And httpl "Host" entities have a unique, serial row index and uuid, so
   * they no longer rely on the IP address as the sole unique property.
   *
   * But, in the event of those rare conditions that could cause duplicate
   * records, this leaves the door open to letting them happen, without blowing
   * up the database.  In other words, while duplicates still theoretically
   * can't happen, they apparently can and will, though now it will be without
   * any database warnings.
   *
   * So, when checking locally for stored Hosts (IP addresses), all those with
   * matching IPs are gathered.  It should be a rare occurance to find more
   * than one, but they're now always counted, and if there is more than one
   * match, the oldest ones (by index number) will be scrubbed and only the
   * most recent record (and whatever status it contains) will be used.
   *
   * @param string  $ip
   *   The Host_IP to search for in local storage.
   *
   * @return integer | null
   *  The status of a stored host found.
   */
  public function getIpLocalStatus($ip);

  /**
   * Create and store new evaluated hosts to httpbl_host table.
   *
   * (legacy name was "_httpbl_cache_set".)
   *
   * While getIpLocalStatus() should already be handling the scrubbing of any
   * duplicates, this function will still double-check for existing
   * records before adding a new one for the same host.  If any are found,
   * it will skip adding another one, and instead log an error.
   *
   * @param string    $ip
   *   The host_ip to identify a new stored host.
   *
   * @param integer   $status
   *   The evaluated status to be stored with the new host.
   *
   * @param int       $offset
   *   Time added to \Drupal::time()->getRequestTime() to determine the expiry of the host.
   *
   * @return |void
   */
  public function setIpLocalStatus($ip, $status, $offset = 0);

  /**
   * Emergency White-list Update of Host IP.
   *
   * This functioned intended to be reserved for drush.
   *
   * @param string    $ip
   *   The IP address to white-list.
   *
   * @return |void
   */
  public function drushWhitelist($ip);

  /**
   * Translates status codes to Mnemonic.
   *
   * @param int $status
   *   The ID for an Httpbl managed IP address.
   *
   * @return string
   *   A translatable text equivalent of the status.
   */
  public function getHumanStatus($status);

  /**
   * Update stored status of Host IP.
   *
   * This is only called from the White-list challenge form, in the event the
   * visitor fails the challenge.
   *
   * When a Greylisted user fails a White-list test, they are Blacklisted
   * (status = 1).
   *
   * Also, per config option, they are banned in Core Ban_ip.
   *
   * (legacy name was "_httpbl_cache_update".)
   *
   * @param string    $ip
   *   The IP address to white-list.
   *
   * @param integer    $status
   *   Status to set.  Defaults to 0 (white-list).
   *
   * @param integer    $offset
   *   Time to add to \Drupal::time()->getRequestTime() to generate expiry.
   *
   * @return |void
   */
  public static function updateIpLocalStatus($ip, $status, $offset = 0);

  /**
   * Check if an IP is already white-listed via a session white-list challenge.
   *
   * @param string $ip
   *
   * @return bool
   *
   *   TRUE if $_SESSION['httpbl_status'] == 'session_whitelisted'
   */
  public static function visitor_whitelisted_session($ip);

}
