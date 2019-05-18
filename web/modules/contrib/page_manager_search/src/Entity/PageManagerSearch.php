<?php

namespace Drupal\page_manager_search\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;


/**
 * Defines the Page manager search entity.
 * Emulate entity for making Page Variant entity searchable.
 *
 * @ingroup page_manager_search
 *
 * @ContentEntityType(
 *   id = "page_manager_search",
 *   label = @Translation("Page Manager Search"),
 *   base_table = "page_manager_search",
 *   entity_keys = {
 *    "id" = "id",
 *    "label" = "name",
 *    "uuid" = "uuid"
 *   },
 *   fieldable = FALSE,
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" =
 *   "Drupal\page_manager_search\PageManagerSearchEntityListBuilder",
 *     "views_data" =
 *   "Drupal\page_manager_search\Entity\PageManagerSearchEntityViewsData",
 *   },
 *   links = {
 *     "canonical" = "/page-manager-search/{page_manager_search}",
 *  },
 *  admin_permission = "administer Page Manager Search entity",
 * )
 */
class PageManagerSearch extends ContentEntityBase implements ContentEntityInterface {

  /**
   * Get referenced Page Variant Entity.
   */
  public function getPageVariant() {
    return $this->get('pid')->entity;
  }

  /**
   * Get referenced Page Variant Id.
   */
  public function getPageVariantId() {
    return $this->get('pid')->target_id;
  }

  /**
   * Set referenced Page Variant Id.
   */
  public function setPageVariantId($pid) {
    return $this->set('pid', $pid);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if ($update) {
      if (\Drupal::moduleHandler()->moduleExists('search')) {
        search_mark_for_reindex('page_manager_search', $this->id());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Page Manager Search entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Page Manager Search entity.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the page.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content'))
      ->setDescription(t('The content of the page.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['path_to_page'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Page Variant path'))
      ->setDescription(t('The path to the Page Variant.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Page variant ID'))
      ->setDescription(t('Page variant ID of the referenced Page.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
