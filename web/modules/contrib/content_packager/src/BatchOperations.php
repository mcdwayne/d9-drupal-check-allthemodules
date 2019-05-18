<?php

namespace Drupal\content_packager;

use Drupal\views\Views;

/**
 * Batch operations to apply to Views for content packaging.
 *
 * @package content_packager
 */
class BatchOperations {

  /**
   * Implements callback_batch_operation().
   *
   * Prepares the destination defined in the module configuration.
   *
   * @param string $packageUri
   *   The URI to package files into.
   * @param array|\ArrayAccess $context
   *   The batch context array.
   */
  public static function prepareDestination($packageUri, &$context) {
    if (!isset($context['results']['prepared'])) {
      $context['results']['prepared'] = FALSE;
    }

    // We really shouldn't be doing something destructive like a recursive
    // delete if we're encounting any problems.
    if (!file_valid_uri($packageUri) || !file_unmanaged_prepare($packageUri)) {
      $msg = t('Packager directory might not be configured correctly.');
      \Drupal::messenger()->addError($msg);
      return;
    }

    if (!file_unmanaged_delete_recursive($packageUri)) {
      $msg = t('Packager directory could not be deleted.');
      \Drupal::messenger()->addError($msg);
    }

    $errors = content_packager_prepare_directory($packageUri);

    foreach ($errors as $error) {
      \Drupal::messenger()->addError($error);
    }

    $context['results']['prepared'] = count($errors) === 0;
  }

  /**
   * Implements callback_batch_operation().
   *
   * First renders a view and then stores its rendered output onto
   * the filesystem.
   *
   * @param string $view_id
   *   A view to render.
   * @param string $display_id
   *   The specific display ID to render on the view.
   * @param string $data_uri
   *   A URI destination.
   * @param array|\ArrayAccess $context
   *   The batch context array.
   */
  public static function renderAndSaveViewOutput($view_id, $display_id, $data_uri, &$context) {

    if (!isset($context['results']['rendered'])) {
      $context['results']['rendered'] = FALSE;
      $context['results']['failed'] = [];
      $context['results']['completed_count'] = 0;
    }

    $view = Views::getView($view_id);
    $view->setDisplay($display_id);
    $render_array = $view->preview();

    $context['results']['rendered'] = TRUE;

    // Must be time to save output!
    $output = $render_array['#markup'];

    if (!isset($context['results']['prepared']) || $context['results']['prepared'] !== TRUE) {
      $context['results']['failed']['copy'] = ['id' => $data_uri, 'type' => 'data export'];
      return;
    }

    if (!file_put_contents($data_uri, $output)) {
      $context['results']['failed']['copy'] = ['id' => $data_uri, 'type' => 'data export'];
      $msg = t('Failed to output the data to file!');

      \Drupal::messenger()->addError($msg);
    }

    $context['results']['completed_count']++;
  }

  /**
   * Implements callback_batch_operation().
   *
   * @param array $entity_infos
   *   An array of entity id and entity type pairs.
   * @param string $package_uri
   *   The URI where the content is being packaged to.
   * @param array $options
   *   The image styles we want to generate URIs for and the fields we want
   *   to ignore.
   *      ['image_styles' => [], 'field_blacklist' => []].
   * @param array $context
   *   The batch context array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function copyEntityFiles(array $entity_infos, $package_uri, array $options, array &$context) {
    if (!isset($context['results']['failed']['copy'])) {
      $context['results']['failed']['copy'] = [];
    }

    $uris = [];
    foreach ($entity_infos as $info) {
      $id = $info['id'];
      $type = $info['type'];

      if (!isset($context['results']['prepared']) || $context['results']['prepared'] !== TRUE) {
        $context['results']['failed']['copy'][] = ['id' => $id, 'type' => $type];
        return;
      }

      $entity = \Drupal::entityTypeManager()->getStorage($type)->load($id);
      $uris = array_merge($uris, EntityProcessor::processEntity($entity, $options));
    }

    /** @var \Drupal\Core\File\FileSystem $filesystem */
    $filesystem = \Drupal::service('file_system');

