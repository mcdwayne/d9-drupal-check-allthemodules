<?php

namespace Drupal\searchcloud_block\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\searchcloud_block\SearchCloudInterface;

/**
 * Defines the searchcloud entity.
 *
 * @ContentEntityType(
 *   id = "searchcloud_block",
 *   label = @Translation("Searchcloud entity"),
 *   controllers = {
 *     "list" = "Drupal\searchcloud_block\Entity\Controller\SearchCloudListController",
 *
 *     "form" = {
 *       "add" = "Drupal\searchcloud_block\Entity\Form\SearchCloudFormController",
 *       "edit" = "Drupal\searchcloud_block\Entity\Form\SearchCloudFormController",
 *       "delete" = "Drupal\searchcloud_block\Entity\Form\SearchCloudDeleteForm",
 *     },
 *   },
 *   base_table = "searchcloud_block_count",
 *   admin_permission = "administer searchcloud",
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "stid",
 *     "label" = "keyword",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "searchcloud_block.entity.edit",
 *     "admin-form" = "searchcloud_block.base",
 *     "delete-form" = "searchcloud_block.entity.delete"
 *   }
 * )
 */
class SearchCloud extends ContentEntityBase implements SearchCloudInterface {

  /**
   * The searcloud term ID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $stid;

  /**
   * The searcloud UUID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $uuid;

  /**
   * Name of the term.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $keyword;

  /**
   * Count of the term.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $count;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['stid'] = FieldDefinition::create('integer')->setLabel(t('ID'))
      ->setDescription(t('The ID of the searchcloud entity.'))->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the searchcloud entity.'))->setReadOnly(TRUE);

    $fields['keyword'] = FieldDefinition::create('string')->setLabel(t('Keyword'))
      ->setDescription(t('The keyword of the searchcloud entity.'));

    $fields['count'] = FieldDefinition::create('integer')->setLabel(t('Count'))
      ->setDescription(t('The count of the searchcloud entity.'));

    $fields['hide'] = FieldDefinition::create('integer')->setLabel(t('Hide'))
      ->setDescription(t('The hide of the searchcloud entity.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('stid')->value;
  }

  /**
   * Check if the keyword already exists.
   *
   * @return int|bool
   *   FALSE or stid of the keyword.
   */
  public function checkDuplicate() {
    $entity_ids = \Drupal::entityQuery('searchcloud_block')->condition('keyword', $this->keyword->value)
      ->condition('count', $this->count->value)->execute();

    if (empty($entity_ids)) {
      return FALSE;
    }
    else {
      reset($entity_ids);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function init() {
    parent::init();
    unset($this->stid);
    unset($this->uuid);
    unset($this->keyword);
    unset($this->count);
  }

}
