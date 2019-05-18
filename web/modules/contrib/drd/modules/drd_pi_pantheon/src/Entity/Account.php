<?php

namespace Drupal\drd_pi_pantheon\Entity;

use Drupal\drd\Agent\Action\Base as ActionBase;
use Drupal\drd_pi\DrdPiAccount;
use Drupal\drd_pi\DrdPiCore;
use Drupal\drd_pi\DrdPiDomain;
use Drupal\drd_pi\DrdPiHost;
use GuzzleHttp\Client;

/**
 * Defines the Pantheon Account entity.
 *
 * @ConfigEntityType(
 *   id = "pantheon_account",
 *   label = @Translation("Pantheon Account"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\drd_pi\DrdPiAccountListBuilder",
 *     "form" = {
 *       "add" = "Drupal\drd_pi_pantheon\Entity\AccountForm",
 *       "edit" = "Drupal\drd_pi_pantheon\Entity\AccountForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "pantheon_account",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/drd/settings/pantheon/accounts/{pantheon_account}",
 *     "add-form" = "/drd/settings/pantheon/accounts/add",
 *     "edit-form" = "/drd/settings/pantheon/accounts/{pantheon_account}/edit",
 *     "delete-form" = "/drd/settings/pantheon/accounts/{pantheon_account}/delete",
 *     "collection" = "/drd/settings/pantheon/accounts"
 *   }
 * )
 */
class Account extends DrdPiAccount implements AccountInterface {

  const ENDPOINT     = 'https://terminus.pantheon.io:443/api/';
  const CLIENT       = 'terminus';
  const USER_AGENT   = 'Terminus/1.6.0 (php_version=' . PHP_VERSION . '&script=bin/terminus';
  const CONTENT_TYPE = 'application/json';

  private $authenticated = FALSE;
  private $authentication;

  /**
   * {@inheritdoc}
   */
  public function getEncryptedFieldNames() {
    return [
      'machine_token',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getModuleName() {
    return 'drd_pi_pantheon';
  }

  /**
   * {@inheritdoc}
   */
  public static function getConfigName() {
    return self::getModuleName() . '.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformName() {
    return 'Pantheon';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineToken() {
    return $this->getDecrypted('machine_token');
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineToken($machineToken) {
    $this->setEncrypted('machine_token', $machineToken);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationMethod() {
    return ActionBase::SEC_AUTH_PANTHEON;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationSecrets(DrdPiDomain $domain) {
    return [
      'PANTHEON_SITE' => $domain->host()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformHosts() {
    $this->hosts = [];

    $this->auth();
    if ($result = $this->request('users/' . $this->authentication->user_id . '/memberships/sites')) {
      foreach ($result as $item) {
        if (empty($item->site->frozen) && in_array($item->site->framework, ['drupal', 'drupal8'])) {
          $name = implode(' ', [
            $this->getPlatformName(),
            $this->label(),
            $item->site->name,
          ]);
          $this->hosts[$item->site->id] = new DrdPiHost($this, $name, $item->site->id);
        }
      }
    }
    return $this->hosts;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformCores(DrdPiHost $host) {
    $this->cores = [];

    $sites = $this->request('sites/' . $host->id());
    if ($result = $this->request('sites/' . $host->id() . '/environments')) {
      foreach ($result as $id => $item) {
        if ($item->is_initialized) {
          $core = new DrdPiCore($this, $id, $id);
          $core->setHost($host);
          $domain = new DrdPiDomain($this, $id, $id);

          // Check if the domainn ame redirects to grab the real domain name.
          $domainname = "{$id}-{$sites->name}.{$item->dns_zone}";
          $client = new Client(['base_uri' => 'https://' . $domainname]);
          $response = $client->request('HEAD', NULL, ['allow_redirects' => FALSE]);
          $locaction = $response->getHeader('Location');
          if (!empty($locaction)) {
            $domainname = parse_url(array_pop($locaction), PHP_URL_HOST);
          }

          $domain->setDetails($core, $domainname);
          $core->addDomain($domain);
          $this->cores[$id] = $core;
        }
      }
    }
    return $this->cores;
  }

  /**
   * Authenticate remotely if required.
   */
  private function auth() {
    if ($this->authenticated) {
      return;
    }
    $this->authenticated = TRUE;
    $this->authentication = $this->post('authorize/machine-token', ['machine_token' => $this->getMachineToken()]);
  }

  /**
   * Send a POST request with form values in $form.
   *
   * @param string $path
   *   API path to post to.
   * @param array $form
   *   Array with form values to post.
   *
   * @return object
   *   Object with values from the response.
   */
  private function post($path, array $form) {
    $form['client'] = self::CLIENT;
    return $this->request($path, ['body' => json_encode($form)], 'POST', FALSE);
  }

  /**
   * Send a request to the API endpoint.
   *
   * @param string $path
   *   API path to send the request to.
   * @param array $options
   *   Options for the request.
   * @param string $method
   *   Request method, GET or POST.
   * @param bool $auth_first
   *   Whether to authenticate first.
   *
   * @return object
   *   Object with values from the response.
   */
  private function request($path, array $options = [], $method = 'GET', $auth_first = TRUE) {
    if ($auth_first) {
      $this->auth();
    }
    $options['headers']['Content-type'] = self::CONTENT_TYPE;
    $options['headers']['User-Agent'] = self::USER_AGENT;
    if ($this->authenticated) {
      $options['headers']['Authorization'] = 'Bearer ' . $this->authentication->session;
    }
    $client = new Client(['base_uri' => self::ENDPOINT . $path]);
    $response = $client->request($method, NULL, $options);
    return json_decode($response->getBody()->getContents());
  }

}
