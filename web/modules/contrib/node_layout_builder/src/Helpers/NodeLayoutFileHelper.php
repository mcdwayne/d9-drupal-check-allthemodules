<?php

namespace Drupal\node_layout_builder\Helpers;

use Drupal\file\Entity\File;

/**
 * Class NodeLayoutFileHelper.
 *
 * Methods for file handlers.
 */
class NodeLayoutFileHelper {

  /**
   * Load file by fid.
   *
   * @param int $fid
   *   ID file.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null|static
   *   File object or NULL.
   */
  public static function loadFileByFid($fid) {
    $file_object = File::load($fid);
    if ($file_object) {
      return $file_object;
    }

    return NULL;
  }

  /**
   * Create file by fid.
   *
   * @param int $fid
   *   ID file.
   */
  public static function createFile($fid) {
    $file = File::load($fid);
    $file_usage = \Drupal::service('file.usage');
    if (gettype($file) == 'object') {
      $file->setPermanent();
      $file->save();
      $file_usage->add($file, 'node_layout_builder', 'file', $fid);
    }
  }

  /**
   * Delete file by fid.
   *
   * @param int $fid
   *   ID file.
   */
  public static function deleteFile($fid) {
    $file = File::load($fid);
    if (gettype($file) == 'object') {
      $file_usage = \Drupal::service('file.usage');
      $list = $file_usage->listUsage($file);
      if (isset($list['node_layout_builder']['file'][$fid])) {
        $count = $list['node_layout_builder']['file'][$fid];
        for ($i = 0; $i < $count; $i++) {
          $file->delete();
        }
      }
      else {
        $file->delete();
      }
    }
  }

