<?php
/**
 * @file
 * Contains \Drupal\adv_varnish\Varnish.
 */

namespace Drupal\adv_varnish;

use Drupal\Core\Session\AccountProxyInterface;

/**
 * Main class provide basic methods to work with Varnish.
 */
class Varnish implements VarnishInterface {

  /**
   * @var $getStatusResults
   *   Varnish terminal status.
   */
  public static $getStatusResults;

  /**
   * @var $account
   *   Instance of AccountProxyInterface.
   */
  protected $account;

  /**
   * @var array
   *   Configuration settings.
   */
  protected $configuration;

  /**
   * Class constructor.
   *
   * @param AccountProxyInterface $account
   * @param VarnishConfiguratorInterface $configuration
   */
  public function __construct(AccountProxyInterface $account, VarnishConfiguratorInterface $configuration) {
    $this->account = $account;
    $this->configuration = $configuration;
  }

  /**
   * Parse the host from the global $base_url.
   *
   * @return string
   *   Varnish host.
   */
  public function varnishGetHost() {
    global $base_url;
    $parts = parse_url($base_url);
    return $parts['host'];
  }

  /**
   * Execute varnish command and get response.
   *
   * @param string $client
   *   Terminal settings.
   * @param string $command
   *   Command line to execute.
   *
   * @return mixed
   *   Result of executed command.
   */
  public function varnishExecuteCommand($client, $command) {

    // Send command and get response.
    socket_write($client, "$command\n");
    $status = $this->varnishReadSocket($client);
    if ($status['code'] != 200) {
      return FALSE;
    }
    else {

      // Successful connection.
      return $status;
    }
  }

  /**
   * Low-level socket read function.
   *
   * @params
   *   $client an initialized socket client
   *
   *   $retry how many times to retry on "temporarily unavailable" errors.
   *
   * @return array
   *   Response array.
   */
  public function varnishReadSocket($client, $retry = 2) {
    // Status and length info is always 13 characters.
    $header = socket_read($client, 13, PHP_BINARY_READ);
    if ($header == FALSE) {
      $error = socket_last_error();
      // 35 = socket-unavailable, so it might be blocked from our write.
      // This is an acceptable place to retry.
      if ($error == 35 && $retry > 0) {
        return $this->varnishReadSocket($client, $retry - 1);
      }
      else {
        return array(
          'code' => $error,
          'msg' => socket_strerror($error),
        );
      }
    }
    $msg_len = (int) substr($header, 4, 6) + 1;
    $status = array(
      'code' => substr($header, 0, 3),
      'msg' => socket_read($client, $msg_len, PHP_BINARY_READ),
    );
    return $status;
  }

