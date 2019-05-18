<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Core entity.
 *
 * @ingroup drd
 *
 * @ContentEntityType(
 *   id = "drd_core",
 *   label = @Translation("Core"),
 *   handlers = {
 *     "view_builder" = "Drupal\drd\Entity\ViewBuilder\Core",
 *     "list_builder" = "Drupal\drd\Entity\ListBuilder\Core",
 *     "views_data" = "Drupal\drd\Entity\ViewsData\Core",
 *
 *     "form" = {
 *       "default" = "Drupal\drd\Entity\Form\Core",
 *       "add" = "Drupal\drd\Entity\Form\Core",
 *       "edit" = "Drupal\drd\Entity\Form\Core",
 *       "delete" = "Drupal\drd\Entity\Form\CoreDelete",
 *     },
 *     "access" = "Drupal\drd\Entity\AccessControlHandler\Core",
 *   },
 *   base_table = "drd_core",
 *   admin_permission = "administer DrdCore entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/drd/cores/core/{drd_core}",
 *     "edit-form" = "/drd/cores/core/{drd_core}/edit",
 *     "delete-form" = "/drd/cores/core/{drd_core}/delete"
 *   },
 *   field_ui_base_route = "drd_core.settings"
 * )
 */
class Core extends ContentEntityBase implements CoreInterface {
  use EntityChangedTrait;
  use UseCacheBackendTrait;

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
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'host' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName($fallbackToDomain = TRUE) {
    return $this->get('name')->value;
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
  public function getHost() {
    return $this->get('host')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setHost(HostInterface $host) {
    $this->set('host', $host->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalRelease() {
    return $this->get('drupalversion')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateSettings() {
    $settings = $this->get('updsettings')->getValue();
    return empty($settings) ? [] : $settings[0];
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdatePlugin() {
    /** @var \Drupal\drd\Update\ManagerStorageInterface $updateManager */
    $updateManager = \Drupal::service('plugin.manager.drd_update.storage');
    return $updateManager->executableInstance($this->getUpdateSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function setDrupalRelease(ReleaseInterface $release) {
    $this->set('drupalversion', $release->id());
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
  public function isPublished() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED);
    if (!$published) {
      /* @var \Drupal\drd\Entity\DomainInterface $domain */
      foreach ($this->getDomains() as $domain) {
        $domain
          ->setPublished(FALSE)
          ->save();
      }
    }
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
  public function getDrupalRoot() {
    return $this->get('drupalroot')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Core entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Core entity.'))
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
      ->setDescription(t('The name of the Core entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Core is published.'))
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
      ->setDescription(t('Tags for the core.'))
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
      ->setDescription(t('Header key/value pairs for all domains in this core.'))
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

    $fields['host'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Host'))
      ->setDescription(t('The host on which this core is installed.'))
      ->setSetting('target_type', 'drd_host')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
        'settings' => [],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -5,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['drupalroot'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Drupal root'))
      ->setDescription(t('The root directory of the Drupal installation.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['drupalversion'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Drupal version'))
      ->setDescription(t('The installed Drupal core version.'))
      ->setSetting('target_type', 'drd_release')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

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

    $fields['updsettings'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Update Settings'))
      ->setDescription(t('Serialized settings for update.'))
      ->setDefaultValue([]);

    $fields['locked_releases'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Locked Releases'))
      ->setDescription(t('Locked releases for this core.'))
      ->setSetting('target_type', 'drd_release')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['hacked_releases'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hacked Releases'))
      ->setDescription(t('Hacked releases for this core.'))
      ->setSetting('target_type', 'drd_release')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomains(array $properties = []) {
    $properties['core'] = $this->id();
    $storage = \Drupal::entityTypeManager()->getStorage('drd_domain');
    return $storage->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstActiveDomain() {
    $properties = [
      'core' => $this->id(),
      'installed' => 1,
    ];
    $storage = \Drupal::entityTypeManager()->getStorage('drd_domain');
    $domains = $storage->loadByProperties($properties);
    return empty($domains) ? NULL : array_shift($domains);
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    $host = $this->getHost();
    $headers = empty($host) ? [] : $host->getHeader();
    foreach ($this->get('header') as $header) {
      $headers[$header->key] = $header->value;
    }
    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableUpdates($includeLocked = FALSE, $securityOnly = FALSE, $forceLockedSecurity = FALSE) {
    $releases = [];
    $checked = [];
    foreach ($this->getDomains() as $domain) {
      foreach ($domain->getReleases() as $release) {
        if ($release->isUnsupported()) {
          // No supported release, skip that.
          continue;
        }
        $major = $release->getMajor();
        $project = $major->getProject();
        if (!in_array($project->getName(), $checked)) {
          $checked[] = $project->getName();
          if (!$major->isHidden()) {
            $recommended = $major->getRecommendedRelease();
            if (!empty($recommended) && $recommended->id() != $release->id()) {
              $parent = $major->getParentProject();
              if (empty($parent)) {
                if (!$includeLocked && $this->isReleaseLocked($release, TRUE)) {
                  if (!($forceLockedSecurity && $release->isSecurityRelevant())) {
                    continue;
                  }
                }
                if ($securityOnly && !$release->isSecurityRelevant()) {
                  continue;
                }
                $releases[] = $recommended;
              }
            }
          }
        }
      }
    }
    return $releases;
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
    return implode(':', ['drd_core', $this->uuid(), $topic]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateLogList() {
    $logList = $this->cacheGet($this->cid('updatelogs'));
    return empty($logList) ? [] : $logList->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateLog($timestamp) {
    $cid = $this->cid('updatelog:' . $timestamp);
    $log = $this->cacheGet($cid);
    return empty($log) ? 'No longer available' : $log->data;
  }

  /**
   * {@inheritdoc}
   */
  public function saveUpdateLog($log) {
    $logList = $this->getUpdateLogList();
    $timestamp = \Drupal::time()->getRequestTime();
    $cid = $this->cid('updatelog:' . $timestamp);
    $this->cacheSet($cid, $log);
    $logList[] = [
      'timestamp' => $timestamp,
      'cid' => $cid,
    ];
    $this->cacheSet($this->cid('updatelogs'), $logList);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLockedReleases(array $releases) {
    $this->set('locked_releases', $releases);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLockedReleases() {
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $releases */
    $releases = $this->get('locked_releases');
    return $releases->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function isReleaseLocked(ReleaseInterface $release, $checkGlobal = FALSE) {
    return ($checkGlobal && $release->isLocked()) || in_array($release, $this->getLockedReleases());
  }

  /**
   * {@inheritdoc}
   */
  public function lockRelease(ReleaseInterface $release) {
    if (!$this->isReleaseLocked($release)) {
      $locked_releases = $this->getLockedReleases();
      $locked_releases[] = $release;
      $this->setLockedReleases($locked_releases);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unlockRelease(ReleaseInterface $release) {
    if ($this->isReleaseLocked($release)) {
      $locked_releases = $this->getLockedReleases();
      $idx = array_search($release, $locked_releases);
      unset($locked_releases[$idx]);
      $this->setLockedReleases($locked_releases);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unlockAllReleases() {
    $this->setLockedReleases([]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHackedReleases(array $releases) {
    $this->set('hacked_releases', $releases);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHackedReleases() {
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $releases */
    $releases = $this->get('hacked_releases');
    return $releases->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function isReleaseHacked(ReleaseInterface $release) {
    return in_array($release, $this->getHackedReleases());
  }

  /**
   * {@inheritdoc}
   */
  public function markReleaseHacked(ReleaseInterface $release) {
    if (!$this->isReleaseHacked($release)) {
      $hacked_releases = $this->getHackedReleases();
      $hacked_releases[] = $release;
      $this->setHackedReleases($hacked_releases);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function markReleaseUnhacked(ReleaseInterface $release) {
    if ($this->isReleaseHacked($release)) {
      $hacked_releases = $this->getHackedReleases();
      $idx = array_search($release, $hacked_releases);
      unset($hacked_releases[$idx]);
      $this->setHackedReleases($hacked_releases);
    }
    return $this;
  }

}
