<?php

namespace Drupal\udheader\Entity;

use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\udheader\HeaderInterface;
use Drupal\udheader\Utils;
use Drupal\user\UserInterface;

/**
 * Defines the Ubuntu Drupal Header entity.
 *
 * @ingroup udheader
 *
 * @ContentEntityType(
 *   id = "udheader",
 *   label = @Translation("Ubuntu Drupal Header entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\udheader\Controllers\HeaderListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\udheader\Form\HeaderForm",
 *       "edit" = "Drupal\udheader\Form\HeaderForm",
 *       "delete" = "Drupal\udheader\Form\HeaderDeleteForm",
 *     },
 *     "access" = "Drupal\udheader\HeaderAccessControlHandler",
 *   },
 *   base_table = "udheader_entity",
 *   revision_table = "udheader_revision",
 *   admin_permission = "administer udheader entity",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "published" = "published",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/udheader/{udheader}",
 *     "edit-form" = "/udheader/{udheader}/edit",
 *     "delete-form" = "/udheader/{udheader}/delete",
 *     "collection" = "/udheader/list",
 *   },
 * )
 */
class Header extends ContentEntityBase implements HeaderInterface {
  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields  = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $node_types = node_type_get_names();
    $node_types['udheader_default'] = 'Default';
    $images = Utils::get_images();

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Header name'))
      ->setDescription(t('The name of the Ubuntu Drupal Header.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        '#label_display' => 'hidden',
        '#type' => 'string',
        '#weight' => -6,
      ])
      ->setDisplayOptions('form', [
        '#type' => 'string_textfield',
        '#weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['node'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Node Type'))
      ->setDescription(t('The node type this header targets.'))
      ->setSettings([
        'allowed_values' => $node_types,
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        '#type' => 'options_select',
        '#weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['left_image'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Left image'))
      ->setDescription(t('The image for the left pane of the header.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'allowed_values' => $images['left'],
      ])
      ->setDisplayOptions('view', [
        '#label_display' => 'hidden',
        '#type' => 'list_default',
        '#weight' => -4,
      ])
      ->setDisplayOptions('form', [
        '#type' => 'options_select',
        '#weight' => -4,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['center_image'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Center image'))
      ->setDescription(t('The image for the middle pane of the header.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'allowed_values' => $images['center'],
      ])
      ->setDisplayOptions('view', [
        '#label_display' => 'hidden',
        '#type' => 'list_default',
        '#weight' => -3,
      ])
      ->setDisplayOptions('form', [
        '#type' => 'options_select',
        '#weight' => -3,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['right_image'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Right image'))
      ->setDescription(t('The image for the right pane of the header.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'allowed_values' => $images['right'],
      ])
      ->setDisplayOptions('view', [
        '#label_display' => 'hidden',
        '#type' => 'list_default',
        '#weight' => -3,
      ])
      ->setDisplayOptions('form', [
        '#type' => 'options_select',
        '#weight' => -3,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['center_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Middle content'))
      ->setDescription(t('The textual content of the middle pane.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'text_processing' => 1,
      ])
      ->setDisplayOptions('view', [
        '#label_display' => 'hidden',
        '#type' => 'text',
        '#weight' => -2,
      ])
      ->setDisplayOptions('form', [
        '#type' => 'string_textarea',
        '#weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['right_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Right content'))
      ->setDescription(t('The textual content of the right pane.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'text_processing' => 1,
      ])
      ->setDisplayOptions('view', [
        '#label_display' => 'hidden',
        '#type' => 'text',
        '#weight' => -1,
      ])
      ->setDisplayOptions('form', [
        '#type' => 'string_textarea',
        '#weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}
