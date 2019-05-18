<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Release entity.
 *
 * @ingroup drd
 *
 * @ContentEntityType(
 *   id = "drd_release",
 *   label = @Translation("Release"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\drd\Entity\ListBuilder\Release",
 *     "views_data" = "Drupal\drd\Entity\ViewsData\Release",
 *
 *     "form" = {
 *       "default" = "Drupal\drd\Entity\Form\Release",
 *       "edit" = "Drupal\drd\Entity\Form\Release",
 *     },
 *     "access" = "Drupal\drd\Entity\AccessControlHandler\Release",
 *   },
 *   base_table = "drd_release",
 *   admin_permission = "administer DrdRelease entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "version",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/drd/releases/release/{drd_release}",
 *     "edit-form" = "/drd/releases/release/{drd_release}/edit",
 *   },
 *   field_ui_base_route = "drd_release.settings"
 * )
 */
class Release extends ContentEntityBase implements ReleaseInterface {
  use EntityChangedTrait;

  private $justCreated = FALSE;

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
  public function isJustCreated($flag = NULL) {
    if (isset($flag)) {
      $this->justCreated = $flag;
    }
    return $this->justCreated;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnsupported() {
    return in_array($this->getUpdateStatus(), [
      UpdateManagerInterface::REVOKED,
      UpdateManagerInterface::NOT_SUPPORTED,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function isSecurityRelevant() {
    return ($this->getUpdateStatus() == UpdateManagerInterface::NOT_SECURE);
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateStatus() {
    return $this->get('updatestatus')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->get('version')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getReleaseVersion() {
    $version = explode('-', $this->getVersion());
    if (strpos($version[0], '.x') > 0) {
      array_shift($version);
    }
    return implode('-', $version);
  }

  /**
   * {@inheritdoc}
   */
  public function setVersion($version) {
    $this->set('version', $version);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMajor() {
    return $this->get('major')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setMajor(MajorInterface $major) {
    $this->set('major', $major->id());
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
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->get('locked')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocked($locked) {
    $this->set('locked', $locked ? 1 : 0);
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
      ->setDescription(t('The ID of the Release entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Release entity.'))
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

    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version'))
      ->setDescription(t('The version of the Release entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Release is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Release entity.'));

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

    $fields['major'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Major version'))
      ->setDescription(t('The major version for which this is a specific release.'))
      ->setSetting('target_type', 'drd_major')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -3,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['updatestatus'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Update status'))
      ->setDescription(t('The update status of this release.'))
      ->setSetting('size', 'small')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -2,
        'settings' => [
          'type' => 'number_unformatted',
        ],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['information'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Information'))
      ->setDescription(t('Serialized information about the release.'))
      ->setDefaultValue([]);

    $fields['updateinfo'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Update information'))
      ->setDescription(t('Serialized information about the update information of this release.'))
      ->setDefaultValue([]);

    $fields['locked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Locked'))
      ->setDescription(t('A boolean indicating whether the Release is locked.'))
      ->setDefaultValue(FALSE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function findOrCreate($type, $name, $version) {
    $release = self::find($name, $version);
    if (empty($release)) {
      $major = Major::findOrCreate($type, $name, $version);
      $storage = \Drupal::entityTypeManager()->getStorage('drd_release');
      /** @var ReleaseInterface $release */
      $release = $storage->create([
        'version' => $version,
        'major' => $major->id(),
      ]);
      $release->save();
      $release->isJustCreated(TRUE);
    }
    return $release;
  }

  /**
   * {@inheritdoc}
   */
  public static function find($name, $version) {
    $major = Major::find($name, $version);
    if ($major) {
      $storage = \Drupal::entityTypeManager()->getStorage('drd_release');
      $releases = $storage->loadByProperties([
        'major' => $major->id(),
        'version' => $version,
      ]);
    }
    return empty($releases) ? FALSE : reset($releases);
  }

  /**
   * Get information from the release details.
   *
   * @param string|null $key
   *   Get a portion of the update information or all.
   *
   * @return mixed
   *   Return all or a portion of the release's update information or FALSE if
   *   the requested information doesn't exist.
   */
  private function getUpdateInfo($key = NULL) {
    $value = $this->get('updateinfo')->getValue()[0];
    return isset($key) ? isset($value[$key]) ? $value[$key] : FALSE : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectType() {
    return $this->getMajor()->getProject()->getType();
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectLink() {
    $uri = $this->getUpdateInfo('link');
    if (empty($uri)) {
      $uri = $this->getMajor()->getProject()->getProjectLink();
    }
    return Url::fromUri($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getReleaseLink() {
    $releases = $this->getUpdateInfo('releases');
    if (empty($releases[$this->getVersion()])) {
      return $this->getProjectLink();
    }
    return Url::fromUri($releases[$this->getVersion()]['release_link']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDownloadLink() {
    $releases = $this->getUpdateInfo('releases');
    if (empty($releases[$this->getVersion()])) {
      return $this->getReleaseLink();
    }
    return Url::fromUri($releases[$this->getVersion()]['download_link']);
  }

}
