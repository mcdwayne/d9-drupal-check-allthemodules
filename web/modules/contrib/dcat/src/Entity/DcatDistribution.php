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
 * Defines the Distribution entity.
 *
 * @ingroup dcat
 *
 * @ContentEntityType(
 *   id = "dcat_distribution",
 *   label = @Translation("Distribution"),
 *   label_singular = @Translation("Distribution"),
 *   label_plural = @Translation("Distributions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count distribution",
 *     plural = "@count distributions",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dcat\DcatDistributionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dcat\Form\DcatDistributionForm",
 *       "add" = "Drupal\dcat\Form\DcatDistributionForm",
 *       "edit" = "Drupal\dcat\Form\DcatDistributionForm",
 *       "delete" = "Drupal\dcat\Form\DcatDistributionDeleteForm",
 *     },
 *     "access" = "Drupal\dcat\DcatDistributionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dcat\DcatDistributionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dcat_distribution",
 *   data_table = "dcat_distribution_field_data",
 *   admin_permission = "administer distribution entities",
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
 *     "canonical" = "/distribution/{dcat_distribution}",
 *     "add-form" = "/admin/structure/dcat/distribution/add",
 *     "edit-form" = "/distribution/{dcat_distribution}/edit",
 *     "delete-form" = "/distribution/{dcat_distribution}/delete",
 *     "collection" = "/admin/structure/dcat/distribution",
 *   },
 *   field_ui_base_route = "dcat_distribution.settings"
 * )
 */
class DcatDistribution extends ContentEntityBase implements DcatDistributionInterface {

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
      ->setDescription(t('The user ID of author of the Distribution entity.'))
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
      ->setLabel(t('Distribution IRI'))
      ->setDescription(t('The (external) distribution IRI of the distribution, e.g. http://example.com/distribution-001.'))
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
      ));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the distribution, e.g. CSV distribution of imaginary dataset 001.'))
      ->setSettings(array(
        'max_length' => 1020,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
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
      ->setDescription(t('A boolean indicating whether the Distribution is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['issued'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Issued'))
      ->setDescription(t('Date of formal issuance (e.g., publication) of the distribution.'))
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
      ->setDescription(t('Most recent date on which the distribution was changed, updated or modified.'))
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
