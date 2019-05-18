<?php

namespace Drupal\image_hotspots\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\image_hotspots\ImageHotspotInterface;

/**
 * Defines the image hotspot entity class.
 *
 * @ingroup image_hotspots
 *
 * @ContentEntityType(
 *   id = "image_hotspot",
 *   label = @Translation("Image hotspot"),
 *   base_table = "image_hotspot",
 *   entity_keys = {
 *     "id" = "hid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class ImageHotspot extends ContentEntityBase implements ImageHotspotInterface {

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    return [
      'field_name' => $this->field_name->getValue()[0]['target_id'],
      'fid' => $this->fid->getValue()[0]['target_id'],
      'image_style' => $this->image_style->getValue()[0]['target_id'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getUid() {
    return $this->uuid->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title->value = $title;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description->value = $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getLink() {
    return $this->link->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLink($url) {
    $this->link->value = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoordinates() {
    return [
      'x' => $this->x->value,
      'y' => $this->y->value,
      'x2' => $this->x2->value,
      'y2' => $this->y2->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setCoordinates(array $coordinates) {
    $this->x->value = $coordinates['x'];
    $this->y->value = $coordinates['y'];
    $this->x2->value = $coordinates['x2'];
    $this->y2->value = $coordinates['y2'];
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByTarget(array $values) {
    $storage = \Drupal::entityTypeManager()->getStorage('image_hotspot');
    return $storage->loadByProperties($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['hid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Hid'))
      ->setDescription(t('The hotspot id.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('Uuid'))
      ->setDescription(t('The hotspot uuid.'))
      ->setReadOnly(TRUE);

    $fields['field_name'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Image field with hotspot'))
      ->setDescription(t('The id of image field with the hotspot.'))
      ->setSetting('target_type', 'field_config');

    $fields['fid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('File Id'))
      ->setDescription(t('Image file id.'))
      ->setSetting('target_type', 'file');

    $fields['image_style'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Image style'))
      ->setDescription(t('Image style.'))
      ->setSetting('target_type', 'image_style');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Id'))
      ->setDescription(t('The id of user that created hotspot.'))
      ->setSetting('target_type', 'user');

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Title of the hotspot.'));

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Description of the hotspot.'));

    $fields['link'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Link'))
      ->setDescription(t('link of the hotspot.'));

    $fields['x'] = BaseFieldDefinition::create('float')
      ->setLabel(t('X coordinate'))
      ->setDescription(t('The X coordinate of hotspot.'))
      ->setSetting('unsigned', TRUE);

    $fields['y'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Y coordinate'))
      ->setDescription(t('The Y coordinate of hotspot.'))
      ->setSetting('unsigned', TRUE);

    $fields['y2'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Y2 coordinate'))
      ->setDescription(t('The Y2 coordinate of hotspot.'))
      ->setSetting('unsigned', TRUE);

    $fields['x2'] = BaseFieldDefinition::create('float')
      ->setLabel(t('X2 coordinate'))
      ->setDescription(t('The X2 coordinate of hotspot.'))
      ->setSetting('unsigned', TRUE);

    return $fields;
  }

}
