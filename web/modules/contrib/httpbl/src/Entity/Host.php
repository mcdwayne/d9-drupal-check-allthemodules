<?php
/**
 * @file
 * Contains \Drupal\httpbl\Entity\Host.
 */

namespace Drupal\httpbl\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\httpbl\HostInterface;

/**
 * Defines the host entity class.
 *
 * @ingroup httpbl
 *
 * @ContentEntityType(
 *   id = "host",
 *   label = @Translation("Httpbl Host"),
 *   label_singular = @Translation("host"),
 *   label_plural = @Translation("hosts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count host",
 *     plural = "@count hosts"
 *   ),
 *   base_table = "httpbl_host",
 *   handlers = {
 *     "storage_schema" = "Drupal\httpbl\HostStorageSchema",
 *     "views_data" = "Drupal\httpbl\HostViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\httpbl\Entity\Controller\HostListBuilder",
 *     "form" = {
 *       "add" = "Drupal\httpbl\Form\HostForm",
 *       "edit" = "Drupal\httpbl\Form\HostForm",
 *       "delete" = "Drupal\httpbl\Form\HostDeleteForm",
 *     },
 *     "access" = "Drupal\httpbl\HostAccessControlHandler",
 *   },
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "hid",
 *     "host_ip" = "host_ip",
 *     "label" = "host_ip",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/people/host/{host}",
 *     "edit-form" = "/admin/config/people/host/{host}/edit",
 *     "delete-form" = "/admin/config/people/host/{host}/delete",
 *     "collection" = "/admin/config/people/host/list"
 *   },
 *   field_ui_base_route = "httpbl.host_settings",
 * )
 */
class Host extends ContentEntityBase implements ContentEntityInterface, HostInterface {

  // More work to be done before using this trait.
  // @See https://www.drupal.org/node/2747373
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getHostIp() {
    return $this->get('host_ip')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostIp($ip) {
    $this->get('host_ip')->value = $ip;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostStatus() {
    return $this->get('host_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostStatus($status) {
    $this->get('host_status')->value = $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiry() {
    return $this->get('expire')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpiry($timestamp) {
    $this->get('expire')->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->get('source')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    $this->get('source')->value = $source;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function projectLink($text = 'Project Honeypot') {
    $url = \Drupal\Core\Url::fromUri('http://www.projecthoneypot.org/search_ip.php?ip=' . $this->getHostIp());
    $url_options = [
      'attributes' => [
        'target' => '_blank',
        'title' => t('Project Honey Pot IP Address Inspector.'),
      ]];
    $url->setOptions($url_options);

    return \Drupal\Core\Link::fromTextAndUrl(t($text), $url )->toString();
  }

  /**
   * {@inheritdoc}
   *
   * Implemented in HostQuery.
   */
  public static function getHostsByIp($ip) {}

  /**
   * {@inheritdoc}
   *
   * Implemented in HostQuery.
   */
  public static function loadHostsByIp($ip) {}

  /**
   * {@inheritdoc}
   *
   * Implemented in HostQuery.
   */
  public static function countExpiredHosts($now) {}

  /**
   * {@inheritdoc}
   *
   * Implemented in HostQuery.
   */
  public static function getExpiredHosts($now) {}

  /**
   * {@inheritdoc}
   *
   * Implemented in HostQuery.
   */
  public static function loadExpiredHosts($now) {}

  /**
   * {@inheritdoc}
   *
   * Invalidates an entity's cache tags upon save.
   *
   * Override to always invalidate cache tags.
   *
   */
  protected function invalidateTagsOnSave($update) {
    $tags = $this->getEntityType()->getListCacheTags();
    if ($this->hasLinkTemplate('canonical')) {
      // Creating or updating an entity may change a cached 403 or 404 response.
      $tags = Cache::mergeTags($tags, ['4xx-response']);
    }
    $tags = Cache::mergeTags($tags, $this->getCacheTagsToInvalidate());
    Cache::invalidateTags($tags);
  }

  /**
   * Determines the schema for the base_table property defined above.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['hid'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setDescription(new TranslatableMarkup('The Unique ID of the Host entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    // Host Ip (Name) field for the host.
    $fields['host_ip'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Http:BL Evaluated Host'))
      ->setDescription(new TranslatableMarkup('Evaluated Host IP address.'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 15,
          'text_processing' => 0,
        ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    // Status the host; whether or not it is safe, grey-listed or blacklisted.
    $fields['host_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Host Status'))
      ->setDescription(new TranslatableMarkup('Evaluated status (HTTPBL_LIST_* constants)'))
      ->setRequired(TRUE)
      ->setSetting('size', 'tiny')
      ->setConstraints(array(
        'Range' => array('min' => 0, 'max' => 2),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'integer',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE);

    // Evaluation source for the host.
    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Evaluation Source'))
      ->setDescription(new TranslatableMarkup('Who evaluated this host?'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 32,
          'text_processing' => 0,
        ))
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'string',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('view', TRUE);

    // Expiration timestamp for this host.
    $fields['expire'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Expires'))
      ->setDescription(t('Time this host should be purged (via cron).'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // Created timestamp for this host.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(t('Time this host was created.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    // Changed timestamp for this host.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(t('The time that the host was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setDescription(new TranslatableMarkup('The UUID of the Host entity.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
