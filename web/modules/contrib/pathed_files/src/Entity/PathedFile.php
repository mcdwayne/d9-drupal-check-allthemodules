<?php

namespace Drupal\pathed_file\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the pathed_file entity.
 *
 * @ingroup pathed_file
 *
 * @ConfigEntityType(
 *   id = "pathed_file",
 *   label = @Translation("PathedFile"),
 *   admin_permission = "administer pathed files",
 *   handlers = {
 *     "access" = "Drupal\pathed_file\PathedFileAccessController",
 *     "list_builder" = "Drupal\pathed_file\Controller\PathedFileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pathed_file\Form\PathedFileAddForm",
 *       "edit" = "Drupal\pathed_file\Form\PathedFileEditForm",
 *       "delete" = "Drupal\pathed_file\Form\PathedFileDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/pathed_file.add",
 *     "edit-form" = "/pathed_file.edit",
 *     "delete-form" = "/pathed_file.delete",
 *     "canonical" = "/pathed_file.canonical"
 *   }
 * )
 */
class PathedFile extends ConfigEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Remove the alias for this file.
    foreach ($entities as $entity) {
      \Drupal::service('path.alias_storage')->delete(array('source' => '/pathed-files/' . $entity->id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $entity = $this->load($this->id);
    $source = '/pathed-files/' . $entity->id;

    $alias_service = \Drupal::service('path.alias_storage');
    $alias = $alias_service->load(array('source' => $source));

    $pid = isset($alias['pid']) ? $alias['pid'] : NULL;
    $alias_service->save($source, '/' . $entity->path, LanguageInterface::LANGCODE_NOT_SPECIFIED, $pid);
  }

  /**
   * The pathed_file ID.
   *
   * @var string
   */
  public $id;

  /**
   * The pathed_file UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The pathed_file label.
   *
   * @var string
   */
  public $label;

  /*
   * The pathed file's content.
   *
   * @var string
   */
  public $content;

/*
 * The pathed file's URL path (relative to base URL).
 *
 * @var string
 */
  public $path;
}
