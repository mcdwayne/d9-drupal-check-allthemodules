<?php

/**
 * @file
 * Hooks provided by the Cloudinary Stream Wrapper.
 */

/**
 * Allow modules to process resource structure after created.
 *
 * For a detailed example, see cloudinary_storage.module.
 *
 * @param array $resource
 *   The file or folder resource which created on Cloudinary.
 */
function hook_cloudinary_stream_wrapper_resource_create(array $resource) {
  // Update parent path to store new file or folder.
  if (isset($resource['public_id'])) {
    if ($storage_class = cloudinary_storage_class()) {
      $storage = new $storage_class($resource);
      list($path, $file) = $storage->resourceUpdate();
      $data = array(CLOUDINARY_STORAGE_NEW => $file);

      if ($resource['mode'] == CLOUDINARY_STREAM_WRAPPER_FILE) {
        $storage->folderUpdate($path, $data);
      }
      elseif ($resource['mode'] == CLOUDINARY_STREAM_WRAPPER_FOLDER) {
        $storage->folderUpdate($path, NULL, $data);
      }
    }
  }
}

/**
 * Allow modules to process resource structure after file renamed.
 *
 * For a detailed example, see cloudinary_storage.module.
 *
 * @param array $src_resource
 *   The source resource which need to rename.
 * @param array $dst_resource
 *   The destination resource which has renamed.
 */
function hook_cloudinary_stream_wrapper_resource_rename(array $src_resource, array $dst_resource) {
  if ($storage_class = cloudinary_storage_class()) {
    $src_path = $dst_path = FALSE;
    $src_file = $dst_file = '';

    $src_storage = new $storage_class($src_resource);
    list($src_path, $src_file) = $src_storage->resourceUpdate(FALSE);

    $dst_storage = new $storage_class($dst_resource);
    list($dst_path, $dst_file) = $dst_storage->resourceUpdate();

    if ($src_path !== FALSE && $src_path == $dst_path) {
      $src_storage->folderUpdate($src_path, array(CLOUDINARY_STORAGE_NEW => $dst_file, CLOUDINARY_STORAGE_REMOVE => $src_file));
    }
    else {
      if ($src_path !== FALSE) {
        $src_storage->folderUpdate($src_path, array(CLOUDINARY_STORAGE_REMOVE => $src_file));
      }

      if ($dst_path !== FALSE) {
        $dst_storage->folderUpdate($dst_path, array(CLOUDINARY_STORAGE_NEW => $dst_file));
      }
    }
  }
}

/**
 * Allow modules to preprocess resource structure before load.
 *
 * For a detailed example, see cloudinary_storage.module.
 *
 * @param array $resource
 *   The file or folder resource which need to prepare on locally first.
 *
 * @return array
 *   An array which prepared locally.
 */
function hook_cloudinary_stream_wrapper_resource_prepare(array $resource) {
  if (isset($resource['public_id'])) {
    if ($storage_class = cloudinary_storage_class()) {
      $storage = new $storage_class($resource, FALSE);
      $data = $storage->getResource();
      $resource = array_merge($resource, $data);
    }
  }

  return $resource;
}

/**
 * Allow modules to process resource structure after loaded.
 *
 * For a detailed example, see cloudinary_storage.module.
 *
 * @param array $resource
 *   The file or folder resource which has been loaded from cloudinary.
 */
function hook_cloudinary_stream_wrapper_resource_loaded(array $resource) {
  // Insert or update resource data which load from remote.
  if ($storage_class = cloudinary_storage_class()) {
    $storage = new $storage_class($resource);
    $storage->resourceUpdate();
  }
}

/**
 * Allow module to process resource after deleted.
 *
 * For a detailed example, see cloudinary_storage.module.
 *
 * @param array $resource
 *   The file or folder resource which has been deleted from cloudinary.
 */
function hook_cloudinary_stream_wrapper_resource_delete(array $resource) {
  if (isset($resource['public_id'])) {
    if ($storage_class = cloudinary_storage_class()) {
      $storage = new $storage_class($resource);
      list($path, $file) = $storage->resourceUpdate(FALSE);

      if ($resource['mode'] == CLOUDINARY_STREAM_WRAPPER_FILE) {
        $storage->folderUpdate($path, array(CLOUDINARY_STORAGE_REMOVE => $file));
      }
    }
  }
}

/**
 * Allow modules to replace url of image styles with cloudinary url.
 *
 * Convert drupal image effects to cloudinary transformation.
 * For a detailed example, see cloudinary.module.
 *
 * @return array
 *   An array of effects, keyed by the exist effect name.
 *   Exist effect name which from implements hook_image_effect_info().
 *   Callback for a method to convert this effect,
 *   File for load methods from this file.
 */
function hook_cloudinary_stream_wrapper_transformation() {
  $path = drupal_get_path('module', 'cloudinary');

  return array(
    'image_crop' => array(
      'title' => t('Crop'),
      'callback' => 'cloudinary_transformation_image_crop',
      'file' => $path . '/includes/cloudinary.transformation.drupal.inc',
    ),
  );
}

/**
 * Alter effects for convert before it is callback.
 *
 * @param array $effects
 *   All effects which from implements
 *   hook_cloudinary_stream_wrapper_transformation().
 */
function hook_cloudinary_stream_wrapper_transformation_alter(array &$effects) {
  $effects['image_crop']['title'] = t('Custom Crop');
  $effects['image_crop']['callback'] = 'cloudinary_transformation_image_crop_custom';
}

/**
 * Alter change of the options in the very last moment before upload.
 *
 * @param array $options
 *   Cloudinary stream upload options.
 */
function hook_cloudinary_stream_wrapper_options_alter(array &$options) {
  $options['folder'] = 'folder';
  $options['callback'] = 'callback';
}
