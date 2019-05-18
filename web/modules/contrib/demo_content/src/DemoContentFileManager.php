<?php

/**
 * @file
 * Contains \Drupal\demo_content\DemoContentFileManager.
 */

namespace Drupal\demo_content;

use Drupal\file\Entity\File;

/**
 * Class DemoContentFileManager
 * 
 * @package Drupal\demo_content
 */
class DemoContentFileManager {

  /**
   * Create a file entity.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  public function create(array $values) {
    $path = $values['path'];
    $path_info = pathinfo($path);
    $file_destination_path = file_unmanaged_copy($path);
    unset($values['path']);

    $values += [
      'filename' => $path_info['basename'],
      'uri' => $file_destination_path,
      'status' => 1,
    ];

    // Create a File.
    $file = File::create($values);

    // Save the file.
    $file->save();

    return $file;
  }

  /**
   * Create a file entity.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  public function update($file, array $values) {
    $path = $values['path'];
    $path_info = pathinfo($path);
    $file_destination_path = file_unmanaged_copy($path);
    unset($values['path']);

    $values += [
      'filename' => $path_info['basename'],
      'uri' => $file_destination_path,
      'status' => 1,
    ];

    foreach ($values as $key => $value) {
      $file->set($key, $value);
    }

    // Save the file.
    $file->save();

    return $file;
  }
}
