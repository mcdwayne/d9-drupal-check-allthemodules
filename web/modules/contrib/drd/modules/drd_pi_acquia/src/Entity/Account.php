<?php

namespace Drupal\drd_pi_acquia\Entity;

use Drupal\drd\Agent\Action\Base as ActionBase;
use Drupal\drd_pi\DrdPiAccount;
use Drupal\drd_pi\DrdPiCore;
use Drupal\drd_pi\DrdPiDomain;
use Drupal\drd_pi\DrdPiHost;
use GuzzleHttp\Client;

/**
 * Defines the acquia Account entity.
 *
 * @ConfigEntityType(
 *   id = "acquia_account",
 *   label = @Translation("Acquia Account"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\drd_pi\DrdPiAccountListBuilder",
 *     "form" = {
 *       "add" = "Drupal\drd_pi_acquia\Entity\AccountForm",
 *       "edit" = "Drupal\drd_pi_acquia\Entity\AccountForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "acquia_account",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/drd/settings/acquia/accounts/{acquia_account}",
 *     "add-form" = "/drd/settings/acquia/accounts/add",
 *     "edit-form" = "/drd/settings/acquia/accounts/{acquia_account}/edit",
 *     "delete-form" = "/drd/settings/acquia/accounts/{acquia_account}/delete",
 *     "collection" = "/drd/settings/acquia/accounts"
 *   }
 * )
 */
class Account extends DrdPiAccount implements AccountInterface {

  const ENDPOINT = 'https://cloudapi.acquia.com/v1/';

  /**
   * {@inheritdoc}
   */
  public function getEncryptedFieldNames() {
    return [
      'private_key',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getModuleName() {
    return 'drd_pi_acquia';
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
    return 'Acquia';
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('email');
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateKey() {
    return $this->getDecrypted('private_key');
  }

  /**
   * {@inheritdoc}
   */
  public function setPrivateKey($privateKey) {
    $this->setEncrypted('private_key', $privateKey);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationMethod() {
    return ActionBase::SEC_AUTH_ACQUIA;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationSecrets(DrdPiDomain $domain) {
    $secrets = [];
    if ($result = $this->curl('sites/' . $domain->host()->id() . '/envs/' . $domain->id() . '/dbs')) {
      $info = reset($result);
      $secrets = [
        'username' => $info->username,
        'password' => $info->password,
      ];
    }
    return $secrets;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformHosts() {
    $this->hosts = [];

    if ($result = $this->curl('sites')) {
      foreach ($result as $item) {
        $name = implode(' ', [
          $this->getPlatformName(),
          $this->label(),
          $item,
        ]);
        $this->hosts[$item] = new DrdPiHost($this, $name, $item);
      }
    }

    return $this->hosts;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformCores(DrdPiHost $host) {
    $this->cores = [];

    if ($result = $this->curl('sites/' . $host->id() . '/envs')) {
      foreach ($result as $item) {
        $core = new DrdPiCore($this, $item->name, $item->name);
        $core->setHost($host);
        $domain = new DrdPiDomain($this, $item->name, $item->name);
        $domain->setDetails($core, $item->default_domain);
        $core->addDomain($domain);
        $this->cores[$item->name] = $core;
      }
    }
    return $this->cores;
  }

  /**
   * Execute a curl request on the platform.
   *
   * @param string $command
   *   The command for the curl request.
   *
   * @return array
   *   Json decoded response from the platform.
   */
  public function curl($command) {
    $endpoint = self::ENDPOINT . $command . '.json';
    try {
      $client = new Client([
        'base_uri' => $endpoint,
        'auth' => [
          $this->getEmail(),
          $this->getPrivateKey(),
        ],
      ]);
      $response = $client->request('get');
      if ($response->getStatusCode() < 300) {
        return json_decode($response->getBody());
      }
    }
    catch (\Exception $ex) {
      // Ignore.
    }
    return [];
  }

}
