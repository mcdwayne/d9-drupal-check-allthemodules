<?php

namespace Drupal\drd\Entity;

use Drupal\drd\EncryptionEntityInterface;

/**
 * Provides an interface for defining Domain entities.
 *
 * @ingroup drd
 */
interface DomainInterface extends BaseInterface, EncryptionEntityInterface {

  /**
   * Get the cached cookies to be included into the next request.
   *
   * @return array
   *   The cached cookies.
   */
  public function getCookies();

  /**
   * Cache cookies that have been received from a request.
   *
   * @param array $cookies
   *   The cookies.
   *
   * @return $this
   */
  public function setCookies(array $cookies);

  /**
   * Get language code of the domain.
   *
   * @return string
   *   Language code.
   */
  public function getLangCode();

  /**
   * Gets the Domain's domain name.
   *
   * @return string
   *   The Domain's domain name.
   */
  public function getDomainName();

  /**
   * Gets the Domain core.
   *
   * @return \Drupal\drd\Entity\CoreInterface
   *   Core of the Domain.
   */
  public function getCore();

  /**
   * Sets the Domain core.
   *
   * @param CoreInterface $core
   *   The Domain core.
   *
   * @return $this
   */
  public function setCore(CoreInterface $core);

  /**
   * Get the domain's selected authentication type.
   *
   * @return string
   *   The selected authentication type.
   */
  public function getAuth();

  /**
   * Get the domain's authentication settings.
   *
   * @param string|null $type
   *   TODO.
   * @param bool $for_remote
   *   TODO.
   *
   * @return array
   *   The authentication settings.
   */
  public function getAuthSetting($type = NULL, $for_remote = FALSE);

  /**
   * Set the domain's authentication settings.
   *
   * @param array $settings
   *   The authentication settings.
   *
   * @return $this
   */
  public function setAuthSetting(array $settings);

  /**
   * Get the domain's selected crypt method.
   *
   * @return string
   *   The selected crypt method.
   */
  public function getCrypt();

  /**
   * Get the domain's crypt settings.
   *
   * @param string|null $type
   *   TODO.
   *
   * @return array
   *   The crypt settings.
   */
  public function getCryptSetting($type = NULL);

  /**
   * Set the domain's crypt settings.
   *
   * @param array $settings
   *   The crypt settings.
   * @param bool $encrypted
   *   Whether the data is already encrypted or not.
   *
   * @return $this
   */
  public function setCryptSetting(array $settings, $encrypted = FALSE);

  /**
   * Create a domain entity instance by checking if one already exists.
   *
   * @param CoreInterface $core
   *   The core entity to which the domain is attached.
   * @param string $uri
   *   The URI of the domain.
   * @param array $extra_values
   *   Extra values for the entity.
   *
   * @return $this
   *
   * @throws \Exception
   */
  public static function instanceFromUrl(CoreInterface $core, $uri, array $extra_values);

  /**
   * Build the URL for a remote request.
   *
   * @param string $query
   *   The query of the remote request.
   *
   * @return \Drupal\Core\Url
   *   Fully setup URL object.
   */
  public function buildUrl($query = '');

  /**
   * Determine the supported crypt methods for a remote domain.
   *
   * @return mixed
   *   - FALSE if we can't connect to the given domain
   *   - NULL if we were able to connect but without the expected DRD result
   *   - the decoded response from the remote DRD
   */
  public function getSupportedCryptMethods();

  /**
   * Authorize DRD instance remotely by using server secrets.
   *
   * @param string $method
   *   Name of the authorization method.
   * @param array $secrets
   *   List of secrets.
   *
   * @return bool
   *   Whether the authorization succeeded.
   */
  public function authorizeBySecret($method, array $secrets);

  /**
   * Push a one-time-token to a remote domain.
   *
   * @param string $token
   *   The one-time-token.
   *
   * @return bool
   *   TRUE if the operation was successful, FALSE otherwise.
   */
  public function pushOtt($token);

  /**
   * Returns if domain's agent is installed.
   *
   * @return bool
   *   TRUE if the agent is installed.
   */
  public function isInstalled();

  /**
   * Find out if domain uses the default port.
   *
   * @return bool
   *   TRUE if the default port (80 for http or 443 for https) is used.
   */
  public function isDefaultPort();