  /**
   * Sends commands to Varnish.
   *
   * @param mixed $commands
   *    Array of commands to execute.
   *
   * @return array
   *   Result status.
   */
  public function varnishTerminalRun($commands) {
    if (!extension_loaded('sockets')) {
      // Prevent fatal errors if people don't have requirements.
      return FALSE;
    }
    // Convert single commands to an array so we
    // can handle everything in the same way.
    if (!is_array($commands)) {
      $commands = array($commands);
    }
    $ret = array();
    $terminals = explode(' ', $this->getSetting('connection', 'control_terminal', '127.0.0.1:6082'));
    // The variable varnish_socket_timeout defines the timeout in milliseconds.
    $timeout = $this->getSetting('connection', 'socket_timeout', 100);
    $seconds = (int) ($timeout / 1000);
    $microseconds = (int) ($timeout % 1000 * 1000);
    foreach ($terminals as $terminal) {
      list($server, $port) = explode(':', $terminal);
      $client = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
      socket_set_option($client, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $seconds, 'usec' => $microseconds));
      socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $seconds, 'usec' => $microseconds));
      if (@!socket_connect($client, $server, $port)) {
        $ret[$terminal] = FALSE;

        // If a varnish server is unavailable, move on to the next in the list.
        continue;
      }

      // If there is a CLI banner message (varnish >= 2.1.x),
      // try to read it and move on.
      $varnish_version = \Drupal::config('adv_varnish.settings')->get('varnish_version');
      if (!$varnish_version) {
        $varnish_version = 2.1;
      }
      if (floatval($varnish_version) > 2.0) {
        $status = $this->varnishReadSocket($client);
        // Do we need to authenticate?
        if ($status['code'] == 107) {
          $secret = $this->getSetting('connection', 'control_key', '');
          $challenge = substr($status['msg'], 0, 32);
          $pack = $challenge . "\x0A" . $secret . "\x0A" . $challenge . "\x0A";
          $key = hash('sha256', $pack);
          socket_write($client, "auth $key\n");
          $status = $this->varnishReadSocket($client);
          if ($status['code'] != 200) {
          }
        }
      }
      foreach ($commands as $command) {
        if ($status = $this->varnishExecuteCommand($client, $command)) {
          $ret[$terminal][$command] = $status;
        }
      }
    }
    return $ret;
  }

  /**
   * Get the status (up/down) of each of the varnish servers.
   *
   * @return array
   *    An array of server statuses, keyed by varnish terminal addresses.
   */
  public function varnishGetStatus() {
    // Use a static-cache so this can be called repeatedly without incurring
    // socket-connects for each call.
    $results = (isset(self::$getStatusResults)) ? self::$getStatusResults : NULL;

    if (is_null($results)) {
      $results = array();
      $status = $this->varnishTerminalRun(array('status'));
      $terminals = explode(' ', $this->getSetting('connection', 'control_terminal', '127.0.0.1:6082'));
      foreach ($terminals as $terminal) {
        $stat = array_shift($status);
        $results[$terminal] = ($stat['status']['code'] == 200);
      }
    }

    return $results;
  }

  /**
   * Return module settings.
   *
   * @param string $block
   *    Setting block.
   * @param string $setting
   *    Setting key.
   * @param string $default
   *    Default setting value.
   *
   * @return mixed
   *    Setting value by key.
   */
  public function getSetting($block, $setting, $default = NULL) {

    $setting = $block . '.' . $setting;
    $config = $this->configuration->get($setting);
    $result = !empty($config)
      ? $config
      : $default;

    return $result;
  }

  /**
   * Purge varnish cache for specific tag.
   *   *
   * @param $tag
   *   (string/array) tag to search and purge.
   *
   * @param $header
   *   (
   * @return array
   */
  public function purgeTags($tag, $header = 'X-TAG') {

    // Build pattern.
    $pattern = (count($tag) > 1)
        ? implode(';|', $tag) . ';'
        : reset($tag) . ';';

    // Remove quotes from pattern.
    $pattern = strtr($pattern, array('"' => '', "'" => ''));

    // Clean all or only current host.
    if ($this->getSetting('purge', 'all_hosts', TRUE)) {
      $command_line = "ban obj.http.$header ~ \"$pattern\"";
    }
    else {
      $host = $this->varnishGetHost();
      $command_line = "ban req.http.host ~ $host && obj.http.$header ~ \"$pattern\"";
    }

    // Log action.
    if ($this->getSetting('general', 'logging', FALSE)) {

    }

    // Query Varnish.
    $res = $this->varnishTerminalRun(array($command_line));
    return $res;
  }

  /**
   * Purge varnish cache for specific request, like '/sites/all/files/1.txt';
   *
   * @param $pattern
   *   (string/array) list of tags to search and purge.
   * @param $exact
   *   (bool) specify if pattern regex or exact match string.
   *
   * @return array
   */
  function purgeRequest($pattern, $exact = FALSE) {

    // Remove quotes from pattern.
    $pattern = strtr($pattern, array('"' => '', "'" => ''));
    $command = !empty($exact) ? '==' : '~';

    // Clean all or only current host.
    if ($this->getSetting('purge', 'all_hosts', TRUE)) {
      $command_line = "ban req.url $command \"$pattern\"";
    }
    else {
      $host = $this->varnishGetHost();
      $command_line = "ban req.http.host ~ $host && req.url $command \"$pattern\"";
    }

    // Log action.
    if ($this->getSetting('general', 'logging', FALSE)) {
      $message = t('u=@uid purge !command_line', [
        '@uid' => $this->account->id(),
        '!command_line' => $command_line,
      ]);
    }

    // Query Varnish.
    $res = $this->varnishTerminalRun(array($command_line));
    return $res;
  }

}
