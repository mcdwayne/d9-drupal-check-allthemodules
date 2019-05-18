<?php

/**
 * @file
 * Contains \Drupal\file_compressor_field\Plugin\Field\FieldType\FileCompressorFieldItemList.
 */

namespace Drupal\file_compressor_field\Plugin\Field\FieldType;

use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\file_compressor_field\Plugin\FileCompressorPluginInterface;

/**
 * Represents a configurable entity file compressor field.
 */
class FileCompressorFieldItemList extends FileFieldItemList implements FileCompressorFieldItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $langcodes = ($this->getFieldDefinition()->isTranslatable()) ? array($this->getLangcode()) : array_keys(\Drupal::languageManager()->getLanguages());
    foreach (array_filter($this->getFieldDefinition()->getSetting('compressed_fields')) as $field_to_compress) {
      foreach ($langcodes as $current_langcode) {
        $field_items = $this->getEntity()->getTranslation($current_langcode)->{$field_to_compress};
        if (!$field_items->isEmpty()) {
          foreach ($field_items as $field_item) {
            if (isset($field_item->target_id) && $field_item->target_id > 0 && !isset($files[$field_item->target_id])) {
              $current_file = File::load($field_item->target_id);
              $files[$current_file->fid->value] = $current_file->uri->value;
            }
          }
        }
      }
    }

    if (!empty($files) && $compressed_file = $this->generateCompressedFile($files) ) {
      $item = array(
        'target_id' => $compressed_file->fid->value,
        'display' => 1,
      );
      $this->set(0, $item);
    }
  }

  /**
   * Generates a compressed file given a list of URIs,
   *
   * @param array $files
   *   Array containing file uris to compress,
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   The compressed ile entity, FALSE otherwise.
   */
  protected function generateCompressedFile($files) {
    $file_compressor_manager = \Drupal::service('plugin.manager.file_compressor');
    /** @var FileCompressorPluginInterface $file_compressor */
    $file_compressor = $file_compressor_manager->createInstance($this->getSetting('file_compressor'));
    $create_compressed_file = $this->isEmpty() || $this->getEntity()->isNewRevision();
    if ($create_compressed_file) {
      $directory_uri = $this->getUploadLocation();
      if (!file_prepare_directory($directory_uri, FILE_CREATE_DIRECTORY)) {
        drupal_set_message('The directory wasn\'t created or was not writable', 'error');
        return FALSE;
      }
      do {
        $file_uri = $file_compressor->generateCompressedFileUri($directory_uri . '/compressed_file_' . user_password(4));
        $exists = file_exists($file_uri);
      } while ($exists);
    }
    else {
      $compressed_field_components = File::load($this->target_id);
      $file_uri = $compressed_field_components->getFileUri();
    }

    if ($file_compressor->generateCompressedFile($file_uri, $files)) {
      if ($create_compressed_file) {
        $user = \Drupal::currentUser();
        $compressed_field_components = entity_create('file', array(
          'uri' => $file_uri,
          'uid' => $user->id(),
          'status' => FILE_STATUS_PERMANENT,
        ));
        drupal_register_shutdown_function(array(get_class($this), 'fileCompressorFieldStore'), $this, $file);
      }
      else {
        $compressed_field_components->setFileUri($file_uri);
      }
      $compressed_field_components->save();
      return $compressed_field_components;
    }

  return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUploadLocation($data = array()) {
    $settings = $this->getFieldDefinition()->getSettings();
    $destination = trim($settings['file_directory'], '/');

    // Replace tokens.
    $destination = \Drupal::token()->replace($destination, $data);

    return $settings['uri_scheme'] . '://' . $destination;
  }

  /**
   * Renames and stores the compressed file in its definitive destination.
   *
   * @param \Drupal\file_compressor_field\Plugin\Field\FieldType\FileCompressorFieldItemListInterface $field
   *   The FileCompressorFieldItemList definition.
   * @param \Drupal\file\Entity\File $file
   *   The File entity to move and store.
   */
  public static function fileCompressorFieldStore(FileCompressorFieldItemListInterface $field, File $file) {
    $entity = \Drupal::entityManager()->loadEntityByUuid($field->getFieldDefinition()->getTargetEntityTypeId(), $field->getEntity()->uuid->value);
    $file_compressor_manager = \Drupal::service('plugin.manager.file_compressor');
    /** @var FileCompressorPluginInterface $file_compressor */
    $file_compressor = $file_compressor_manager->createInstance($field->getSetting('file_compressor'));
    $values[] = $field->getEntity()->getEntityTypeId();
    $values[] = $entity->id();
    // Check whether the entity type supports revisions and initialize it if so.
    if ($field->getEntity()->getEntityType()->isRevisionable()) {
      $values[] = $entity->{$field->getEntity()->getEntityType()->getKey('revision')}->value;
    }
    if ($field->getFieldDefinition()->isTranslatable()) {
      $values[] = $field->getLangcode();
    }
    $destination = $field->getUploadLocation() . '/' . implode('-', $values);
    $destination = $file_compressor->generateCompressedFileUri($destination);
    if ($destination = file_unmanaged_move($file->getFileUri(), $destination, FILE_EXISTS_RENAME)) {
      $file->setFileUri($destination);
      $file->setFilename(drupal_basename($destination));
      $file->save();
    }
  }

}