  /**
   * Get a token which contains all details for configuring a remote domain.
   *
   * @var string|bool $redirect
   *   FALSE if the remote domain should not redirect after configuration has
   *   been completed, e.g. if Drush is being used. Otherwise provide a URL to
   *   which the remote domain should redirect when configuration has been
   *   completed.
   *
   * @return string
   *   The configuration token.
   */
  public function getRemoteSetupToken($redirect);

  /**
   * Create a renderable link to initiate a remote user session.
   *
   * @var string $label
   *   The label for the link.
   *
   * @return string
   *   The rendered link.
   */
  public function getRemoteLoginLink($label);

  /**
   * Get a URL which the remote domain should redirect to after configuration.
   *
   * @var bool $initial
   *   Whether this is the initial setup or not.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL after remote configuration.
   */
  public function getRemoteSetupRedirect($initial = FALSE);

  /**
   * Get a rendered link to get to the remote configuration form.
   *
   * @var string $label
   *   The label to show in the link
   * @var bool $initial
   *   Whether this is the initial setup or not.
   *
   * @return string
   *   The URL to get to the remote configuration form.
   */
  public function getRemoteSetupLink($label, $initial = FALSE);

  /**
   * Get a local domain name used temporarily for updates.
   *
   * @return string
   *   A temporary domain name.
   */
  public function getLocalUrl();

  /**
   * Call the session action to reveice a URL which starts a new remote session.
   *
   * @return string
   *   The session URL.
   */
  public function getSessionUrl();

  /**
   * Received the remote database.
   *
   * @return string[]
   *   List of filenames of downloaded database files.
   */
  public function database();

  /**
   * Download a remote file.
   *
   * @param string $source
   *   Remote filename.
   * @param string $destination
   *   Local filename.
   *
   * @return bool
   *   TRUE if operation was successful.
   */
  public function download($source, $destination);

  /**
   * Ping the remote domain.
   *
   * @return bool
   *   TRUE if operation was successful.
   */
  public function ping();

  /**
   * Receive and store remote information.
   *
   * @return array
   *   Array of received information.
   */
  public function remoteInfo();

  /**
   * Initialize core for a new domain.
   *
   * @param CoreInterface $core
   *   The core to be initialized.
   *
   * @throws \Exception
   */
  public function initCore(CoreInterface $core);

  /**
   * Initialize authentication and crypt settings with default values.
   *
   * @param string $name
   *   Name of the domain.
   * @param string $crypt
   *   Crypt method.
   * @param array $crypt_setting
   *   Crypt settings.
   */
  public function initValues($name, $crypt = NULL, array $crypt_setting = []);

  /**
   * Callback to retrieve all domains from the remote Drupal installation.
   *
   * @param CoreInterface $core
   *   Retrieve all domains for the given core where the current domain entity
   *   is also allocated to that same core.
   */
  public function retrieveAllDomains(CoreInterface $core);

  /**
   * Set domain aliases.
   *
   * @param array $aliase
   *   List of domain aliases.
   *
   * @return $this
   */
  public function setAliase(array $aliase);

  /**
   * Recheck the URL to get SSL setting and port.
   *
   * @param string $url
   *   URL to check.
   *
   * @return $this
   */
  public function updateScheme($url);

  /**
   * Set project releases being used by this domain.
   *
   * @param \Drupal\drd\Entity\ReleaseInterface[] $releases
   *   List of release entities.
   *
   * @return $this
   */
  public function setReleases(array $releases);

  /**
   * Get project releases being used by this domain.
   *
   * @return \Drupal\drd\Entity\ReleaseInterface[]
   *   List of releases.
   */
  public function getReleases();

  /**
   * Get messages being created during remote actions.
   *
   * @return array
   *   List of messages.
   */
  public function getMessages();

  /**
   * Get the latest security review.
   *
   * @return array
   *   Renderable array of latest security review.
   */
  public function getReview();

  /**
   * Get the latest monitoring review.
   *
   * @return array
   *   Json array of monitoring review.
   */
  public function getMonitoring();

  /**
   * Get the latest query results from cache.
   *
   * @return array
   *   Contains the keys 'query', 'info', 'headers' and 'rows' which can be
   *   rendered as a table or any other sort of output.
   */
  public function getQueryResult();

  /**
   * Get the current maintenance mode.
   *
   * @param bool $refresh
   *   Whether to refresh status from remote.
   *
   * @return bool
   *   TRUE if maintenance mode is turned on.
   */
  public function getMaintenanceMode($refresh = TRUE);

