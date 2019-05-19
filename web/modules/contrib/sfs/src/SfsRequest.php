<?php
namespace Drupal\sfs;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class SfsRequest {
  
  use StringTranslationTrait;
  
  /**
   * @var \Drupal\Core\Config\Config;
   */
  protected $config;
  
  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;
  
  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;
  
  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  
  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;
  
  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;
  
  /**
   * SfsRequest constructor.
   * 
   * @param ConfigFactoryInterface $config_factory
   * @param AccountInterface $current_user
   * @param LoggerChannelFactoryInterface $logger
   * @param ClientInterface $http_client
   * @param Connection $connection
   * @param CacheBackendInterface $cache_backend
   */
  public function __construct(
    ConfigFactoryInterface $config_factory, 
    AccountInterface $current_user, 
    LoggerChannelFactoryInterface $logger, 
    ClientInterface $http_client, 
    Connection $connection, 
    CacheBackendInterface $cache_backend
    ) {
      $this->config = $config_factory->getEditable('sfs.settings');
      $this->account = $current_user;
      $this->log = $logger->get('sfs');
      $this->httpClient = $http_client;
      $this->connection = $connection;
      $this->cacheBackend = $cache_backend;
  }
  
  /**
   * Check registered user accounts of being spammers at www.stopforumspam.com.
   */
  public function checkUsers() {
    if (!$this->config->get('sfs_cron_job')) {
      return FALSE;
    }
    $lastUid = $this->config->get('sfs_cron_last_uid');
    $limit = $this->config->get('sfs_cron_account_limit');
    if ($limit > 0) {
      $query = $this->connection->select('users', 'e');
      $query->fields('e', ['uid']);
      $query->condition('uid', $lastUid, '>');
      $query->range(0, $limit);
      $query->orderBy('uid', 'ASC');
      $uids = $query->execute()->fetchCol();
      foreach ($uids as $uid) {
        $lastUid = $uid;
        $user = User::load($uid);
        $include = ($user->isActive() || $this->config->get('sfs_cron_blocked_accounts'));
        if ($include && !$user->hasPermission('exclude from sfs scans') && $this->userIsSpammer($user)) {
          try {
            $user->block();
            $user->save();
            $this->log->notice('User acount @uid has been disabled.', ['@uid' => $uid]);
          }
          catch (EntityStorageException $e) {
            $this->log->error('Failed to disable user acount @uid: @error', ['@uid' => $uid, '@error' => $e->getMessage()]);
          }
        }
      }
      $this->config->set('sfs_cron_last_uid', $lastUid);
      $this->config->save();
    }
    return TRUE;
  }
  
  /**
   * @param User $user
   * @return boolean
   */
  public function userIsSpammer(User $user) {
    $name = $user->getAccountName();
    $mail = $user->getEmail();
    $ips = $this->getUserIpAddresses($user->id());
    
    if (empty($ips)) {
      return $this->isSpammer($name, $mail, NULL);
    }
    else {
      foreach ($ips as $ip) {
        if ($this->isSpammer($name, $mail, $ip)) {
          return TRUE;
        }
      }
    }
    
    return FALSE;
  }
  
  /**
   * @param int $uid
   * @return array
   */
  public function getUserIpAddresses($uid) {
    $hostnames = [];
    
    // Retrieve IP addresses from still available sessions.
    $query = $this->connection->select('sessions', 'e');
    $query->fields('e', ['hostname']);
    $query->condition('uid', $uid, '=');
    $ips = $query->execute()->fetchCol();
    
    $hostnames = array_merge($hostnames, $ips);
    
    // Retrieve IP addresses saved during adding content
    $query = $this->connection->select('sfs_hostname', 'e');
    $query->fields('e', ['hostname']);
    $query->condition('uid', $uid, '=');
    $ips = $query->execute()->fetchCol();
    
    $hostnames = array_merge($hostnames, $ips);
    
    // Retrieve IP addresses from comments
    $query = $this->connection->select('comment_field_data', 'e');
    $query->fields('e', ['hostname']);
    $query->condition('uid', $uid, '=');
    $ips = $query->execute()->fetchCol();
    
    $hostnames = array_merge($hostnames, $ips);
    $hostnames = array_unique($hostnames);
    
    return $hostnames;
  }
  
