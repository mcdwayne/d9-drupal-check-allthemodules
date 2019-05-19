<?php

namespace Drupal\sponsor\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Sponsor entity.
 *
 * @ingroup sponsor
 *
 * @ContentEntityType(
 *   id = "sponsor",
 *   label = @Translation("Sponsor"),
 *   bundle_label = @Translation("Sponsor type"),
 *   handlers = {
 *     "storage" = "Drupal\sponsor\SponsorStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\sponsor\SponsorListBuilder",
 *     "views_data" = "Drupal\sponsor\Entity\SponsorViewsData",
 *     "translation" = "Drupal\sponsor\SponsorTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\sponsor\Form\SponsorForm",
 *       "add" = "Drupal\sponsor\Form\SponsorForm",
 *       "edit" = "Drupal\sponsor\Form\SponsorForm",
 *       "delete" = "Drupal\sponsor\Form\SponsorDeleteForm",
 *     },
 *     "access" = "Drupal\sponsor\SponsorAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\sponsor\SponsorHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "sponsor",
 *   data_table = "sponsor_field_data",
 *   revision_table = "sponsor_revision",
 *   revision_data_table = "sponsor_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer sponsor entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/sponsor/{sponsor}",
 *     "add-page" = "/sponsor/add",
 *     "add-form" = "/sponsor/add/{sponsor_type}",
 *     "edit-form" = "/sponsor/{sponsor}/edit",
 *     "delete-form" = "/sponsor/{sponsor}/delete",
 *     "version-history" = "/sponsor/{sponsor}/revisions",
 *     "revision" = "/sponsor/{sponsor}/revisions/{sponsor_revision}/view",
 *     "revision_revert" = "/sponsor/{sponsor}/revisions/{sponsor_revision}/revert",
 *     "revision_delete" = "/sponsor/{sponsor}/revisions/{sponsor_revision}/delete",
 *     "translation_revert" = "/sponsor/{sponsor}/revisions/{sponsor_revision}/revert/{langcode}",
 *     "collection" = "/sponsor",
 *   },
 *   bundle_entity_type = "sponsor_type",
 *   field_ui_base_route = "entity.sponsor_type.edit_form"
 * )
 */
class Sponsor extends RevisionableContentEntityBase implements SponsorInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the sponsor owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
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

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Sponsor entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Sponsor entity.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Sponsor is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['sponsor_body'] = BaseFieldDefinition::create('text_with_summary')
      ->setLabel(t('Body'))
      ->setDescription(t('Body for Sponsor content.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_with_summary',
        'weight' => 5,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    $fields['sponsor_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Sponsor URL'))
      ->setDescription(t('Link to Sponsor Website.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'URL',
        'type' => 'uri',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 6,
      ]);

    $fields['sponsor_level'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sponsorship Level'))
      ->setDescription(t('Primary Sponsorship Level.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['default_sponsorship_level']])
      ->setDisplayOptions('view', [
        'label' => 'Sponsorship Level',
        'type' => 'entity_reference_entity_view',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 6,
      ]);

    $fields['sponsor_other_levels'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Other Sponsorship Levels'))
      ->setDescription(t('Additional Sponsorship Levels.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['default_sponsorship_level']])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'Sponsorship Level',
        'type' => 'entity_reference_entity_view',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 6,
      ]);

    $fields['sponsor_logo'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sponsor Logo'))
      ->setDescription(t('Media Image of the Sponsor Logo.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setSetting('target_type', 'media')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['image']])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_browser_entity_reference',
        'settings' => [
          'entity_browser' => 'media_browser',
          'field_widget_display' => 'rendered_entity',
          'field_widget_edit' => TRUE,
          'field_widget_remove' => TRUE,
          'selection_mode' => 'selection_append',
          'field_widget_display_settings' => [
            'view_mode' => 'thumbnail',
          ],
          'open' => FALSE,
        ],
        'weight' => 6,
      ]);

    $fields['sponsor_notes'] = BaseFieldDefinition::create('text_with_summary')
      ->setLabel(t('Notes'))
      ->setDescription(t('Notes for this sponsor. Not Visible on sponsor node.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 5,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    $fields['sponsor_users'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Users'))
      ->setDescription(t('Users in the system that are related to this sponsor.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);
    return $fields;
  }

}
