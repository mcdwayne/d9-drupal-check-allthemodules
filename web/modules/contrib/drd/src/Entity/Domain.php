<?php

namespace Drupal\drd\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\drd\Crypt\Base as CryptBase;
use Drupal\drd\Plugin\Action\BaseEntityRemote;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Domain entity.
 *
 * @ingroup drd
 *
 * @ContentEntityType(
 *   id = "drd_domain",
 *   label = @Translation("Domain"),
 *   handlers = {
 *     "view_builder" = "Drupal\drd\Entity\ViewBuilder\Domain",
 *     "list_builder" = "Drupal\drd\Entity\ListBuilder\Domain",
 *     "views_data" = "Drupal\drd\Entity\ViewsData\Domain",
 *
 *     "form" = {
 *       "default" = "Drupal\drd\Entity\Form\Domain",
 *       "edit" = "Drupal\drd\Entity\Form\Domain",
 *       "reset" = "Drupal\drd\Entity\Form\DomainReset",
 *     },
 *     "access" = "Drupal\drd\Entity\AccessControlHandler\Domain",
 *   },
 *   base_table = "drd_domain",
 *   admin_permission = "administer DrdDomain entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/drd/domains/domain/{drd_domain}",
 *     "edit-form" = "/drd/domains/domain/{drd_domain}/edit",
 *     "reset-form" = "/drd/domains/domain/{drd_domain}/reset",
 *   },
 *   field_ui_base_route = "drd_domain.settings"
 * )
 */
class Domain extends ContentEntityBase implements DomainInterface {
  use EntityChangedTrait;
  use UseCacheBackendTrait;
  use StringTranslationTrait;

  // TODO: Drupal 6 and 7 default to FALSE, Drupal 8 only supports TRUE.
  /**
   * Flag to determine if remote domain support clean URLs or not.
   *
   * @var bool
   */
  protected $cleanUrl = TRUE;

