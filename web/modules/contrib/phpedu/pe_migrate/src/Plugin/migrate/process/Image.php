<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\pe_migrate\process\Image.
 */

namespace Drupal\pe_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\Component\Render\PlainTextOutput;

/**
 * This plugin uploads the image file and build the field array.
 *
 * @MigrateProcessPlugin(
 *   id = "image",
 *   handle_multiples = TRUE
 * )
 */
class Image extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $entity_type = $this->configuration['entity_type'];
    $bundle = $this->configuration['bundle'];
    // user pictures.
    if ($entity_type == 'user') {
      $value = $row->getSourceProperty('name') . '.jpg';
    }

    $src = \Drupal::root() . '/' . drupal_get_path('module', 'pe_migrate') . '/data/';
    $src .= $entity_type . '/images/';
    if (!empty($bundle)) {
      $src .= $bundle . '/';
    }
    $src .= $value;

    if (is_file($src)) {
      // set bundle to entity_type for user as we need it for field definition.
      if ($entity_type == 'user') {
        $bundle =  $entity_type;
      }
      $field_definition = FieldConfig::loadByName($entity_type, $bundle, $destination_property);

      // Copy file to temporary location.
      $destination = 'temporary://' . $value;
      file_unmanaged_copy($src, $destination, FILE_CREATE_DIRECTORY);

      $path = \Drupal::service('file_system')->realpath($destination);

      // create file object.
      $image = File::create();
      $image->setFileUri($path);
      $image->setOwnerId(\Drupal::currentUser()->id());
      $image->setMimeType('image/' . pathinfo($path, PATHINFO_EXTENSION));
      $image->setFileName(\Drupal::service('file_system')->basename($path));
      $destination_dir = static::getUploadLocation($field_definition->getSettings());
      file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY);
      $destination = $destination_dir . '/' . basename($path);
      $file = file_move($image, $destination, FILE_CREATE_DIRECTORY);

      if($file instanceof File && !empty($file->getFileUri())) {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        list($width, $height) = getimagesize($file->getFileUri());

        // Overwrite $value with file data.
        $value = array(
          'target_id' => $file->id(),
          'alt' => $filename,
          'title' => $filename,
          'width' => $width,
          'height' => $height,
        );
      }
    }
    else {
      $value = [];
    }

    return $value;
  }

  /**
   * @param array $settings
   * @param array $data
   * @return string
   */
  public static function getUploadLocation(array $settings, $data = []) {
    $destination = trim($settings['file_directory'], '/');
    // Replace tokens and convert it to plain text because of any HTML in it.
    $destination = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($destination, $data));
    return $settings['uri_scheme'] . '://' . $destination;
  }
}
