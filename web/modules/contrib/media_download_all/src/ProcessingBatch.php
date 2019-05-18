<?php

namespace Drupal\media_download_all;

use Drupal\media_download_all\Plugin\Archiver\Zip;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Batch Processing.
 */
class ProcessingBatch {

  /**
   * Translation function wrapper.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface:translate()
   */
  public static function t($string, array $args = [], array $options = []) {
    return \Drupal::translation()->translate($string, $args, $options);
  }

  /**
   * Plular Translation function wrapper.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface:translate()
   */
  public static function formatPlural($count, $singular, $plural, array $args = array(), array $options = array()) {
    return \Drupal::translation()->formatPlural($count, $singular, $plural, $args, $options);
  }

  /**
   * Set message function wrapper.
   *
   * @see \drupal_set_message()
   */
  public static function message($message = NULL, $type = 'status', $repeat = TRUE) {
    drupal_set_message($message, $type, $repeat);
  }

  /**
   * Batch operation callback.
   *
   * @param string $entity_type
   *   The entity type.
   * @param mixed $entity_id
   *   The entity id.
   * @param string $field_name
   *   The field name.
   * @param mixed $fid
   *   The file id.
   * @param mixed $file_name
   *   The file name.
   */
  public static function operation($entity_type, $entity_id, $field_name, $fid, $file_name, &$context) {
    $zip_files_directory = "private://media_download_all";
    $file_path = \Drupal::service('file_system')->realpath($zip_files_directory) . "/$entity_type-$entity_id-$field_name.zip";

    // Initialize batch.
    $file_zip = new Zip($file_path, TRUE);
    $file_zip->add($fid);
    $file_zip->close();

    $context['results'][] = [$entity_type, $entity_id, $field_name];
    $context['message'] = static::t('Compressing file @fid - @file_name...',
      ['@fid' => $fid, '@file_name' => $file_name]);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Was the process successfull?
   * @param array $results
   *   Batch process results array.
   * @param array $operations
   *   Performed operations array.
   */
  public static function operationFinished($success, $results, $operations) {
    if ($success) {
      static::message(static::t('Compressed @count files.', ['@count' => count($results)]));

      if (count($results)) {
        list($entity_type, $entity_id, $field_name) = reset($results);

        $cid = 'media_download_all:' . $entity_type . ':' . $entity_id;
        $cache = \Drupal::cache()->get($cid);
        if ($cache) {
          $cached_files = $cache->data;
        }

        $zip_files_directory = "private://media_download_all";

        $file_path = \Drupal::service('file_system')->realpath($zip_files_directory) .
                    "/{$entity_type}-{$entity_id}-{$field_name}.zip";

        $cached_files[$field_name] = $file_path;
        $cache_tags = ['media_download_all', "{$entity_type}:{$entity_id}"];
        \Drupal::cache()->set($cid, $cached_files, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

        $redirect_uri = '/media_download_all/' . "$entity_type/$entity_id/$field_name";

        return new RedirectResponse($redirect_uri);
      }
    }
    else {
      $message = static::t('Finished with an error.');
      static::message($message, 'error');
    }
  }

}
