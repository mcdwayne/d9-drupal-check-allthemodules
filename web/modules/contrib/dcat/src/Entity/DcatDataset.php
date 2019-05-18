<?php

namespace Drupal\dcat\Entity;

use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Dataset entity.
 *
 * @ingroup dcat
 *
 * @ContentEntityType(
 *   id = "dcat_dataset",
 *   label = @Translation("Dataset"),
 *   label_singular = @Translation("Dataset"),
 *   label_plural = @Translation("Datasets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count dataset",
 *     plural = "@count datasets",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\dcat\DcatDatasetViewBuilder",
 *     "list_builder" = "Drupal\dcat\DcatDatasetListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dcat\Form\DcatDatasetForm",
 *       "add" = "Drupal\dcat\Form\DcatDatasetForm",
 *       "edit" = "Drupal\dcat\Form\DcatDatasetForm",
 *       "delete" = "Drupal\dcat\Form\DcatDatasetDeleteForm",
 *     },
 *     "access" = "Drupal\dcat\DcatDatasetAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dcat\DcatDatasetHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dcat_dataset",
 *   data_table = "dcat_dataset_field_data",
 *   admin_permission = "administer dataset entities",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/dataset/{dcat_dataset}",
 *     "add-form" = "/admin/structure/dcat/dataset/add",
 *     "edit-form" = "/dataset/{dcat_dataset}/edit",
 *     "delete-form" = "/dataset/{dcat_dataset}/delete",
 *     "collection" = "/admin/structure/dcat/dataset",
 *   },
 *   field_ui_base_route = "dcat_dataset.settings"
 * )
 */
class DcatDataset extends ContentEntityBase implements DcatDatasetInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);


    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the dataset.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['external_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dataset IRI'))
      ->setDescription(t('The (external) dataset IRI of the dataset, e.g. http://example.com/dataset-001.'))
      ->setSettings(array(
        'max_length' => 1020,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the dataset, e.g. Imaginary dataset.'))
      ->setSettings(array(
        'max_length' => 1020,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Dataset is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['issued'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Issued'))
      ->setDescription(t('Date of formal issuance (e.g., publication) of the dataset.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['modified'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Modified'))
      ->setDescription(t('Most recent date on which the dataset was changed, updated or modified.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
