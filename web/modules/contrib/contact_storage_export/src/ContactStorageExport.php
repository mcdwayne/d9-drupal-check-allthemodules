<?php

namespace Drupal\contact_storage_export;

/**
 * Class ContactStorageExport.
 *
 * @package Drupal\contact_storage_export
 */
class ContactStorageExport {

  /**
   * Get the last id that was exported.
   *
   * @param string $contact_form
   *   The contact form machine name.
   *
   * @return int
   *   The last id exported (or zero if none yet).
   */
  public static function getLastExportId($contact_form) {
    $key = 'contact_storage_export.' . $contact_form;
    return \Drupal::keyValue($key)->get('last_id', 0);
  }

  /**
   * Set the last id that was exported.
   *
   * @param string $contact_form
   *   The contact form machine name.
   * @param int $last_id
   *   The last id exported.
   */
  public static function setLastExportId($contact_form, $last_id) {
    $key = 'contact_storage_export.' . $contact_form;
    \Drupal::keyValue($key)->set('last_id', $last_id);
  }

}
