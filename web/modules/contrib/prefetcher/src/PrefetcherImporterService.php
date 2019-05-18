<?php

namespace Drupal\prefetcher;

use Drupal\Component\Utility\NestedArray;

/**
 * Class PrefetcherImporterService.
 *
 * @package Drupal\prefetcher
 */
class PrefetcherImporterService {

  public static $messages = [];

  /**
   * Imports the sitemap provided by the simple_sitemap module.
   *
   * @param $chunk_id
   * @param $context
   */
  public static function importSimpleSitemap($chunk_id, &$context = []) {
    // Do not use dependency injection here,
    // as the prefetcher shouldn't have a hard dependency on simple_sitemap.
    $xml = new \DOMDocument();
    if (\Drupal::hasService('simple_sitemap.generator')) {
      if ($sitemap = \Drupal::service('simple_sitemap.generator')->getSitemap($chunk_id)) {
        $xml->loadXML($sitemap);
      }
    }
    else {
      $context['finished'] = TRUE;
      return;
    }

    if (!isset($context['sandbox']['current'])) {
      $i = 0;
      foreach ($xml->getElementsByTagName('loc') as $url) {
        $i++;
      }
      $context['sandbox']['overall'] = $i;
      $context['sandbox']['current'] = 0;
      $context['finished'] = 0;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('prefetcher_uri');
    $i = 0;
    $num_process = 10;
    $limit = $context['sandbox']['current'] + $num_process;
    foreach ($xml->getElementsByTagName('loc') as $url) {
      $i++;
      if ($i > $context['sandbox']['current']) {
        $url = parse_url($url->textContent);
        if (!empty($url['path']) && empty($storage->loadByProperties(['relpath' => $url['path']]))) {
          /** @var \Drupal\prefetcher\Entity\PrefetcherUriInterface $prefetcher_uri */
          $prefetcher_uri = $storage->create();
          $prefetcher_uri->setPath($url['path']);
          $storage->save($prefetcher_uri);
        }
      }
      if ($i > $limit) {
        break;
      }
    }
    $context['sandbox']['current'] += $num_process;
    $context['finished'] = $context['sandbox']['current'] / $context['sandbox']['overall'];
  }

  /**
   * Main method: execute parsing and saving of redirects.
   *
   * @param mixed $file
   *    Either a Drupal file object (ui) or a path to a file (drush).
   * @param string[] $options
   *    User-supplied default flags.
   */
  public static function import($file, $options) {
    // Parse the CSV file into a readable array.
    $data = self::read($file, $options);
    if (empty($data)) {
      drupal_set_message(t('The uploaded file contains no rows with compatible prefetcher data.'), 'warning');
    }
    else {
      // Save valid redirects.
      $operations = [];
      foreach ($data as $row) {
        $operations[] = [
          ['\Drupal\prefetcher\PrefetcherImporterService', 'save'],
          [$row, $options['override']],
        ];
      }
      $batch = [
        'title' => t('Saving prefetcher keywords'),
        'operations' => $operations,
        'finished' => ['\Drupal\prefetcher\PrefetcherImporterService', 'finish'],
        'file' => drupal_get_path('module', 'prefetcher') . '/prefetcher.module',
      ];
      batch_set($batch);
    }
  }

  /**
   * Batch API callback.
   */
  public static function finish($success, $results, $operations) {
    if ($success) {
      $message = t('URIs processed.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message, 'status');
  }

  /**
   * Convert CSV file into readable PHP array.
   *
   * @param mixed $file
   *    A Drupal file object.
   * @param str[] $options
   *    User-passed defaults.
   *
   * @return str[]
   *    Keyed array of URIs, in the format
   *    [uri, entity_id, entity_type].
   */
  protected static function read($file, $options = array()) {
    $filepath = \Drupal::service('file_system')->realpath($file->getFileUri());
    if (!$f = fopen($filepath, 'r')) {
      return ['success' => FALSE, 'message' => [t('Unable to read the file')]];
    }
    $options_default = array(
      'delimiter' => ',',
      'no_headers' => FALSE,
      'override' => FALSE,
    );
    $options = NestedArray::mergeDeep($options_default, $options);
    // Line count.
    $line_no = 0;
    $data = [];
    while ($line = fgetcsv($f, 0, $options['delimiter'])) {
      $line_no++;
      if ($line_no == 1 && !$options['no_headers']) {
        drupal_set_message(t('Skipping the header row.'));
        continue;
      }
      if (!is_array($line)) {
        self::$messages['warning'][] = t('Line @line_no is invalid; bypassed.', array('@line_no' => $line_no));
        continue;
      }
      // Check for filled name / url.
      if (empty($line[0])) {
        self::$messages['warning'][] = t('Line @line_no contains invalid data; bypassed.', array('@line_no' => $line_no));
        continue;
      }

      $row_data = [
        'uri' => trim($line[0]),
      ];

      if (isset($line[1]) && isset($line[2])) {
        // entity_type and entity_id given.
        $row_data['entity_type'] = trim($line[1]);
        $row_data['entity_id'] = trim($line[2]);
      }

      // Build a row of data.
      $data[$line_no] = $row_data;
    }
    fclose($f);
    return $data;
  }

  /**
   * Save an individual redirect entity, if no redirect already exists.
   *
   * @param string[] $redirect_array
   *    Keyed array of redirects, in the format [id, name].
   * @param bool $override
   *    A 1 indicates that existing redirects should be updated.
   */
  public static function save($prefetcher_uri_array, $override) {
    if (0 === strpos($prefetcher_uri_array['uri'], '/')) {
      // path given
      // Get existing prefetcher uri object based on uri.
      if ($prefetcher_uris = self::prefetcherPathExists($prefetcher_uri_array)) {
        if ($override == 1) {
          $prefetcher_uri = reset($prefetcher_uris);
        }
        else {
          return;
        }
      }
      else {
        /** @var \Drupal\prefetcher\Entity\PrefetcherUri $prefetcher_uri */
        $prefetcherUriEntityManager = \Drupal::entityTypeManager()->getStorage('prefetcher_uri');
        $prefetcher_uri = $prefetcherUriEntityManager->create();
        $prefetcher_uri->setPath($prefetcher_uri_array['uri']);
      }
    }
    elseif (0 === strpos($prefetcher_uri_array['uri'], 'http')) {
      // uri given
      // Get existing prefetcher uri object based on uri.
      if ($prefetcher_uris = self::prefetcherUriExists($prefetcher_uri_array)) {
        if ($override == 1) {
          $prefetcher_uri = reset($prefetcher_uris);
        }
        else {
          return;
        }
      }
      else {
        /** @var \Drupal\prefetcher\Entity\PrefetcherUri $prefetcher_uri */
        $prefetcherUriEntityManager = \Drupal::entityTypeManager()->getStorage('prefetcher_uri');
        $prefetcher_uri = $prefetcherUriEntityManager->create();
        $prefetcher_uri->setUri($prefetcher_uri_array['uri']);
      }
    } else {
      // bad data.
      return;
    }

    // Check for filled entity_type field.
    // Check for filled entity_id field.
    if (!empty($prefetcher_uri_array['entity_type']) && is_numeric($prefetcher_uri_array['entity_id'])) {
      // Set link value.
      $prefetcher_uri->set('entity_type', $prefetcher_uri_array['entity_type']);
      // Set link value.
      $prefetcher_uri->set('entity_id', $prefetcher_uri_array['entity_id']);
    }
    // Save prefetcher_uri entity.
    $prefetcher_uri->save();
  }

  /**
   * Check if a prefetcher already exists for this uri path.
   *
   * @param str[] $row
   *    Keyed array of uris, in the format
   *    [uri, entity_type, entity_id].
   *
   * @return mixed
   *    FALSE if the prefetcher_uri does not exist, array of prefecher_uri objects
   *    if it does.
   */
  protected static function prefetcherUriExists($row) {
    $uri = trim($row['uri']);
    // Search for duplicate.
    $uri_entity = \Drupal::entityTypeManager()
      ->getStorage('prefetcher_uri')
      ->loadByProperties(['uri' => $uri]);
    if (!empty($uri_entity)) {
      return $uri_entity;
    }
    return FALSE;
  }

  /**
   * Check if a prefetcher already exists for this path.
   *
   * @param str[] $row
   *    Keyed array of uris, in the format
   *    [uri, entity_type, entity_id].
   *
   * @return mixed
   *    FALSE if the prefetcher_uri does not exist, array of prefecher_uri objects
   *    if it does.
   */
  protected static function prefetcherPathExists($row) {
    $path = trim($row['uri']);
    // Search for duplicate.
    $uri = \Drupal::entityTypeManager()
      ->getStorage('prefetcher_uri')
      ->loadByProperties(['relpath' => $path]);
    if (!empty($uri)) {
      return $uri;
    }
    return FALSE;
  }

}
