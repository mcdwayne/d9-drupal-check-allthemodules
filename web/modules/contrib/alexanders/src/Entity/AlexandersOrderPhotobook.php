<?php

namespace Drupal\alexanders\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Alexanders Photobook entity.
 *
 * @ContentEntityType(
 *   id = "alexanders_order_photobook",
 *   label = @Translation("Alexanders Order Photobook"),
 *   label_singular = @Translation("Alexanders Order Photobook"),
 *   label_plural = @Translation("Alexanders Order Photobooks"),
 *   label_count = @PluralTranslation(
 *     singular = "@count photobook",
 *     plural = "@count photobooks",
 *   ),
 *   base_table = "alexanders_order_photobook",
 *   data_table = "alexanders_order_item_photobook_data",
 *   admin_permission = "administer site settings",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "book_id",
 *     "label" = "sku",
 *   },
 * )
 */
class AlexandersOrderPhotobook extends AlexandersOrderItem {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->get('cover')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFile($url) {
    $this->set('cover', $url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddFile() {
    return $this->get('guts')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddFile($url) {
    $this->set('guts', $url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['cover'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cover URL'))
      ->setDescription(t('URL of cover for Alexanders to print'))
      ->setRequired(TRUE);

    $fields['guts'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Guts URL'))
      ->setDescription(t('URL of guts for Alexanders to print'))
      ->setRequired(TRUE);

    // Remove fields we don't need for this item.
    unset($fields['file'], $fields['foil']);

    return $fields;
  }

}
