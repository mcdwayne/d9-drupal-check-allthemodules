<?php

namespace Drupal\contact_storage_export;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\contact\Entity\Message;
use Drupal\file\Entity\File;

/**
 * Class ContactStorageExportBatches.
 *
 * @package Drupal\contact_storage_export
 */
class ContactStorageExportBatches {

  /**
   * The temp file.
   *
   * @var null|FileInterace
   */
  protected static $tempFile = NULL;

  /**
   * Process callback for the batch set the export form.
   *
   * @param array $settings
   *   The settings from the export form.
   * @param array $context
   *   The batch context.
   */
  public static function processBatch(array $settings, array &$context) {
    if (empty($context['sandbox'])) {

      // Store data in results for batch finish.
      $context['results']['data'] = [];
      $context['results']['settings'] = $settings;

      // Whether we are doing since last export.
      $last_id = 0;
      if ($settings['since_last_export']) {
        $last_id = ContactStorageExport::getLastExportId($settings['contact_form']);
      }

      // Create a temp file.
      $file = self::getTempFile();
      $context['results']['fid'] = $file->id();

      // Set initial batch progress.
      $context['sandbox']['settings'] = $settings;
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = 0;
      $context['sandbox']['max'] = self::getMax($settings, $last_id);

    }
    else {
      $settings = $context['sandbox']['settings'];
    }

    if ($context['sandbox']['max'] == 0) {

      // If we have no rows to export, immediately finish.
      $context['finished'] = 1;

    }
    else {

      // Load the tempfile.
      self::$tempFile = File::load($context['results']['fid']);

      // Get the next batch worth of data.
      self::getContactFormData($settings, $context);

      // Check if we are now finished.
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }

    }

  }

  /**
   * Get the submissions for the given contact form.
   *
   * @param array $settings
   *   The settings from the export form.
   * @param array $context
   *   The batch context.
   */
  private static function getContactFormData(array $settings, array &$context) {
    $last_id = 0;
    if ($settings['since_last_export']) {
      $last_id = ContactStorageExport::getLastExportId($settings['contact_form']);
    }

    $limit = 25;
    $query = \Drupal::entityQuery('contact_message');
    $query->condition('contact_form', $settings['contact_form']);
    $query->condition('id', $last_id, '>');
    $query->range($context['sandbox']['progress'], $limit);
    $query->sort('id', 'ASC');
    if ($mids = $query->execute()) {
      if ($messages = Message::loadMultiple($mids)) {
        self::prepareMessages($messages, $settings, $context);
      }
    }
  }

  /**
   * Get max amount of messages to export.
   *
   * @param array $settings
   *   The settings from the export form.
   * @param int $last_id
   *   The last id exported or 0 if all.
   *
   * @return int
   *   The maximum number of messages to export.
   */
  private static function getMax(array $settings, $last_id) {
    $query = \Drupal::entityQuery('contact_message');
    $query->condition('contact_form', $settings['contact_form']);
    $query->condition('id', $last_id, '>');
    $query->count();
    $result = $query->execute();
    return ($result ? $result : 0);
  }

  /**
   * Prepare the contact_message objects for export to CSV.
   *
   * @param array $messages
   *   The contact_message objects.
   * @param array $settings
   *   The settings from the export form.
   * @param array $context
   *   The batch context.
   */
  private static function prepareMessages(array $messages, array $settings, array &$context) {
    /** @var \Drupal\contact_storage_export\ContactStorageExportService $exporter */
    $exporter = \Drupal::service('contact_storage_export.exporter');

    /** @var \Drupal\contact\MessageInterface $message */
    $message = reset($messages);

    // Prepare message labels.
    $settings['labels'] = $exporter->getLabels($message);

    $csv_data = [];
    foreach ($messages as $contact_message) {
      $id = $contact_message->id();
      // Serialize the contact message.
      $serialized_message = $exporter->serialize($contact_message, $settings);

      // Add the row to our CSV data.
      $csv_data[] = $serialized_message;

      // Update the batch.
      $context['results']['current_id'] = $id;
      $context['sandbox']['progress']++;
      $context['sandbox']['current_id'] = $id;

      // Set the current message.
      $context['message'] = t('Processed up to Contact Message ID @id. Your file will download immediately when complete.', [
        '@id' => $id,
      ]);
    }

    // Add the rows to our CSV data.
    $csv_string = \Drupal::service('contact_storage_export.exporter')
      ->encodeData($csv_data);
    self::writeToTempFile($csv_string);
  }

  /**
   * Finish callback for the batch set the export form.
   *
   * @param bool $success
   *   Whether the batch was successful or not.
   * @param array $results
   *   The bath results.
   * @param array $operations
   *   The batch operations.
   */
  public static function finishBatch($success, array $results, array $operations) {
    if ($success) {

      // Store last exported ID if requested.
      if ($results['settings']['since_last_export']) {
        ContactStorageExport::setLastExportId($results['settings']['contact_form'], $results['current_id']);
      }

      // Save the data to the tempstore.
      $key = ContactStorageExportTempstore::setTempstore($results['fid'], $results['settings']['filename']);

      // Redirect to download page.
      $route = 'contact_storage_export.contact_storage_download_form';
      $args = [
        'contact_form' => $results['settings']['contact_form'],
        'key' => $key,
      ];
      $url = Url::fromRoute($route, $args);
      $url_string = $url->toString();
      $response = new RedirectResponse($url_string);
      return $response;

    }
    else {
      $message = t('There was no data to export.');
      drupal_set_message($message, 'warning');
    }

    // Redirect back to export page.
    $route = 'entity.contact_form.export_form';
    $args = [
      'contact_form' => $results['settings']['contact_form'],
    ];
    $url = Url::fromRoute($route, $args);
    $url_string = $url->toString();
    $response = new RedirectResponse($url_string);
    return $response;

  }

  /**
   * Get the temp file.
   *
   * @return \Drupal\file\Entity\FileInterface
   *   The temporary file.
   */
  protected static function getTempFile() {
    if (!self::$tempFile) {
      self::$tempFile = self::createTempFile();
    }
    return self::$tempFile;
  }

  /**
   * Create the temporary file.
   */
  protected static function createTempFile() {
    // Get file tempnam.
    $dir = 'temporary://contact_storage_export';
    $file_system = \Drupal::service('file_system');
    $temp_nam = $file_system->tempnam($dir, 'contact_storage_export');

    // Create the file.
    $file = File::create([
      'filename' => 'contact-storage-export.csv',
      'uri' => $temp_nam,
    ]);
    $file->setTemporary();
    $file->save();
    return $file;
  }

  /**
   * Write to the temp file.
   *
   * @param string $data
   *   The data to add to the file.
   */
  protected static function writeToTempFile($data) {
    $file = self::getTempFile();
    if (file_put_contents($file->getFileUri(), $data, FILE_APPEND) === FALSE) {
      $url = Url::fromRoute('entity.contact_form.export_form', []);
      $url_string = $url->toString();
      $response = new RedirectResponse($url_string);
      $response->send();
      $message = t('The export was unsuccessful for an unknown reason. Please check your error logs.');
      drupal_set_message($message, 'warning');
    }
    $file->setSize($file->getFileUri());
    $file->save();
  }

}
