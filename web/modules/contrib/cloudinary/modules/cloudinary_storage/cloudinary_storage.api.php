<?php

/**
 * @file
 * Hooks provided by the Cloudinary Storage.
 */

/**
 * This hook allows modules to provides new storage method.
 *
 * @return array
 *   An array of storage types, keyed by the type name.
 *   Class for new storage class extend base class CloudinaryStorage,
 *   more detial see exist cloudinary storage sub modules.
 */
function hook_cloudinary_storage_info() {
  return array(
    'cloudinary_storage_name' => array(
      'title' => t('Name'),
      'class' => 'CloudinaryStorageName',
    ),
  );
}

/**
 * Alter storages for cloudinary before it is load.
 *
 * @param array $storages
 *   All storages which from implements
 *   hook_cloudinary_storage_info().
 */
function hook_cloudinary_storage_info_alter(array &$storages) {
  $storages['cloudinary_storage_name']['title'] = t('Custom Name');
  $storages['cloudinary_storage_name']['class'] = 'CloudinaryStorageCustomName';
}
