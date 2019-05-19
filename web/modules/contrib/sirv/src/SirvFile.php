<?php

namespace Drupal\sirv;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * Provides functions for Sirv files.
 */
class SirvFile {

  /**
   * Render API callback.
   */
  public static function value(&$element, $input, FormStateInterface $form_state) {
    // TODO: Use the original value callback (#original_value_callback),
    // instead of hard-coding it.
    $return = ImageWidget::value($element, $input, $form_state);

    if ($input === FALSE) {
      return $return;
    }

    $upload_name = implode('_', $element['#parents']);
    $all_files = \Drupal::request()->files->get('files', []);
    if (empty($all_files[$upload_name]) || empty($return['fids'])) {
      return $return;
    }

    foreach ($return['fids'] as $fid) {
      static::copyFileToSirv($fid);
    }

    return $return;
  }

  /**
   * Copy a file to Sirv.
   *
   * @param int $fid
   *   The ID of the file.
   */
  public static function copyFileToSirv($fid) {
    // Load the file entity.
    $source_entity = \Drupal::entityTypeManager()->getStorage('file')->load($fid);

    // Get the source file's URI.
    $source_uri = $source_entity->getFileUri();
    if (!$source_uri) {
      return;
    }

    // Get the source file's scheme.
    $source_scheme = file_uri_scheme($source_uri);

    // Define the destination file's scheme and URI.
    $destination_scheme = 'sirv';
    $destination_uri = preg_replace("/^$source_scheme/", $destination_scheme, $source_uri);

    // Copy the file.
    $destination_dir = \Drupal::service('file_system')->dirname($destination_uri);
    file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY);
    file_unmanaged_copy($source_uri, $destination_uri, FILE_EXISTS_RENAME);
  }

}
