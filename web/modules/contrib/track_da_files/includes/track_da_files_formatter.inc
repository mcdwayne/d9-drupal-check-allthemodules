<?php

/**
 * @file
 * This file contains the specialized field formatters.
 */

use Drupal\file\Entity\File;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\file\FileUsage\FileUsageInterface;

// Load all Field module hooks for File.
require_once drupal_get_path('module', 'file') . '/file.field.inc';

/**
 * Returns HTML for a link to a file.
 *
 * @param array $variables
 *   An associative array containing:
 *   - file: A file object to which the link will be created.
 *   - icon_directory: (optional) A path to a directory of icons to be used for
 *     files. Defaults to the value of the "file_icon_directory" variable.
 *
 * @ingroup themeable
 */

function theme_track_da_files_file_link($variables) {

  $file_field_links_show_enabled = \Drupal::config('track_da_files.settings')->get('file_field_links_show_enabled');

  $output = '';
  $file = $variables['file'];
  $options = array(
    'attributes' => $variables['attributes'],
  );

  // Set options as per anchor format described at
  // http://microformats.org/wiki/file-format-examples
  $url = track_da_files_create_url($file->getFileUri());

  // Set options as per anchor format described at
  // http://microformats.org/wiki/file-format-examples
  $options['attributes']['type'] = $file->getMimeType() . '; length=' . $file->getSize();

  // Use the description as the link text if available.
  if (empty($variables['description'])) {
    //$link_text = $file->getFilename();
  }
  else {
    $link_text = $variables['description'];
    $options['attributes']['title'] = check_plain($file->getFilename());
  }

  $file_icon = array(
    '#theme' => 'file_icon',
    '#file' => $file,
    '#icon_directory' => $variables['icon_directory'],
  );

  $options['query']['file'] = '1';

  if (isset($file->type)) {
    $options['query']['type'] = $file->type;
  }

  if (isset($file->id)) {
    $options['query']['id'] = $file->id;
  }

  $output = '<span class="file">' . drupal_render($file_icon) . ' ' . l($link_text, $url, $options) . '</span>';
  if ((isset($file->displays)) && ($file->displays > 0 && isset($track_da_files_file_field_links_show_enabled))) {
    $output .= '<span class="file-displayed-counter">' . format_plural($file->displays, 'displayed 1 time', 'displayed @count times') . '</span>';
  }
  return $output;
}



/**
 * Returns HTML for a file attachments table.
 *
 * @param array $variables
 *   An associative array containing:
 *   - items: An array of file attachments.
 *
 * @ingroup themeable
 */
function theme_track_da_files_file_formatter_table($variables) {

  $header = array(t('Attachment'), t('Size'));
  $rows = array();
  foreach ($variables['items'] as $delta => $item) {

    $rows[] = array(
      theme('track_da_files_file_link', array('file' => (object) $item)),
      format_size($item['filesize']),
    );
  }

  return empty($rows) ? '' : theme('table', array('header' => $header, 'rows' => $rows));
}
