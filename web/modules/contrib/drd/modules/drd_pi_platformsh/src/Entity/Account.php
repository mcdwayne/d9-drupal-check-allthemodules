<?php

namespace Drupal\drd_pi_platformsh\Entity;

use Drupal\drd\Agent\Action\Base as ActionBase;
use Drupal\drd_pi\DrdPiAccount;
use Drupal\drd_pi\DrdPiCore;
use Drupal\drd_pi\DrdPiDomain;
use Drupal\drd_pi\DrdPiHost;
use Platformsh\Client\PlatformClient;

/**
 * Defines the PlatformSH Account entity.
 *
 * @ConfigEntityType(
 *   id = "platformsh_account",
 *   label = @Translation("PlatformSH Account"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\drd_pi\DrdPiAccountListBuilder",
 *     "form" = {
 *       "add" = "Drupal\drd_pi_platformsh\Entity\AccountForm",
 *       "edit" = "Drupal\drd_pi_platformsh\Entity\AccountForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "platformsh_account",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/drd/settings/platformsh/accounts/{platformsh_account}",
 *     "add-form" = "/drd/settings/platformsh/accounts/add",
 *     "edit-form" = "/drd/settings/platformsh/accounts/{platformsh_account}/edit",
 *     "delete-form" = "/drd/settings/platformsh/accounts/{platformsh_account}/delete",
 *     "collection" = "/drd/settings/platformsh/accounts"
 *   }
 * )
 */
class Account extends DrdPiAccount implements AccountInterface {

  /**
   * The client object to talk to the PlatformSH platform.
   *
   * @var \Platformsh\Client\PlatformClient
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->client = new PlatformClient();
    $this->client->getConnector()->setApiToken($this->getApiToken(), 'exchange');
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptedFieldNames() {
    return [
      'api_token',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getModuleName() {
    return 'drd_pi_platformsh';
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
    return 'PlatformSH';
  }

  /**
   * {@inheritdoc}
   */
  public function getApiToken() {
    return $this->getDecrypted('api_token');
  }

  /**
   * {@inheritdoc}
   */
  public function setApiToken($apiToken) {
    $this->setEncrypted('api_token', $apiToken);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationMethod() {
    return ActionBase::SEC_AUTH_PLATFORMSH;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationSecrets(DrdPiDomain $domain) {
    return [
      'PLATFORM_PROJECT' => $domain->host()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformHosts() {
    $this->hosts = [];

    if ($result = $this->client->getProjects()) {
      foreach ($result as $item) {
        $data = $item->getData();
        if ($data['status'] == 'active') {
          $name = implode(' ', [
            $this->getPlatformName(),
            $this->label(),
            $data['name'],
          ]);
          $this->hosts[$data['id']] = new DrdPiHost($this, $name, $data['id']);
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

    $project = $this->client->getProject($host->id());
    if ($result = $project->getEnvironments()) {
      foreach ($result as $item) {
        $data = $item->getData();
        if ($data['status'] == 'active' && $data['has_code']) {
          $core = new DrdPiCore($this, $data['title'], $data['id']);
          $core->setHost($host);
          $domain = new DrdPiDomain($this, $data['title'], $data['id']);
          $publicUrl = $data['_links']['pf:routes'][0]['href'];
          $domain->setDetails($core, parse_url($publicUrl, PHP_URL_HOST));
          $core->addDomain($domain);
          if (!empty($data['http_access']['basic_auth'])) {
            foreach ($data['http_access']['basic_auth'] as $user => $pass) {
              $domain->setHeader('Authorization', 'Basic ' . base64_encode(implode(':', [$user, $pass])));
            }
          }
          $this->cores[$data['id']] = $core;
        }
      }
    }
    return $this->cores;
  }

}