    foreach ($uris as $source) {
      $source_uri = file_uri_target($source);

      $filename = $filesystem->basename($source_uri);
      $dirname = $filesystem->dirname($source_uri);
      $dest_dir = $package_uri . DIRECTORY_SEPARATOR . $dirname;
      $target = $dest_dir . DIRECTORY_SEPARATOR . $filename;

      if (self::copyImage($source, $dest_dir, $target) === FALSE) {
        $context['results']['failed']['copy'][] = $source;
        continue;
      }

      $context['results']['completed_count']++;
    }
  }

  /**
   * Implements callback_batch_operation().
   *
   * Batch operation to duplicate files into the package folder.
   *
   * @param string $source
   *   The path to the source image to package.
   * @param string $dest_dir
   *   The directory path to copy an image to.
   * @param string $target
   *   The destination path (including filename) to copy the image to.
   *
   * @return string|false
   *   The path to the new file, or FALSE in the event of an error.
   *
   * @see file_unmanaged_copy()
   */
  private static function copyImage($source, $dest_dir, $target) {
    $result = FALSE;

    if (!file_prepare_directory($dest_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      \Drupal::logger('content_packager')->error('Failed to create the directory to pack images: %directory', ['%directory' => $dest_dir]);
    }

    if (!file_unmanaged_prepare($source, $target, FILE_EXISTS_REPLACE)) {
      return FALSE;
    }

    $result = file_unmanaged_copy($source, $target, FILE_EXISTS_REPLACE);
    $dt = filemtime($source);
    if ($dt !== FALSE && $result !== FALSE) {
      touch($target, $dt);
    }

    return $result;
  }

  /**
   * Implements callback_batch_operation().
   *
   * Adds a file to a zip file located at a given path.  It is assumed that
   * the file being added is stored within the same directory that the zip
   * file is being produced in.
   *
   * @param string $zip_name
   *   The file name of the zip file to generate/add to.
   * @param string $zip_dir
   *   A path (no uri scheme) to the directory the zip file is destined for.
   * @param string $file_name
   *   The file name being added to the zip file.
   * @param string $file_dir
   *   The directory the file is located in (no uri scheme).  Will have $zip_dir
   *   trimmed from it, so the assumption is that the file and zip file are in
   *   the same parent directory.
   * @param array $context
   *   The batch context array.
   */
  public static function zipFile($zip_name, $zip_dir, $file_name, $file_dir, array &$context) {
    // It initially seems like we should be using Drupal's Archiver plugins
    // but they really seem like they don't do what we need, which is Zip
    // creation.
    $zip = new \ZipArchive();

    $zip_path = $zip_dir . DIRECTORY_SEPARATOR . $zip_name;
    $file_path = $file_dir . DIRECTORY_SEPARATOR . $file_name;
    $opened = $zip->open($zip_path, \ZipArchive::CREATE);
    if ($opened === FALSE) {
      $context['results']['failed']['zip'][] = $file_path;
      return;
    }

    // ZipArchive operations return true even if file doesn't exist or otherwise
    // has problems, so explicitly detect a failure at this point.
    if (!file_exists($file_path) || !is_readable($file_path)) {
      $context['results']['failed']['zip'][] = $file_path;
      return;
    }

    $internal_file_dir = $file_dir;
    if (substr($internal_file_dir, 0, strlen($zip_dir)) == $zip_dir) {
      $internal_file_dir = substr($internal_file_dir, strlen($zip_dir));
    }

    if ($zip->addFile($file_path, $internal_file_dir . DIRECTORY_SEPARATOR . $file_name) === FALSE) {
      $context['results']['failed']['zip'][] = $file_path;
      return;
    }
    if ($zip->close() === FALSE) {
      $context['results']['failed']['zip'][] = $file_path;
      return;
    }

    $context['results']['completed_count']++;
  }

  /**
   * Implements callback_batch_finished().
   */
  public static function packingFinished($success, $results, $operations) {
    if (!$success) {
      \Drupal::messenger()->addError('The content has not been properly packaged.');
      return;
    }

    $file_count = isset($results['completed_count']) ? $results['completed_count'] : 0;

    \Drupal::messenger()->addStatus(t('Content packaging successfull!'));
    \Drupal::messenger()->addStatus(t('%file_count files copied to content packager directory.  Content packaging successfull!', ['%file_count' => $file_count]));

    content_packager_clear_processed();

    if (!empty($results['failed'])) {
      $msg = t(':count files did not copy or get zipped correctly; please refer to the <a href="@system-log">error log</a> for more information.',
        [':count' => count($results['failed']), '@system-log' => './admin/reports/dblog']);
      \Drupal::messenger()->addError($msg);
      return;
    }

  }

}
