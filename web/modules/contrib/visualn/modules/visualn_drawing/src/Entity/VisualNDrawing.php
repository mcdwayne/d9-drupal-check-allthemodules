<?php

namespace Drupal\visualn_drawing\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\visualn\WindowParametersTrait;

/**
 * Defines the VisualN Drawing entity.
 *
 * @ingroup visualn_drawing
 *
 * @ContentEntityType(
 *   id = "visualn_drawing",
 *   label = @Translation("VisualN Drawing"),
 *   bundle_label = @Translation("VisualN Drawing type"),
 *   handlers = {
 *     "storage" = "Drupal\visualn_drawing\VisualNDrawingStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn_drawing\VisualNDrawingListBuilder",
 *     "views_data" = "Drupal\visualn_drawing\Entity\VisualNDrawingViewsData",
 *     "translation" = "Drupal\visualn_drawing\VisualNDrawingTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\visualn_drawing\Form\VisualNDrawingForm",
 *       "add" = "Drupal\visualn_drawing\Form\VisualNDrawingForm",
 *       "edit" = "Drupal\visualn_drawing\Form\VisualNDrawingForm",
 *       "delete" = "Drupal\visualn_drawing\Form\VisualNDrawingDeleteForm",
 *     },
 *     "access" = "Drupal\visualn_drawing\VisualNDrawingAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\visualn_drawing\VisualNDrawingHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "visualn_drawing",
 *   data_table = "visualn_drawing_field_data",
 *   revision_table = "visualn_drawing_revision",
 *   revision_data_table = "visualn_drawing_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer visualn drawing entities",
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
 *     "canonical" = "/admin/visualn/drawing/{visualn_drawing}",
 *     "add-page" = "/admin/visualn/drawing/add",
 *     "add-form" = "/admin/visualn/drawing/add/{visualn_drawing_type}",
 *     "edit-form" = "/admin/visualn/drawing/{visualn_drawing}/edit",
 *     "delete-form" = "/admin/visualn/drawing/{visualn_drawing}/delete",
 *     "version-history" = "/admin/visualn/drawing/{visualn_drawing}/revisions",
 *     "revision" = "/admin/visualn/drawing/{visualn_drawing}/revisions/{visualn_drawing_revision}/view",
 *     "revision_revert" = "/admin/visualn/drawing/{visualn_drawing}/revisions/{visualn_drawing_revision}/revert",
 *     "revision_delete" = "/admin/visualn/drawing/{visualn_drawing}/revisions/{visualn_drawing_revision}/delete",
 *     "translation_revert" = "/admin/visualn/drawing/{visualn_drawing}/revisions/{visualn_drawing_revision}/revert/{langcode}",
 *     "collection" = "/admin/visualn/drawings-library",
 *   },
 *   bundle_entity_type = "visualn_drawing_type",
 *   field_ui_base_route = "entity.visualn_drawing_type.edit_form"
 * )
 */
class VisualNDrawing extends RevisionableContentEntityBase implements VisualNDrawingInterface {

  use EntityChangedTrait;
  use WindowParametersTrait;

  const THUMBNAIL_IMAGE_STYLE = 'visualn_drawing_thumbnail';

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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the visualn_drawing owner the
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
      ->setDescription(t('The user ID of author of the VisualN Drawing entity.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
	'weight' => 100,
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
      ->setDescription(t('The name of the VisualN Drawing entity.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
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
      ->setDescription(t('A boolean indicating whether the VisualN Drawing is published.'))
      ->setDisplayOptions('form', [
	'type' => 'boolean_checkbox',
	'settings' => [
	  'display_label' => TRUE,
	],
	'weight' => 100,
      ])
      ->setDisplayConfigurable('form', TRUE)
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

    // add default fetcher field and use it by defualt on entity type config page
    // @todo: it doesn't create a separate database table,
    //  instead adds columns to the visualn_drawing_field_data table
    $fields['fetcher'] = BaseFieldDefinition::create('visualn_fetcher')
      ->setLabel(t('Default fetcher'))
      ->setDescription(t('Fetchers collect data and graphics parameters to build a drawing.'))
      //->setSettings([])
      //->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'visualn_fetcher',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'visualn_fetcher',
        'weight' => -3,
      ])
      ->setRequired(FALSE)
      ->setCardinality(1)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      // @todo: should it be translatable?
      //   same for Name field
      //->setTranslatable(TRUE);
      //->setRevisionable(TRUE)

    $fields['thumbnail'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Thumbnail'))
      ->setDescription(t('The thumbnail of the drawing.'))
      ->setSettings([
        'file_directory' => 'drawing_thumbnails/[date:custom:Y]-[date:custom:m]',
        // hide alt field
        'alt_field' => FALSE,
        //'alt_field_required' => FALSE,
        'file_extensions' => 'png jpg jpeg',
      ])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'image',
        'weight' => 0,
        'settings' => [
          'image_style' => 'thumbnail',
        ],
      ))
      ->setDisplayOptions('form', array(
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: add to interface
   */
  public function buildDrawing() {
    // @todo: there are multiple ways to get bundle entity type,
    //    see https://www.drupal.org/docs/8/api/entity-api/working-with-the-entity-api
    $bundle_entity_type = $this->getEntityType()->getBundleEntityType();
    $bundle = $this->bundle();

    // get config entity for the bundle
    $bundle_config_entity = \Drupal::entityTypeManager()->getStorage($bundle_entity_type)->load($bundle);

    // get drawing fetcher field
    $drawing_fetcher_field = $bundle_config_entity->getDrawingFetcherField();
    if (!empty($drawing_fetcher_field)) {
      if (!$this->get($drawing_fetcher_field)->isEmpty()) {
        // fetcher field load the corresponding drawing fetcher plugin to build drawing markup
        // @todo: what if fetcher field has multiple items (can we also configure delta)?
        $fetcher_field_item = $this->get($drawing_fetcher_field)->first();
        $fetcher_field_item->setWindowParameters($this->getWindowParameters());
        $drawing_markup = $fetcher_field_item->buildDrawing();
        //$drawing_markup = $this->get($drawing_fetcher_field)->get(0)->buildDrawing();
      }
      else {
        $drawing_markup = ['#markup' => ''];
      }
    }
    else {
      $drawing_markup = ['#markup' => ''];
    }

    return $drawing_markup;
  }

}