  /**
   * @param string $username
   * @param string $mail
   * @param string $ip
   * @return boolean
   */
  public function isSpammer($username = NULL, $mail = NULL, $ip = NULL) {
    if ($this->account->hasPermission('exclude from sfs scans')) {
      return FALSE;
    }
    $usernameThreshold = $this->config->get('sfs_criteria_username');
    $emailThreshold = $this->config->get('sfs_criteria_email');
    $ipThreshold = $this->config->get('sfs_criteria_ip');
    
    $request = [];
    if (!empty($username) && $usernameThreshold > 0 && !$this->isWhitelisted('username', $username)) {
      $request['username'] = $username;
    }
    if (!empty($mail) && $emailThreshold > 0 && !$this->isWhitelisted('email', $mail)) {
      $request['email'] = $mail;
    }
    if (!empty($ip) && $ipThreshold > 0 && !$this->isWhitelisted('ip', $ip)) {
      if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $this->log->warning('Invalid IP address: @ip. Spambot will not rely on it.', ['@ip' => $ip]);
      }
      else {
        $request['ip'] = $ip;
      }
    }
      
    if ($request) {
      $data = $this->requestCache($request);
      if ($data) {
        $json = Json::decode($data);
        $usernameSpam = ($usernameThreshold > 0 && !empty($json['username']['appears']) && $json['username']['frequency'] >= $usernameThreshold);
        $emailSpam = ($emailThreshold > 0 && !empty($json['email']['appears']) && $json['email']['frequency'] >= $emailThreshold);
        $ipSpam = ($ipThreshold > 0 && !empty($json['ip']['appears']) && $json['ip']['frequency'] >= $ipThreshold);
        if ($usernameSpam || $emailSpam || $ipSpam) {
          return TRUE;
        }
      }
    }
    
    return FALSE;
  }
  
  /**
   * Retrieve the sfsRequest from the cache.
   * 
   * @param array $request
   * @return boolean|string
   */
  protected function requestCache($request) {
    if (empty($request)) {
      return FALSE;
    }
    $queryString = urldecode(http_build_query($request, '', '&')) . '&json';
    $cid = 'sfs:' . $queryString;
    
    $cache = FALSE;
    $cacheDuration = $this->config->get('sfs_cache_duration');
    if ($cacheDuration) {
      $cache = $this->cacheBackend->get($cid);
    }
    
    if ($cache) {
      $data = $cache->data;
      if ($this->config->get('sfs_log_found_in_cache')) {
        $this->log->notice("Found in cache: %query %data", ['%query' => $queryString, '%data' => $data]);
      }
    }
    else {
      $data = $this->sfsRequest($queryString);
      if ($data) {
        $json = Json::decode($data);
        if (empty($json['success'])) {
          $this->log->warning("Request unsuccessful: %query %data", ['%query' => $queryString, '%data' => $data]);
          return FALSE;
        }
        elseif ($cacheDuration) {
          $this->cacheBackend->set($cid, $data, time() + $cacheDuration);
        }
      }
    }
    return $data;
  }
  
  /**
   * @param string $queryString
   * @return string|boolean
   */
  protected function sfsRequest($queryString) {
    if ($this->config->get('sfs_http_secure')) {
      $url = 'https://www.stopforumspam.com/api?';
    }
    else {
      $url = 'http://www.stopforumspam.com/api?';
    }
    $url .= $queryString;
    $options = [
      'headers' => [
        'Accept' => 'application/json',
      ],
    ];
    try {
      $response = $this->httpClient->request('GET', $url, $options);
      $data = (string) $response->getBody()->getContents();
      if ($this->config->get('sfs_log_successful_request')) {
        $this->log->notice("Success: %query %data", ['%query' => $queryString, '%data' => $data]);
      }
      return $data;
    }
    catch (GuzzleException $e) {
      $this->log->error("Error contacting service: %url Error: %error", ['%url' => $url, '%error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Get hostname for entity type that do not safe IP address by default.
   *
   * @param int $id
   * @param string $type
   *
   * @return string|bool
   */
  public function getHostname($id, $type) {
    $query = $this->connection->select('sfs_hostname', 'e');
    $query->fields('e', ['hostname']);
    $query->condition('entity_id', $id, '=');
    $query->condition('entity_type', $type, '=');
    $result = $query->execute()->fetchField();
    
    return $result;
  }
  
  /**
   * @param string $type
   * @param string $value
   * @return boolean
   */
  protected function isWhitelisted($type, $value) {
    switch ($type) {
      case 'ip':
        $whitelist = $this->config->get('sfs_whitelist_ips');
        $result = strpos($whitelist, $value) !== FALSE;
        break;
      case 'email':
        $whitelist = $this->config->get('sfs_whitelist_emails');
        $result = stripos($whitelist, $value) !== FALSE;
        break;
      case 'username':
        $whitelist = $this->config->get('sfs_whitelist_usernames');
        $result = strpos($whitelist, $value) !== FALSE;
        break;
      default:
        $result = FALSE;
        break;
    }
    return $result;
  }
}
