<?php

namespace Drupal\css_background\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Render\Markup;
use Drupal\user\UserInterface;

/**
 * Defines the CssBackground entity.
 *
 * @ingroup css_background
 *
 * @ContentEntityType(
 *   id = "css_background",
 *   label = @Translation("CSS background"),
 *   bundle_label = @Translation("CSS background type"),
 *   handlers = {
 *     "storage" = "Drupal\css_background\CssBackgroundEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\css_background\CssBackgroundEntityListBuilder",
 *     "views_data" = "Drupal\css_background\Entity\CssBackgroundEntityViewsData",
 *     "translation" = "Drupal\css_background\CssBackgroundEntityTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\css_background\Form\CssBackgroundEntityForm",
 *       "add" = "Drupal\css_background\Form\CssBackgroundEntityForm",
 *       "edit" = "Drupal\css_background\Form\CssBackgroundEntityForm",
 *       "delete" = "Drupal\css_background\Form\CssBackgroundEntityDeleteForm",
 *     },
 *     "access" = "Drupal\css_background\CssBackgroundEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\css_background\CssBackgroundEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "css_background",
 *   data_table = "css_background_field_data",
 *   revision_table = "css_background_revision",
 *   revision_data_table = "css_background_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer hcp css_background entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/css-background/{css_background}",
 *     "add-page" = "/css-background/add",
 *     "add-form" = "/css-background/add/{css_background_type}",
 *     "edit-form" = "/css-background/{css_background}/edit",
 *     "delete-form" = "/css-background/{css_background}/delete",
 *     "version-history" = "/css-background/{css_background}/revisions",
 *     "revision" = "/css-background/{css_background}/revisions/{css_background_revision}/view",
 *     "revision_revert" = "/css-background/{css_background}/revisions/{css_background_revision}/revert",
 *     "translation_revert" = "/css-background/{css_background}/revisions/{css_background_revision}/revert/{langcode}",
 *     "revision_delete" = "/css-background/{css_background}/revisions/{css_background_revision}/delete",
 *     "collection" = "/admin/content/css_background",
 *   },
 *   bundle_entity_type = "css_background_type",
 *   field_ui_base_route = "entity.css_background_type.edit_form"
 * )
 */
class CssBackgroundEntity extends RevisionableContentEntityBase implements CssBackgroundEntityInterface {

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
  public function label() {
    return $this->getCss(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCss($summary = FALSE) {
    $css = [];

    $file = $this->getBgImage();
    if ($file) {
      if ($summary) {
        $css[] = $file->getFilename();
      }
      else {
        $css[] = 'url(' . file_create_url($file->getFileUri()) . ')';
      }
    }

    $color = $this->getBgColor();
    if ($color) {
      $css[] = $color;
    }

    $properties = $this->getBgProperties();
    if ($properties) {
      $css[] = $properties;
    }

    return implode(' ', $css);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getBgImage() {
    return $this->get('bg_image')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setBgImage(ImageInterface $image) {
    $this->set('bg_image', $image);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBgColor() {
    return $this->get('bg_color')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBgColor($name) {
    $this->set('bg_color', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBgProperties() {
    return $this->get('bg_properties')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBgProperties($properties) {
    $this->set('bg_properties', $properties);
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
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUser() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUserId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    /** @var \Drupal\Core\StringTranslation\TranslationManager $translation */
    $translation = \Drupal::translation();

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel($translation->translate('Authored by'))
      ->setDescription($translation->translate('The user ID of author of the CSS background entity.'))
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
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['bg_image'] = BaseFieldDefinition::create('image')
      ->setLabel($translation->translate('Image'))
      ->setDescription($translation->translate('The image of the CSS background entity.'))
      ->setSettings([
        'alt_field' => 0,
        'file_extensions' => 'png gif jpg jpeg',
        'handler' => 'default',
        'target_type' => 'file',
        'title_field' => 0,
        'uri_scheme' => 'public',
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'image',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['bg_color'] = BaseFieldDefinition::create('string')
      ->setLabel($translation->translate('Color'))
      ->setDescription($translation->translate('The background color.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 32,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['bg_properties'] = BaseFieldDefinition::create('string')
      ->setLabel($translation->translate('Properties'))
      ->setDescription($translation->translate('Additional CSS3 background properties. See <a href="@url">@url</a>. For example @example', [
        '@url' => 'https://www.w3schools.com/css/css3_backgrounds.asp',
        '@example' => Markup::create('<ul><li><code>' . implode('</code></li><li><code>', [
          'top left / 100px 100px no-repeat',
          'top left / cover no-repeat',
        ]) . '</code></li></ul>'),
      ]))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'size' => 60,
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel($translation->translate('Publishing status'))
      ->setDescription($translation->translate('A boolean indicating whether the CSS background is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel($translation->translate('Created'))
      ->setDescription($translation->translate('The time that the entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel($translation->translate('Changed'))
      ->setDescription($translation->translate('The time that the entity was last edited.'));

    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel($translation->translate('Revision timestamp'))
      ->setDescription($translation->translate('The time that the current revision was created.'))
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel($translation->translate('Revision user ID'))
      ->setDescription($translation->translate('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel($translation->translate('Revision translation affected'))
      ->setDescription($translation->translate('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Set all values of the entity based on the array input values.
   *
   * @param array $values
   *   The new values.
   */
  public function setValues(array $values) {
    foreach ($values as $field_name => $value) {
      $this->set($field_name, $value ? $value : NULL);
    }
  }

  /**
   * Return if the new values are the same as the current values.
   *
   * @param array $values
   *   The new values.
   *
   * @return bool
   *   Returns TRUE if the values are equal.
   *
   * @todo: Is there a better performant way to compare that does not clone?
   */
  public function equals(array $values) {
    $new = clone $this;
    $new->setValues($values);
    foreach ($values as $field_name => $value) {
      if (!$this->get($field_name)->equals($new->get($field_name))) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
