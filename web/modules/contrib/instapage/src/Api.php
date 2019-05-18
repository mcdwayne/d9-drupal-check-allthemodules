<?php

namespace Drupal\instapage;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Api.
 *
 * This class contains all the necessary
 * functions to communicate with Instapage.
 *
 * @package Drupal\instapage
 */
class Api {

  private $client;
  private $config;
  private $pagesConfig;
  const ENDPOINT = 'http://app.instapage.com';
  const METHOD = 'POST';

  /**
   * Api constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(ConfigFactory $config, Client $client) {
    $this->config = $config->getEditable('instapage.settings');
    $this->pagesConfig = $config->getEditable('instapage.pages');
    $this->client = $client;
  }

  /**
   * Creates and returns a new instance of the service.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Sends out an API call and returns the results.
   *
   * @param string $action
   * @param array $headers
   * @param array $params
   *
   * @return array|bool
   */
  public function createRequest($action = '', $headers = [], $params = []) {
    $headers['integration'] = 'drupal';
    try {
      $request = $this->client->request(
        self::METHOD,
        self::ENDPOINT . '/api/plugin/page' . $action,
        [
          'allow_redirects' => [
            'max' => 5,
          ],
          'connect_timeout' => 45,
          'synchronous' => TRUE,
          'version' => '1.0',
          'form_params' => $params,
          'headers' => $headers,
        ]
      );
      if ($request->getStatusCode() === 200) {
        $headers = $request->getHeaders();
        return [
          'body' => (string) $request->getBody(),
          'status' => $request->getReasonPhrase(),
          'code' => $request->getStatusCode(),
          'headers' => $headers,
        ];
      }
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Save a user in config and register him through the API.
   *
   * @param $email
   * @param $token
   */
  public function registerUser($email, $token) {
    $this->config->set('instapage_user_id', $email);
    $this->config->set('instapage_user_token', $token);
    $this->config->save();
    $this->connectKeys($token);
  }

  /**
   * Verify the user email and password.
   *
   * @param $email
   * @param $password
   *
   * @return array
   */
  public function authenticate($email, $password) {
    $reponse = $this->createRequest('', [], [
      'email' => $email,
      'password' => $password,
    ]);
    if ($reponse && $reponse['code'] == 200) {
      $decoded = json_decode($reponse['body']);
      return ['status' => 200, 'content' => $decoded->data->usertoken];
    }
    return ['error' => TRUE, 'content' => t('Login failed.')];
  }

  /**
   * Callback for getting the account keys.
   *
   * @param $token
   *
   * @return array
   */
  public function getAccountKeys($token) {
    $reponse = $this->createRequest('/get-account-keys', ['usertoken' => $token], ['ping' => TRUE]);
    if ($reponse && $reponse['code'] == 200) {
      $decoded = json_decode($reponse['body']);
      return ['status' => 200, 'content' => $decoded->data->accountkeys];
    }
    return ['error' => TRUE, 'content' => t('Login failed.')];
  }

  /**
   * Callback for getting a list of all pages.
   *
   * @param $token
   *
   * @return array|mixed
   */
  public function getPageList($token) {
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      $response = $this->createRequest('/list', ['accountkeys' => $encoded], ['ping' => TRUE]);
      $decoded = json_decode($response['body']);
      $data = [];

      // Fetch available subaccounts from the API.
      $subAccounts = $this->getSubAccounts($token);
      if (!empty($decoded->data)) {
        foreach ($decoded->data as $item) {
          $data[$item->id] = $item->title;

          // If possible add the subaccount label in brackets.
          if (isset($item->subaccount) && array_key_exists($item->subaccount, $subAccounts)) {
            $data[$item->id] .= ' (' . $subAccounts[$item->subaccount] . ')';
          }
        }
      }
      // Save page labels in config.
      $this->pagesConfig->set('page_labels', $data)->save();
      return $decoded;
    }
    return ['error' => TRUE, 'content' => t('Login failed.')];
  }

  /**
   * Returns encoded account keys.
   *
   * @param $token
   *
   * @return bool|string
   */
  public function getEncodedKeys($token) {
    $keys = $this->getAccountKeys($token);
    if (isset($keys['status']) && $keys['status'] == 200) {
      return base64_encode(json_encode($keys['content']));
    }
    return FALSE;
  }

  /**
   * Callback to edit a page.
   *
   * @param $page_id
   * @param $path
   * @param $token
   */
  public function editPage($page_id, $path, $token, $publish = 1) {
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      $headers = [
        'accountkeys' => $encoded,
      ];
      $params = [
        'page' => $page_id,
        'url' => $path,
        'publish' => $publish,
      ];
      $this->createRequest('/edit', $headers, $params);

      // Get existing page paths from config.
      $pages = $this->pagesConfig->get('instapage_pages');

      // Publishing a page.
      if ($publish) {
        $pages[$page_id] = $path;
      }
      else {
        // When unpublishing a page remove it from config.
        if (array_key_exists($page_id, $pages)) {
          unset($pages[$page_id]);
        }
      }
      // Save new page paths to config.
      $this->pagesConfig->set('instapage_pages', $pages)->save();
    }
  }

  /**
   * API call to connect current domain to Drupal publishing on Instapage.
   *
   * @param $token
   */
  public function connectKeys($token) {
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      $domain = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
      $headers = [
        'accountkeys' => $encoded,
      ];
      $params = [
        'accountkeys' => $encoded,
        'status' => 'connect',
        'domain' => $domain,
      ];
      $this->createRequest('/connection-status', $headers, $params);
    }
  }

  /**
   * Fetch the subaccounts from the API.
   *
   * @param $token
   *
   * @return array
   */
  public function getSubAccounts($token) {
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      $headers = [
        'accountkeys' => $encoded,
      ];
      $reponse = $this->createRequest('/get-sub-accounts-list', $headers);
      if ($reponse && $reponse['code'] == 200) {
        $decode = json_decode($reponse['body']);
        $accounts = [];
        // Create array of subaccounts and return it.
        foreach ($decode->data as $item) {
          $accounts[$item->id] = $item->name;
        }
        return $accounts;
      }
    }
    return [];
  }

}
