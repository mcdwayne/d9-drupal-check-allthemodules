<?php

namespace Drupal\migrate_body\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateExecutableInterface;
use Sunra\PhpSimple\HtmlDomParser;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Plugin\migrate\process\Get;

/**
 * Migrates images and files inside body text.
 *
 * This plugin is designed to parse field of type text(formatted, long).  It looks
 * for the referenced file in <a href="referenced_file"> tags and image
 * tags <img src="referenced_image"> image tags to download and replace with
 * the approporiate relative path.
 *
 * The only downloadable extension are defined by const ALLOWED_EXTENSIONS.
 *
 * Available configuration keys
 * - root_url_regex: A regex pattern to limit the download to certain urls only.
 * - file_location_regex: (optional) Location of public folder.
 * - append_relative_url: (optional) If the reference file is relative, append
 *   the url if relative url.
 * - subfolder_location: (optional) creates a subfolder where to download a file.
 * - create_image_entity: (optional) true or false. If true, downloaded image will create
 *   a file managed entity.
 * - source: Requires the following format
 *    - field_body (this is the source body text field)
 *    - created (timestamp date created)
 *    - changed (timestamp changed)
 *    - status (status)
 *    - '@uid' (uid of the creator)
 *
 * @MigrateProcessPlugin(
 *   id = "body_text_content_migration"
 *   source_module = "migrate"
 * )
 */
class BodyTextContentMigration extends FileCopy {

  const ALLOWED_EXTENSIONS = 'pdf|doc|docx|xls|xlsx|txt|csv|jpg|jpeg|png';

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Preparing for multi-value body texts.
    foreach ($value[0] as &$body) {
      // Get DOM Parser for easy html parsing.
      $dom = HtmlDomParser::str_get_html($body['value']);
      $migrate_info = [
        'executable' => $migrate_executable,
        'row' => $row,
        'destination' => $destination_property,
      ];

      // Get all links.
      foreach ($dom->find('a') as &$element) {
        $save = $this->saveFile($element->href, $migrate_info, $value);
        if ($save) {
          $this->updateLink($element, $save);
        }
      }
      unset($element);

      // Get all images.
      foreach ($dom->find('img') as &$image) {
        $save = $this->saveFile($image->src, $migrate_info, $value);
        if ($save) {
          // Uncomment next line if entity save is required.
          if (isset($this->configuration['create_image_entity'])) {
            $this->saveEntity($value, $save);
          }
          $this->updateImage($image, $save);
        }
      }
      unset($image);

      $body['value'] = $dom->save();

    }
    return $value[0];
  }

  /**
   * Store related item.
   */
  private function saveFile($link, $migrate_info) {
    $new_url = FALSE;

    if (empty($this->configuration['root_url_regex'])) {
      throw new \Exception('root_url_regex parameter is required.');
    }

    // Append root_url_regex if in case relative url.
    if (strpos($link, "/") === 0) {
      if (isset($this->configuration['append_relative_url'])) {
        $link = $this->configuration['append_relative_url'] . $link;
      }
      else {
        throw new \Exception('Must assign append_relative_url parameter, there are relative URLs.');
      }
    }

    if (isset($this->configuration['file_location_regex'])) {
      $file_location_regex = $this->configuration['file_location_regex'];
    }
    else {
      $file_location_regex = "sites\/default\/files";
    }

    $pattern = '/' . $this->configuration['root_url_regex'] . '\/' . $file_location_regex . '\/(.*\.(' . self::ALLOWED_EXTENSIONS . '))/i';

    if (preg_match($pattern, $link, $matches)) {
      $subfolder = '';
      if (isset($this->configuration['subfolder_location'])) {
        $subfolder = $this->configuration['subfolder_location'] . '/';
      }

      // Get the last match because root_url_regex file_location_regex might contain matches.
      $last_match_index = count($matches) - 2;

      $uri = "public://" . $subfolder . urldecode($matches[$last_match_index]);
      $url = urldecode($link);
      $filename = explode("/", $url);
      $filename = end($filename);
      $extension = explode(".", $filename);
      $extension = end($extension);

      // Save files.
      $_value = [$link, $uri];

      // Saves a copy of the file.
      try {
        $this->downloadPlugin->transform(
          $_value,
          $migrate_info['executable'],
          $migrate_info['row'],
          $migrate_info['destination']
        );
      }
      catch (\Exception $e) {
        $row = $migrate_info['row'];
        $nid = $row->getSourceProperty('nid');
        $message = t('Cannot download from Node (%nid) the file %file', [
          '%nid' => $nid,
          '%file' => $link
        ]);
        \Drupal::logger('neiu_migration_common')->error($message);
        // Retain URL.
        return FALSE;
      }

      $public = file_create_url('public://');
      $public = parse_url($public);
      $public = $public['path'];

      $display_url = $public . $subfolder . urldecode($matches[$last_match_index]);

      $new_url = [
        'uri' => $uri,
        'display_url' => $display_url,
      ];
    }

    return $new_url;
  }

  /**
   * Creates a file_managed db entry.
   */
  private function saveEntity($value, &$new_url) {
    // Guess the mime type based on extension.
    $mime_type = \Drupal::service('file.mime_type.guesser.extension')
      ->guess("fake." . $extension);

    $created = isset($value[1]) ? $value[1] : $destination['created'];
    $changed = isset($value[2]) ? $value[2] : $destination['changed'];
    $status = isset($value[3]) ? $value[3] : $destination['status'];
    $uid = isset($value[4]) ? $value[4] : $destination['uid'];

    $data = [
      'type' => 'file',
      'filemime' => $mime_type,
      'uri' => $new_url['uri'],
      'status' => $status,
      'created' => $created,
      'changed' => $changed,
      'uid' => $uid,
    ];

    // Create the entity.
    $file = \Drupal::entityManager()
      ->getStorage('file')
      ->create($data);
    $file->save();

    $new_url['uuid'] = $file->uuid();
  }

  /**
   * Update a tag link.
   */
  private function updateLink(&$element, $data) {
    $element->setAttribute('href', $data['display_url']);
  }

  /**
   * Update image tag link.
   */
  private function updateImage(&$element, $data) {
    $element->setAttribute('src', $data['display_url']);
    if (isset($data['uuid'])) {
      $element->setAttribute('data-entity-type', 'file');
      $element->setAttribute('data-entity-uuid', $data['uuid']);
    }
    else {
      $element->setAttribute('data-entity-type', '');
      $element->setAttribute('data-entity-uuid', '');
    }
  }

}
