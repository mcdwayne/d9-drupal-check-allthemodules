<?php
/**
 * @file
 * Contains \Drupal\collect\Entity\Container.
 */

namespace Drupal\collect\Entity;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Plugin\collect\Model\CollectJson;
use Drupal\collect\Plugin\collect\Model\FieldDefinition;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\TypedData\TypedDataProvider;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the generic data container entity.
 *
 * @todo Validate containers https://www.drupal.org/node/2449359
 *
 * Each container represents data according to the schema URI.
 * Every schema URI can have a responsible model with a plugin.
 * The plugin is responsible to interpret data.
 *
 * @ContentEntityType(
 *   id = "collect_container",
 *   label = @Translation("Collect data container"),
 *   handlers = {
 *     "storage" = "Drupal\collect\CollectStorage",
 *     "storage_schema" = "Drupal\collect\CollectStorageSchema",
 *     "list_builder" = "Drupal\collect\ContainerListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   admin_permission = "administer collect",
 *   base_table = "collect",
 *   revision_table = "collect_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "origin_uri",
 *     "revision" = "vid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/collect",
 *     "canonical" = "/admin/content/collect/{collect_container}",
 *     "version-history" = "/admin/content/collect/{collect_container}/revisions",
 *   }
 * )
 */
class Container extends ContentEntityBase implements CollectContainerInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The data item ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The data item UUID.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'label' => 'above',
        'weight' => 0,
      ));

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The node revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    // Collect assumes that data can originate from remote sites.
    // The origin URI is an identifier that allows to determine the origin
    // systems as well as the data record on that system.
    // The origin system might do not have a data record but the data was
    // gathered on the fly. The URI in this case identifies that process.
    $fields['origin_uri'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Origin URI'))
      ->setDescription(t('The origin URI of the data item.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'label' => 'hidden',
        'weight' => 0,
      ));

    // While created and changed are tracking the dates on the local system,
    // date tracks a date from the source.
    // It is the responsibility of the source to set this field to an
    // appropriate value.
    // This can be either the created or changed date of an entity or the date
    // of an event like form a form submission.
    $fields['date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date'))
      ->setDescription(t('An event in the lifecycle of the stored data.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => 0,
      ));

    // Some content types are able to declare its data structure embedded in the
    // data (e.g. XML) but some do not (e.g. JSON).
    // The schema URI allows to define models for content types that are not
    // capable of doing that on its own.
    $fields['schema_uri'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Schema URI'))
      ->setDescription(t("The schema URI of collected data."))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'collect_schema_uri',
        'label' => 'above',
        'weight' => 0,
      ));

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('MIME Type'))
      ->setDescription(t("The MIME Type of collected data."))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'label' => 'above',
        'weight' => 0,
      ));

    // Stores data.
    // @see \Drupal\collect\Plugin\Field\FieldType\CollectDataItem.
    $fields['data'] = BaseFieldDefinition::create('collect_data')
      ->setLabel(t('Data'))
      ->setDescription(t("The data collected in this item."))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'collect_data',
        'label' => 'above',
        'weight' => 0,
      ));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The timestamp that the data item was created.'))
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the data item was last changed.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = array()) {
    $values += array(
      'date' => REQUEST_TIME,
    );
    return parent::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginUri($origin_uri) {
    $this->set('origin_uri', $origin_uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginUri() {
    return $this->get('origin_uri')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchemaUri($schema_uri) {
    $this->set('schema_uri', $schema_uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaUri() {
    return $this->get('schema_uri')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $type);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->get('data')->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->get('data')->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getDate() {
    return $this->get('date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDate($date) {
    $this->set('date', $date);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Special case: When a field definition container is updated, resave
    // corresponding CollectJSON model to trigger regeneration of property
    // definitions. Note that this feature is not implemented for combined
    // CollectJSON, i.e. where field definitions are saved in the same container
    // as the values.
    if ($this->getSchemaUri() == FieldDefinition::URI) {
      // Find the CollectJSON model that will use this container for field
      // definitions.
      $models = \Drupal::entityManager()->getStorage('collect_model')
        ->loadByProperties(['uri_pattern' => $this->getOriginUri()]);
      if (!empty($models)) {
        /** @var TypedDataProvider $typed_data_provider */
        $typed_data_provider = \Drupal::service('collect.typed_data_provider');
        /** @var Model $model */
        $model = current($models);
        // Add the field definitions one by one, wrapped in PropertyDefinition
        // objects.
        foreach ($typed_data_provider->getTypedData($this)->get('fields')->getValue() as $field_name => $field_definition) {
          $model->setTypedProperty($field_name, new PropertyDefinition($field_name, $field_definition));
        }
        $model->save();
      }
    }

    // Always trigger processing.
    \Drupal::service('collect.postprocessor')->process($this);
  }

}