  /**
   * Get latest Ping status.
   *
   * @param bool $refresh
   *   Whether to refresh status from remote.
   *
   * @return bool|null
   *   TRUE if the remote domain responds properly, FALSE if it doesn't and NULL
   *   if we don't get any response and hence don't know the status.
   */
  public function getLatestPingStatus($refresh = TRUE);

  /**
   * Get a remote block.
   *
   * @param string $module
   *   Module from which to receive a block.
   * @param string $delta
   *   ID of the block within that module.
   * @param bool $refresh
   *   Whether to refresh status from remote.
   *
   * @return bool|string
   *   FALSE if we couldn't receive the block or the rendered result as a string
   *   if it's been successful.
   */
  public function getRemoteBlock($module, $delta, $refresh = TRUE);

  /**
   * Get all remote settings.
   *
   * @return array
   *   List of all remote settings.
   */
  public function getRemoteSettings();

  /**
   * Get all remote GLOBAL variables.
   *
   * @return array
   *   List of all remote GLOBALS.
   */
  public function getRemoteGlobals();

  /**
   * Get remote requirements - in other words the status report.
   *
   * @return array
   *   Renderable status report from remote domain.
   */
  public function getRemoteRequirements();

  /**
   * Store remote messages in cache.
   *
   * @param array $messages
   *   List of remote messages.
   *
   * @return $this
   */
  public function cacheRemoteMessages(array $messages);

  /**
   * Store remote maintenance mode status in cache.
   *
   * @param bool $mode
   *   The current state.
   *
   * @return $this
   */
  public function cacheMaintenanceMode($mode);

  /**
   * Store remote block in cache.
   *
   * @param string $module
   *   The module that provides that block.
   * @param string $delta
   *   The ID of the block within that module.
   * @param string $content
   *   The rendered content of the block.
   *
   * @return $this
   */
  public function cacheBlock($module, $delta, $content);

  /**
   * Cache the ping results.
   *
   * @param bool|null $status
   *   The current ping response status. TRUE or FALSE if the domain responds
   *   OK or not and NULL if it doesn't respond at all.
   *
   * @return $this
   */
  public function cachePingResult($status);

  /**
   * Store remote error log in cache.
   *
   * @param string $log
   *   The error log.
   *
   * @return $this
   */
  public function cacheErrorLog($log);

  /**
   * Store remote requirements (status report) in cache.
   *
   * @param array $requirements
   *   Renderable status report.
   *
   * @return $this
   */
  public function cacheRequirements(array $requirements);

  /**
   * Store remote variables in cache.
   *
   * @param array $variables
   *   List of remote variables.
   *
   * @return $this
   */
  public function cacheVariables(array $variables);

  /**
   * Store remote GLOBALS in cache.
   *
   * @param array $globals
   *   List of remote GLOBALS.
   *
   * @return $this
   */
  public function cacheGlobals(array $globals);

  /**
   * Store remote settings in cache.
   *
   * @param array $settings
   *   List of remote settings.
   *
   * @return $this
   */
  public function cacheSettings(array $settings);

  /**
   * Store remote security review in cache.
   *
   * @param array $review
   *   Renderable security review results.
   *
   * @return $this
   */
  public function cacheReview(array $review);

  /**
   * Store remote monitoring in cache.
   *
   * @param array $monitoring
   *   Json formatted monitoring results.
   *
   * @return $this
   */
  public function cacheMonitoring(array $monitoring);

  /**
   * Store remote SQL query and result in cache.
   *
   * @param string $query
   *   The query that has been executed.
   * @param string $info
   *   Some information about the query result, e.g. "Nothing found".
   * @param array $headers
   *   The column headers for the results.
   * @param array $rows
   *   The query results as an array containing a result array for each row.
   *
   * @return $this
   */
  public function cacheQueryResult($query, $info, array $headers, array $rows);

  /**
   * Reset crypt settings.
   *
   * Set Crypt method and Cipher to the strongest available values and generate
   * a new key for that new crypto setting.
   *
   * @return $this
   */
  public function resetCryptSettings();

  /**
   * Reset domain.
   *
   * Reset the domain so that new authorization with remote site gets available
   * in the UI again.
   *
   * @return $this
   */
  public function reset();

  /**
   * Render Ping status.
   *
   * @return string
   *   Rendered string to indicate the ping status of the domain.
   */
  public function renderPingStatus();

}
