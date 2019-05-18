<?php

namespace Drupal\prefetcher\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
/**
 * Defines the Prefetcher uri entity.
 *
 * @ingroup prefetcher
 *
 * @ContentEntityType(
 *   id = "prefetcher_uri",
 *   label = @Translation("Prefetcher uri"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\prefetcher\Entity\PrefetcherUriViewsData",
 *     "translation" = "Drupal\prefetcher\PrefetcherUriTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\prefetcher\Form\PrefetcherUriForm",
 *       "add" = "Drupal\prefetcher\Form\PrefetcherUriForm",
 *       "edit" = "Drupal\prefetcher\Form\PrefetcherUriForm",
 *       "delete" = "Drupal\prefetcher\Form\PrefetcherUriDeleteForm",
 *     },
 *     "access" = "Drupal\prefetcher\PrefetcherUriAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\prefetcher\PrefetcherUriHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "prefetcher_uri",
 *   data_table = "prefetcher_uri_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer prefetcher uri entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/prefetcher_uri/{prefetcher_uri}",
 *     "add-form" = "/admin/structure/prefetcher_uri/add",
 *     "edit-form" = "/admin/structure/prefetcher_uri/{prefetcher_uri}/edit",
 *     "delete-form" = "/admin/structure/prefetcher_uri/{prefetcher_uri}/delete",
 *     "collection" = "/admin/structure/prefetcher_uri",
 *     "inactive-collection" = "/admin/structure/inactive_prefetcher_uri",
 *   },
 *   field_ui_base_route = "entity.prefetcher_uri.collection",
 *   entity_type_class = "Drupal\prefetcher\Entity\PrefetcherUriType"
 * )
 */
class PrefetcherUri extends ContentEntityBase implements PrefetcherUriInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    #$values += array(
    #  'user_id' => \Drupal::currentUser()->id(),
    #);
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->get('uri')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($name) {
    $this->set('uri', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->get('relpath')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->set('relpath', $path);
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uri'] = BaseFieldDefinition::create('string' )
      ->setLabel(t('URI'))
      ->setDescription(t('The URI to access the content.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['relpath'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Path'))
      ->setDescription(t('The path to access the content. Domain(s) will be retrieved from configuration.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 2,
      ));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Prefetcher uri is active.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('Entity type of uri content.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity id'))
      ->setDescription(t('Entity id of uri content.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 20,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 20,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_crawled'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Last crawled'))
      ->setSetting('datetime_type', 'datetime')
      ->setDescription(t('Datetime value of last time uri was crawled.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 30,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 30,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expires'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Expires'))
      ->setSetting('datetime_type', 'datetime')
      ->setDescription(t('Datetime value of expire date of crawled page.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 40,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 40,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);



    $fields['response_info'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Response http info'))
      ->setDescription(t('Response http info.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 70,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_response_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Last response code'))
      ->setDescription(t('Last response code.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 50,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 50,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_response_size'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Last response size'))
      ->setDescription(t('Last response size.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 50,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 50,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tries'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Retry count'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 50,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 50,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  public function label() {
    if (!empty($this->getUri())) {
      return $this->getUri();
    } elseif (!empty($this->getPath())) {
      return $this->getPath();
    }
    return $this->getOriginalId();
  }

  public function status() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function jumpTheQueue() {
    $date = new \DateTime('now');
    $this->set('expires', $date->format('Y-m-d\TH:i:s'));
    $date->setTimestamp(rand(1000, 100000));
    $this->set('last_crawled', $date->format('Y-m-d\TH:i:s'));
  }

}