  private $databases = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle) {
    parent::__construct($values, $entity_type, $bundle);
    $this->cacheBackend = \Drupal::cache();
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['user_id' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptedFieldNames() {
    return [
      'authsetting',
      'cryptsetting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCookies() {
    $cache = $this->cacheGet($this->cid('cookies'));
    if ($cache) {
      return $cache->data;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setCookies(array $cookies) {
    $this->cacheSet($this->cid('cookies'), $cookies);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName($fallbackToDomain = TRUE) {
    $name = $this->get('name')->value;
    return (empty($name) && $fallbackToDomain) ? $this->getDomainName() : $name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomainName() {
    return $this->get('domain')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCore() {
    return $this->get('core')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCore(CoreInterface $core) {
    $this->set('core', $core->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled() {
    return (bool) $this->get('installed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuth() {
    return $this->get('auth')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthSetting($type = NULL, $for_remote = FALSE) {
    if (!isset($type)) {
      $type = $this->getAuth();
    }
    if (empty($type)) {
      return [];
    }

    if ($for_remote) {
      /** @var \Drupal\drd\Plugin\Auth\Base $auth */
      $auth = \Drupal::service('plugin.manager.drd_auth')->createInstance($type);
      if (!$auth->storeSettingRemotely()) {
        return [];
      }
    }
    $settings = $this->get('authsetting')->getValue();
    $settings = isset($settings[0][$type]) ? $settings[0][$type] : [];

    /* @var \Drupal\drd\Encryption $service */
    $service = \Drupal::service('drd.encrypt');
    $service->decrypt($settings);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthSetting(array $settings) {
    /* @var \Drupal\drd\Encryption $service */
    $service = \Drupal::service('drd.encrypt');
    $service->encrypt($settings);
    $this->set('authsetting', $settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCrypt() {
    return $this->get('crypt')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCryptSetting($type = NULL) {
    if (!isset($type)) {
      $type = $this->getCrypt();
    }
    if (empty($type)) {
      return [];
    }
    $settings = $this->get('cryptsetting')->getValue();
    $settings = isset($settings[0][$type]) ? $settings[0][$type] : [];

    /* @var \Drupal\drd\Encryption $service */
    $service = \Drupal::service('drd.encrypt');
    $service->decrypt($settings);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setCryptSetting(array $settings, $encrypted = FALSE) {
    if (!$encrypted) {
      /* @var \Drupal\drd\Encryption $service */
      $service = \Drupal::service('drd.encrypt');
      $service->encrypt($settings);
    }
    $this->set('cryptsetting', $settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangCode() {
    return $this->get('langcode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Domain entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Domain entity.'))
      ->setReadOnly(TRUE);
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Host entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Domain entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -11,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Domain is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Domain entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['terms'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tags'))
      ->setDescription(t('Tags for the host.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['tags'],
        'sort' => ['field' => '_none'],
        'auto_create' => TRUE,
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setCustomStorage(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -1,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['header'] = BaseFieldDefinition::create('key_value')
      ->setLabel(t('Header'))
      ->setDescription(t('Header key/value pairs for the domain.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setCustomStorage(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'key_value_textfield',
        'weight' => 0,
        'settings' => [
          'key_size' => 60,
          'key_placeholder' => 'Key',
          'size' => 60,
          'placeholder' => 'Value',
          'description_placeholder' => '',
          'description_enabled' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['core'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Core'))
      ->setDescription(t('The Drupal core that contains that domain.'))
      ->setSetting('target_type', 'drd_core')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -10,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['releases'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Releases'))
      ->setDescription(t('The installed core, module and theme releases on this domain.'))
      ->setSetting('target_type', 'drd_release')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['warnings'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Warnings'))
      ->setDescription(t('The requirements from the status page that cause a warning.'))
      ->setSetting('target_type', 'drd_requirement')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['errors'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Errors'))
      ->setDescription(t('The requirements from the status page that cause an error.'))
      ->setSetting('target_type', 'drd_requirement')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['domain'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Domain'))
      ->setDescription(t('The domain name.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -7,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['aliase'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Aliase'))
      ->setDescription(t('The aliases for this domain name.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -4,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uripath'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Uri Path'))
      ->setDescription(t('The base uri path.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -6,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['port'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Port'))
      ->setDescription(t('The port to connect to if different from default ports.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -5,
        'settings' => [
          'type' => 'number_unformatted',
        ],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['auth'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Authentication'))
      ->setDescription(t('The selected type of authenticaion.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -3,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['authsetting'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Authentication Settings'))
      ->setDescription(t('Serialized settings for authentication.'))
      ->setDefaultValue([]);

    $fields['crypt'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Encryption'))
      ->setDescription(t('The selected type of encryption.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -2,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cryptsetting'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Encryption Settings'))
      ->setDescription(t('Serialized settings for encryption.'))
      ->setDefaultValue([]);

    $fields['installed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Installed'))
      ->setDescription(t('A boolean indicating whether the DRD module is installed remotely.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -9,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['secure'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Secure connection'))
      ->setDescription(t('A boolean indicating whether SSL is supported.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => -8,
        'settings' => ['format' => 'ssl-yes-no'],
        'type' => 'drd_domain_secure',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Determine if URI uses SSL and port to be used.
   *
   * @param array $uri_parts
   *   The parsed URL as an array of parts.
   *
   * @return array
   *   An array with 2 values: TRUE or FALSE to tell if URL uses SSL and the
   *   port to be used.
   */
  private static function calculateScheme(array $uri_parts) {
    $secure = ($uri_parts['scheme'] == 'https');
    $port = isset($uri_parts['port']) ? $uri_parts['port'] : ($secure ? 443 : 80);
    return [$secure, $port];
  }

  /**
   * {@inheritdoc}
   */
  public static function instanceFromUrl(CoreInterface $core, $uri, array $values) {
    $uri_parts = parse_url($uri);
    $storage = \Drupal::entityTypeManager()->getStorage('drd_domain');

    $query = \Drupal::entityQuery('drd_domain')
      ->condition('domain', $uri_parts['host']);
    if (isset($uri_parts['path'])) {
      $query->condition('uripath', $uri_parts['path']);
    }
    else {
      $query->notExists('uripath');
    }
    if (isset($uri_parts['port'])) {
      $query->condition('port', $uri_parts['port']);
    }
    $ids = $query->execute();

    /** @var DomainInterface $domain */
    if (empty($ids)) {
      list($secure, $port) = self::calculateScheme($uri_parts);
      if (!$core->isNew()) {
        $values['core'] = $core->id();
      }
      $values['domain'] = $uri_parts['host'];
      $values['uripath'] = isset($uri_parts['path']) ? $uri_parts['path'] : '';
      $values['secure'] = $secure;
      $values['port'] = $port;
      $domain = $storage->create($values);
    }
    else {
      $domain = $storage->load(reset($ids));
    }
    return $domain;
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrl($query = '') {
    $uri = $this->get('secure')->value ? 'https' : 'http';
    $uri .= '://' . $this->get('domain')->value;
    if (!$this->isDefaultPort()) {
      $uri .= ':' . $this->get('port')->value;
    }
    if (!empty($this->get('uripath')->value)) {
      $uri .= '/' . $this->get('uripath')->value;
    }
    if ($this->cleanUrl && !empty($query)) {
      $uri .= '/' . $query;
    }
    $url = Url::fromUri($uri);
    if (!$this->cleanUrl && !empty($query)) {
      $url->setOption('query', ['q' => $query]);
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCryptMethods($cleanUrl = TRUE) {
    $this->cleanUrl = $cleanUrl;

    /** @var \Drupal\drd\HttpRequest $request */
    $request = \Drupal::service('drd.http_request');
    $request->setDomain($this)
      ->setQuery('drd-agent-crypt')
      ->request();

    if (!$request->isRemoteDrd()) {
      if ($cleanUrl) {
        // Let's try again without clean URLs.
        return $this->getSupportedCryptMethods(FALSE);
      }
      if ($request->getStatusCode() == 200) {
        // We received proper response from a website, but not from DRD remote.
        return NULL;
      }
      // We haven't received anything from remote.
      return FALSE;
    }

    try {
      return json_decode($request->getResponse(), TRUE);
    }
    catch (\Exception $ex) {
      // We got some response which is not from drd_agent.
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function authorizeBySecret($method, array $secrets) {
    $body = base64_encode(json_encode([
      'remoteSetupToken' => $this->getRemoteSetupToken(FALSE),
      'method' => $method,
      'secrets' => $secrets,
    ]));
    /* @var \Drupal\drd\HttpRequest $remote */
    $remote = \Drupal::service('drd.http_request');
    $remote
      ->setDomain($this)
      ->setQuery('drd-agent-authorize-secret')
      ->setOption('body', $body)
      ->request();
    return ($remote->getStatusCode() == 200);
  }

  /**
   * {@inheritdoc}
   */
  public function pushOtt($token) {
    $url = $this->buildUrl('drd-agent');
    $body = base64_encode(json_encode([
      'uuid' => $this->uuid(),
      'args' => 'none',
      'iv' => 'none',
      'ott' => $token,
      'config' => $this->getRemoteSetupToken(FALSE),
    ]));
    /* @var \Drupal\drd\HttpRequest $remote */
    $remote = \Drupal::service('drd.http_request');
    $remote
      ->setDomain($this)
      ->setQuery('drd-agent')
      ->setOption('body', $body)
      ->request();
    return ($remote->getStatusCode() == 200);
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultPort() {
    $secure = $this->get('secure')->value;
    $port = $this->get('port')->value;
    return $secure ? ($port == 443) : ($port == 80);
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteSetupToken($redirect) {
    global $base_url;
    $uri_parts = parse_url($base_url);
    $ipv4 = $ipv6 = [];
    \Drupal::service('drd.dnslookup')->lookup($uri_parts['host'], $ipv4, $ipv6);
    $values = [
      'uuid' => $this->get('uuid')->value,
      'auth' => $this->get('auth')->value,
      'authsetting' => $this->getAuthSetting(NULL, TRUE),
      'crypt' => $this->get('crypt')->value,
      'cryptsetting' => $this->getCryptSetting(),
      'redirect' => $redirect,
      'drdips' => [
        'v4' => $ipv4,
        'v6' => $ipv6,
      ],
    ];

    $values = base64_encode(json_encode($values));
    return strtr($values, ['+' => '-', '/' => '_', '=' => '']);
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteLoginLink($label) {
    $url = $this->buildUrl('user/login');
    $url->setOption('attributes', ['target' => '_blank']);
    return \Drupal::linkGenerator()->generate($label, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteSetupRedirect($initial = FALSE) {
    if ($initial) {
      $redirect = new Url('entity.drd_domain.return_remote', ['domain' => $this->id()], ['absolute' => TRUE]);
      $redirect->setOption('query', ['token' => \Drupal::csrfToken()->get($redirect->getInternalPath())]);
    }
    else {
      $redirect = new Url('entity.drd_core.collection', [], ['absolute' => TRUE]);
    }
    return $redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteSetupLink($label, $initial = FALSE) {
    $url = $this->buildUrl('drd-agent-authorize');
    return \Drupal::linkGenerator()->generate($label, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalUrl() {
    return implode('.', [
      'id' . $this->id(),
      'drd',
      'localhost',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionUrl() {
    return BaseEntityRemote::response('drd_action_session', $this);
  }

  /**
   * {@inheritdoc}
   */
  public function database() {
    if (empty($this->databases)) {
      $this->databases = BaseEntityRemote::response('drd_action_database', $this);
    }
    return $this->databases;
  }

  /**
   * {@inheritdoc}
   */
  public function download($source, $destination) {
    return BaseEntityRemote::response('drd_action_download', $this, [
      'source' => $source,
      'destination' => $destination,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function ping() {
    return BaseEntityRemote::response('drd_action_ping', $this);
  }

  /**
   * {@inheritdoc}
   */
  public function remoteInfo() {
    return BaseEntityRemote::response('drd_action_info', $this);
  }

  /**
   * {@inheritdoc}
   */
  public function initCore(CoreInterface $core) {
    $response = $this->remoteInfo();

    if (!$response) {
      return FALSE;
    }

    // Set Drupal root directory.
    $core->set('drupalroot', $response['root']);

    // Set Drupal version.
    $release = Release::findOrCreate('core', 'drupal', $response['version']);
    $core->setDrupalRelease($release);

    // Save core.
    $core->save();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function initValues($name, $crypt = NULL, array $crypt_setting = []) {
    $this->setName($name);
    // Initialize settings.
    if (empty($crypt)) {
      $crypt = 'OpenSSL';
      $crypt_setting = ['cipher' => 'aes-128-cbc'];
    }
    $crypt_setting['password'] = user_password(50);
    $this->set('auth', 'shared_secret');
    $this->set('crypt', $crypt);
    $this
      ->setAuthSetting(['shared_secret' => ['secret' => user_password(50)]])
      ->setCryptSetting([$crypt => $crypt_setting]);
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveAllDomains(CoreInterface $core) {
    $this->set('core', $core->id());
    $this->save();
    BaseEntityRemote::response('drd_action_domains_receive', $core);
  }

  /**
   * {@inheritdoc}
   */
  public function setAliase(array $aliase) {
    $this->set('aliase', $aliase);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateScheme($url) {
    list($secure, $port) = self::calculateScheme(parse_url($url));
    $this->set('secure', $secure);
    $this->set('port', $port);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setReleases(array $releases) {
    $this->set('releases', $releases);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReleases() {
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $releases */
    $releases = $this->get('releases');
    return $releases->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    $cache = $this->cacheGet($this->cid('msg'));
    if ($cache) {
      return $cache->data;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getReview() {
    $cache = $this->cacheGet($this->cid('review'));
    if ($cache) {
      return $cache->data;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMonitoring() {
    $cache = $this->cacheGet($this->cid('monitoring'));
    if ($cache) {
      return $cache->data;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryResult() {
    $cache = $this->cacheGet($this->cid('queryresult'));
    if ($cache) {
      return $cache->data;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMaintenanceMode($refresh = TRUE) {
    $cache = $this->cacheGet($this->cid('maintenancemode'));
    if ($cache) {
      return $cache->data;
    }

    if ($refresh) {
      // We do not know the status, let's get it from remote.
      BaseEntityRemote::response('drd_action_maintenance_mode', $this);
      return $this->getMaintenanceMode(FALSE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestPingStatus($refresh = TRUE) {
    $cache = $this->cacheGet($this->cid('latest_ping_status'));
    if ($cache) {
      return $cache->data;
    }

    if ($refresh) {
      $this->ping();
      return $this->getLatestPingStatus(FALSE);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteBlock($module, $delta, $refresh = TRUE) {
    $cache = $this->cacheGet($this->cid(implode(':', ['block', $module, $delta])));
    if ($cache) {
      return $cache->data;
    }

    if ($refresh) {
      BaseEntityRemote::response('drd_action_blocks', $this, [
        'module' => $module,
        'delta' => $delta,
      ]);
      return $this->getRemoteBlock($module, $delta, FALSE);
    }
    return FALSE;
  }

  /**
   * Get a specific part of the info array from the remote domain.
   *
   * @param string $part
   *   Part to be extracted.
   * @param bool $refresh
   *   Whether to refresh the info from the remote domain if we don't have
   *   fresh data in cache available.
   *
   * @return array
   *   The requisted info part.
   */
  private function getInfoPart($part, $refresh = TRUE) {
    $cache = $this->cacheGet($this->cid($part));
    if ($cache) {
      return $cache->data;
    }

    if ($refresh) {
      BaseEntityRemote::response('drd_action_info', $this);
      return $this->getInfoPart($part, FALSE);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteSettings() {
    return $this->getInfoPart('settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteGlobals() {
    return $this->getInfoPart('globals');
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteRequirements() {
    return $this->getInfoPart('requirements');
  }

  /**
   * Build a cache id.
   *
   * @param string $topic
   *   The topic for the cache id.
   *
   * @return string
   *   The cache id.
   */
  private function cid($topic) {
    return implode(':', ['drd_domain', $this->uuid(), $topic]);
  }

  /**
   * Get cache tags.
   *
   * @return array
   *   The cache tags.
   */
  private function ctags() {
    return [];
  }

  /**
   * Get cache lifetime.
   *
   * @return int
   *   The cache lifetime.
   */
  private function ctime() {
    return \Drupal::time()->getRequestTime() + 600;
  }

  /**
   * Sanitize all parts of a remote response.
   *
   * @param mixed $text
   *   The text or texts that need to be sanitized.
   * @param bool $translatable
   *   Whether the text is translatable.
   *
   * @return array|TranslatableMarkup
   *   The sanitized text or texts.
   */
  private function sanitizeRemoteText($text, $translatable = TRUE) {
    if (is_array($text)) {
      $result = [];
      foreach ($text as $key => $line) {
        $result[$key] = $this->sanitizeRemoteText($line, $translatable);
      }
    }
    elseif (is_string($text)) {
      $text = str_replace('href="/', 'target="_blank" href="' . $this->buildUrl()->toString() . '/', $text);
      $text = str_replace('destination=', 'destoff=', $text);
      if ($translatable) {
        $result = new FormattableMarkup($text, []);
      }
      else {
        $result = $text;
      }
    }
    elseif ($text instanceof TranslatableMarkup) {
      // We extract the text so that we can adjust the links.
      $result = $this->sanitizeRemoteText($text->render(), TRUE);
    }
    else {
      $result = $text;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheRemoteMessages(array $messages) {
    $cid = $this->cid('msg');
    $cache = $this->cacheGet($cid);
    $data = $cache ? $cache->data : [];
    foreach ($messages as $type => $items) {
      foreach ($items as $item) {
        $data[$type] = [
          'ts' => \Drupal::time()->getRequestTime(),
          'msg' => $this->sanitizeRemoteText($item),
        ];
      }
    }
    $this->cacheSet($cid, $data, Cache::PERMANENT, $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheMaintenanceMode($mode) {
    $this->cacheSet($this->cid('maintenancemode'), $mode, $this->ctime(), $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheBlock($module, $delta, $content) {
    $this->cacheSet($this->cid(implode(':', ['block', $module, $delta])), $this->sanitizeRemoteText($content, FALSE), $this->ctime(), $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cachePingResult($status) {
    $cid = $this->cid('latest_ping_status');
    if (isset($status)) {
      $this->cacheSet($cid, $status, Cache::PERMANENT, $this->ctags());
    }
    else {
      if ($this->cacheBackend && $this->useCaches) {
        $this->cacheBackend->delete($cid);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheErrorLog($log) {
    $this->cacheSet($this->cid('errorlog'), $log, Cache::PERMANENT, $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheRequirements(array $requirements) {
    foreach ($requirements as $key => $requirement) {
      if (!empty($requirement['value'])) {
        $value = $this->sanitizeRemoteText($requirement['value'], FALSE);
        $requirements[$key]['value'] = is_string($value) ? new FormattableMarkup($value, []) : $value;
      }
      if (!empty($requirement['description'])) {
        $description = $this->sanitizeRemoteText($requirement['description'], FALSE);
        $requirements[$key]['description'] = is_string($description) ? new FormattableMarkup($description, []) : $description;
      }
    }
    $this->cacheSet($this->cid('requirements'), $requirements, $this->ctime(), $this->ctags());

    // Load the install file, that also loads install.inc from Drupal core.
    \Drupal::moduleHandler()->loadInclude('drd', 'install');
    $warnings = $errors = [];
    // Analyze the requirements and store warnings and errors with the domain.
    foreach ($requirements as $key => $value) {
      $label = isset($value['title']) ? $value['title'] : $key;
      try {
        $requirement = Requirement::findOrCreate($key, $label);
      }
      catch (\Exception $ex) {
        // Ignore old drd_server requirements.
        continue;
      }
      $severity = isset($value['severity']) ? $value['severity'] : REQUIREMENT_INFO;
      switch ($severity) {
        case REQUIREMENT_ERROR:
          $errors[] = $requirement;
          break;

        case REQUIREMENT_WARNING:
          $warnings[] = $requirement;
          break;

      }
    }
    $this->set('warnings', $warnings);
    $this->set('errors', $errors);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheVariables(array $variables) {
    $this->cacheSet($this->cid('variables'), $variables, Cache::PERMANENT, $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheGlobals(array $globals) {
    $this->cacheSet($this->cid('globals'), $globals, Cache::PERMANENT, $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheSettings(array $settings) {
    $this->cacheSet($this->cid('settings'), $settings, Cache::PERMANENT, $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheReview(array $review) {
    $this->cacheSet($this->cid('review'), $review, Cache::PERMANENT, $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheMonitoring(array $monitoring) {
    $this->cacheSet($this->cid('monitoring'), $monitoring, Cache::PERMANENT, $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheQueryResult($query, $info, array $headers, array $rows) {
    $this->cacheSet($this->cid('queryresult'), [
      'query' => $query,
      'info' => $info,
      'headers' => $headers,
      'rows' => $rows,
    ], Cache::PERMANENT, $this->ctags());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    $core = $this->getCore();
    $headers = empty($core) ? [] : $core->getHeader();
    foreach ($this->get('header') as $header) {
      $headers[$header->key] = $header->value;
    }
    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCryptSettings() {
    $crypt_methods_remote = $this->getSupportedCryptMethods();
    if ($crypt_methods_remote === FALSE) {
      drupal_set_message($this->t('Can not connect to domain @url.', ['@url' => $this->get('domain')->value]), 'error');
      return $this;
    }
    if (empty($crypt_methods_remote)) {
      drupal_set_message($this->t('There is no DRD Agent available at domain @url.', ['@url' => $this->get('domain')->value]), 'error');
      return $this;
    }
    elseif (CryptBase::countAvailableMethods($crypt_methods_remote) == 0) {
      drupal_set_message($this->t('The remote site @url has DRD Agent installed but does not support any encryption methods matching those of the dashboard.', ['@url' => $this->get('domain')->value]), 'error');
      return $this;
    }
    $crypt_methods_local = CryptBase::getMethods();

    $method = 'OpenSSL';
    if (!isset($crypt_methods_local[$method]) || !isset($crypt_methods_remote[$method])) {
      foreach ($crypt_methods_local as $value) {
        if (isset($crypt_methods_remote[$value])) {
          $method = $value;
          break;
        }
      }
    }

    $crypt = CryptBase::getInstance($method, []);
    if ($crypt->requiresPassword()) {
      $crypt->resetPassword();
    }
    $this->set('crypt', $method);
    $this->setCryptSetting([$method => $crypt->getSettings()], TRUE);

    return $this->reset();
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->setName(NULL);
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function renderPingStatus() {
    $status = $this->getLatestPingStatus(FALSE);
    if (is_null($status)) {
      return '';
    }
    if ($status) {
      $type = 'ok';
      $title = $this->t('Ping OK');
    }
    else {
      $type = 'failure';
      $title = $this->t('Does not respond');
    }
    return '<div class="drd-ping-info ' . $type . '"><span class="drd-icon">&nbsp;</span><div class="label">' . $title->render() . '</div></div>';
  }

}