  /**
   * Save image background recursively for element and children.
   *
   * @param array $new_data
   *   New data element.
   * @param array $old_data
   *   Old data element (saved).
   */
  public static function saveFileImgBgElementRecursively(array $new_data, array $old_data) {
    foreach ($new_data as $key_new_element => $new_element) {
      $path_old_element = NodeLayoutBuilderHelper::getkeypath($old_data, $key_new_element);
      krsort($path_old_element);
      if ($path_old_element) {
        $old_element_child = NodeLayoutBuilderHelper::getElementFromArrayData($old_data, $path_old_element);
        if (
          isset($old_element_child['#styles']['background']['image'][0]) &&
          isset($new_element['#styles']['background']['image'][0])
        ) {
          $new_fid = $new_element['#styles']['background']['image'][0];
          $old_fid = $old_element_child['#styles']['background']['image'][0];
          if ($old_fid != $new_fid) {
            self::deleteFile($old_fid);
            self::createFile($new_fid);
          }
        }
        elseif (
          !isset($old_element_child['#styles']['background']['image'][0]) &&
          isset($new_element['#styles']['background']['image'][0])
        ) {
          $new_fid = $new_element['#styles']['background']['image'][0];
          self::createFile($new_fid);
        }
        elseif (
          isset($old_element_child['#styles']['background']['image'][0]) &&
          !isset($new_element['#styles']['background']['image'][0])
        ) {
          $old_fid = $old_element_child['#styles']['background']['image'][0];
          self::deleteFile($old_fid);
        }
      }
      else {
        if (isset($new_element['#styles']['background']['image'][0])) {
          self::createFile($new_element['#styles']['background']['image'][0]);
        }
        $old_element_child = [];
      }

      if (isset($new_element['#children'])) {
        if (count($new_element['#children']) > 0) {
          self::saveFileImgBgElementRecursively($new_element['#children'], $old_element_child);
        }
        else {
          if ($path_old_element) {
            $old_element_child = NodeLayoutBuilderHelper::getElementFromArrayData($old_data, $path_old_element);
            if (isset($old_element_child['#styles']['background']['image'][0])) {
              // self::deleteFile($old_element_child['#styles']['background']['image'][0]);.
            }
            else {
              if (isset($old_element_child['#children'])) {
                if (count($old_element_child['#children'])) {
                  self::saveFileImgBgRecursively($old_element_child['#children'], 'delete');
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Save => create or delete image background element.
   *
   * @param array $data
   *   Data element.
   * @param string $type_action
   *   Type of action.
   */
  public static function saveFileImgBgRecursively(array $data, $type_action = 'create') {
    foreach ($data as $element) {
      if (isset($element['#styles']['background']['image'][0])) {
        $fid = $element['#styles']['background']['image'][0];

        // Create or delete file.
        if ($type_action == 'create') {
          self::createFile($fid);
        }
        else {
          self::deleteFile($fid);
        }
        // Do Recursively.
        if (is_array($element['#children'])) {
          if (count($element['#children']) > 0) {
            self::saveFileImgBgRecursively($element['#children'], $type_action);
          }
        }
      }
      else {
        // Do Recursively.
        if (is_array($element['#children'])) {
          if (count($element['#children']) > 0) {
            self::saveFileImgBgRecursively($element['#children'], $type_action);
          }
        }
      }
    }
  }

  /**
   * Save images recursively for element and children.
   *
   * @param array $new_data
   *   New data element.
   * @param array $old_data
   *   Old data element (saved).
   */
  public static function saveFileImgElementRecursively(array $new_data, array $old_data) {
    foreach ($new_data as $key_new_element => $new_element) {
      $path_old_element = NodeLayoutBuilderHelper::getkeypath($old_data, $key_new_element);
      krsort($path_old_element);
      if ($path_old_element) {
        $old_element_child = NodeLayoutBuilderHelper::getElementFromArrayData($old_data, $path_old_element);
        if (
          isset($old_element_child['#data']['image_data']['image'][0]) &&
          isset($new_element['#data']['image_data']['image'][0])
        ) {
          $new_fid = $new_element['#data']['image_data']['image'][0];
          $old_fid = $old_element_child['#data']['image_data']['image'][0];
          if ($old_fid != $new_fid) {
            self::deleteFile($old_fid);
            self::createFile($new_fid);
          }
        }
        elseif (
          !isset($old_element_child['#data']['image_data']['image'][0]) &&
          isset($new_element['#data']['image_data']['image'][0])
        ) {
          $new_fid = $new_element['#data']['image_data']['image'][0];
          self::createFile($new_fid);
        }
        elseif (
          isset($old_element_child['#data']['image_data']['image'][0]) &&
          !isset($new_element['#data']['image_data']['image'][0])
        ) {
          $old_fid = $old_element_child['#data']['image_data']['image'][0];
          self::deleteFile($old_fid);
        }
      }
      else {
        if (isset($new_element['#data']['image_data']['image'][0])) {
          self::createFile($new_element['#data']['image_data']['image'][0]);
        }
        $old_element_child = [];
      }

      if (isset($new_element['#children'])) {
        if (count($new_element['#children']) > 0) {
          self::saveFileImgElementRecursively($new_element['#children'], $old_element_child);
        }
        else {
          if ($path_old_element) {
            $old_element_child = NodeLayoutBuilderHelper::getElementFromArrayData($old_data, $path_old_element);
            if (isset($old_element_child['#data']['image_data']['image'][0])) {
              // self::deleteFile(
              // $old_element_child['#data']['image_data']['image'][0]
              // );.
            }
            else {
              if (isset($old_element_child['#children'])) {
                if (count($old_element_child['#children'])) {
                  // todo: not delete file if already existe in data (sortable).
                  // todo: cuz my be the element was sortabled.
                  // self::saveFileImgRecursively(
                  // $old_element_child['#children'], 'delete'
                  // );.
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Save => create or delete image element.
   *
   * @param array $data
   *   Data element.
   * @param string $type_action
   *   Type of action.
   */
  public static function saveFileImgRecursively(array $data, $type_action = 'create') {
    foreach ($data as $element) {
      if (isset($element['#data']['image_data']['image'][0])) {
        $fid = $element['#data']['image_data']['image'][0];

        // Create or delete file.
        if ($type_action == 'create') {
          self::createFile($fid);
        }
        else {
          self::deleteFile($fid);
        }
        // Do Recursively.
        if (is_array($element['#children'])) {
          if (count($element['#children']) > 0) {
            self::saveFileImgRecursively($element['#children'], $type_action);
          }
        }
      }
      else {
        // Do Recursively.
        if (is_array($element['#children'])) {
          if (count($element['#children']) > 0) {
            self::saveFileImgRecursively($element['#children'], $type_action);
          }
        }
      }
    }
  }

}
