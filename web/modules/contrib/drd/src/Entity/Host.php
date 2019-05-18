<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Host entity.
 *
 * @ingroup drd
 *
 * @ContentEntityType(
 *   id = "drd_host",
 *   label = @Translation("Host"),
 *   handlers = {
 *     "view_builder" = "Drupal\drd\Entity\ViewBuilder\Host",
 *     "list_builder" = "Drupal\drd\Entity\ListBuilder\Host",
 *     "views_data" = "Drupal\drd\Entity\ViewsData\Host",
 *
 *     "form" = {
 *       "default" = "Drupal\drd\Entity\Form\Host",
 *       "add" = "Drupal\drd\Entity\Form\Host",
 *       "edit" = "Drupal\drd\Entity\Form\Host",
 *       "delete" = "Drupal\drd\Entity\Form\HostDelete",
 *     },
 *     "access" = "Drupal\drd\Entity\AccessControlHandler\Host",
 *   },
 *   base_table = "drd_host",
 *   admin_permission = "administer DrdHost entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/drd/hosts/host/{drd_host}",
 *     "edit-form" = "/drd/hosts/host/{drd_host}/edit",
 *     "delete-form" = "/drd/hosts/host/{drd_host}/delete"
 *   },
 *   field_ui_base_route = "drd_host.settings"
 * )
 */
class Host extends ContentEntityBase implements HostInterface {
  use EntityChangedTrait;

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
      'ssh2setting',
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
      /* @var \Drupal\drd\Entity\CoreInterface $core */
      foreach ($this->getCores() as $core) {
        $core
          ->setPublished(FALSE)
          ->save();
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrush() {
    return $this->get('drush')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalConsole() {
    return $this->get('drupalconsole')->value;
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
      ->setDescription(t('The ID of the Host entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Host entity.'))
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
      ->setDescription(t('The name of the Host entity.'))
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
      ->setDescription(t('A boolean indicating whether the Host is published.'))
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
      ->setDescription(t('Header key/value pairs for all domains in all cores of this host.'))
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

    $fields['ssh2'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enable SSH2'))
      ->setDescription(t('A boolean indicating whether the remote host is supporting SSH2.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -4,
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ssh2setting'] = BaseFieldDefinition::create('map')
      ->setLabel(t('SSH2 Settings'))
      ->setDescription(t('Serialized settings for SSH2 connectivity.'))
      ->setDefaultValue([]);

    $fields['drush'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Drush executable'))
      ->setDescription(t('Full path to the Drush executable on the remote host.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['drupalconsole'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DrupalConsole executable'))
      ->setDescription(t('Full path to the DrupalConsole executable on the remote host.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ipv4'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('IP v4'))
      ->setDescription(t('The v4 IP addresses for this host.'))
      ->setSetting('size', 'big')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'ipv4field_formatter',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ipv6'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('IP v6'))
      ->setDescription(t('The v6 IP addresses for this host.'))
      ->setSetting('size', 'big')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'ipv6field_formatter',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCores(array $properties = []) {
    $properties['host'] = $this->id();
    $storage = \Drupal::entityTypeManager()->getStorage('drd_core');
    return $storage->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function getDomains(array $properties = []) {
    $properties['core'] = array_keys($this->getCores());
    if (empty($properties['core'])) {
      return [];
    }
    $storage = \Drupal::entityTypeManager()->getStorage('drd_domain');
    return $storage->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsSsh() {
    return (bool) $this->get('ssh2')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSshSettings() {
    $settings = $this->get('ssh2setting')->getValue();
    $settings = empty($settings) ? [] : $settings[0];
    $settings += [
      'host' => '',
      'port' => 22,
      'auth' => [
        'mode' => 1,
        'username' => '',
        'password' => '',
        'file_public_key' => '',
        'file_private_key' => '',
        'key_secret' => '',
      ],
    ];
    /* @var \Drupal\drd\Encryption $service */
    $service = \Drupal::service('drd.encrypt');
    $service->decrypt($settings);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setSshSettings(array $settings) {
    /* @var \Drupal\drd\Encryption $service */
    $service = \Drupal::service('drd.encrypt');
    $service->encrypt($settings);
    $this->set('ssh2setting', $settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIpv4($refresh = TRUE) {
    $ipv4 = $this->get('ipv4')->getValue();
    if (empty($ipv4[0]['value'])) {
      if ($refresh) {
        return $this->getIpv4(FALSE);
      }
      return FALSE;
    }
    return long2ip($ipv4[0]['value']);
  }

  /**
   * {@inheritdoc}
   */
  public function updateIpAddresses() {
    $ipv4 = $ipv6 = [];
    foreach ($this->getCores() as $core) {
      /** @var CoreInterface $core */
      foreach ($core->getDomains() as $domain) {
        /** @var DomainInterface $domain */
        \Drupal::service('drd.dnslookup')->lookup($domain->getDomainName(), $ipv4, $ipv6);
      }
    }
    $this->set('ipv4', array_unique($ipv4));
    $this->set('ipv6', array_unique($ipv6));
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function findOrCreateByHost($name) {
    $ipv4 = $ipv6 = [];
    \Drupal::service('drd.dnslookup')->lookup($name, $ipv4, $ipv6);
    $storage = \Drupal::entityTypeManager()->getStorage('drd_host');
    if (!empty($ipv4)) {
      $hosts = $storage->loadByProperties([
        'ipv4' => $ipv4,
      ]);
    }
    if (empty($hosts)) {
      $host = $storage->create([
        'name' => $name,
        'ipv4' => $ipv4,
        'ipv6' => $ipv6,
      ]);
      $host->save();
    }
    else {
      $host = reset($hosts);
    }
    return $host;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    $headers = [];
    foreach ($this->get('header') as $header) {
      $headers[$header->key] = $header->value;
    }
    return $headers;
  }

}
