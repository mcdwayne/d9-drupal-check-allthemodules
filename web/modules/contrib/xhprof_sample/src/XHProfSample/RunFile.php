<?php
/**
 * @file
 * Defines XHProf sample file class.
 */

namespace Drupal\xhprof_sample\XHProfSample;

class RunFile extends Run implements RunInterface {
  /**
   * File extension for the resulting sample files.
   */
  public static $suffix = 'sample_xhprof';

  /**
   * Separator for encoded filename parts.
   */
  public static $separator = ';';

  /**
   * {@inheritdoc}
   */
  public static function load($filename) {
    $output_dir = \Drupal::config('xhprof_sample.settings')->get('output_dir');
    $uri = file_stream_wrapper_uri_normalize("{$output_dir}/{$filename}");

    if (!file_exists($uri)) {
      return FALSE;
    }

    $file = new stdClass();
    $file->uri = $uri;
    $file->filename = $filename;
    $file->name = pathinfo($filename, PATHINFO_FILENAME);

    $ret = call_user_func_array(array(__NAMESPACE__ . '\RunFile', 'getFileData'), array($file));
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public static function collectAll() {
    $suffix = self::$suffix;
    $output_dir = \Drupal::config('xhprof_sample.settings')->get('output_dir');
    $files = file_scan_directory($output_dir, "/\.{$suffix}$/");
    return array_map(array(__NAMESPACE__ . '\RunFile', 'getFileData'), $files);
  }

  /**
   * {@inheritdoc}
   */
  public static function collectWhere($meta_type, $meta_value) {
    $output_dir = variable_get('xhprof_sample_output_dir', XHPROF_SAMPLE_DEFAULT_OUTPUT_DIR);

    switch ($meta_type) {
      case 'path':
        $path_id = implode('_', explode('/', $meta_value));
      case 'path_id':
        $path_id = $path_id ?: $meta_value;
        $files = file_scan_directory($output_dir, "/^{$path_id}.+\.sample_xhprof$/");
        break;

      default:
        $files = array();
        break;

    }

    return array_map(array(__NAMESPACE__ . '\RunFile', 'getFileData'), $files);
  }

  /**
   * {@inheritdoc}
   */
  public static function purge() {
    $count = 0;
    $output_files = self::collectAll();

    foreach ($output_files as $idx => $meta) {
      if (file_unmanaged_delete($meta['file']->uri)) {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Parses a filename into metadata and loads file contents.
   *
   * @param mixed $file
   *   The file object or filename to parse.
   *
   * @return array
   *   An array of metadata about this file.
   */
  private static function getFileData($file) {
    $parts = explode(self::$separator, $file->filename);
    $meta_keys = array('path_id', 'runtime', 'user', 'method', 'id');
    $meta = array_combine($meta_keys, $parts);
    $meta['path'] = str_replace('_', '/', $meta['path_id']);
    $meta['file'] = $file;

    $data = file_get_contents($file->uri);
    $meta['data'] = @unserialize($data);

    return $meta;
  }

  /**
   * Constructor.
   */
  public function __construct() {
    $this->id = uniqid();
  }

  /**
   * Generates a filename for a sample file.
   */
  public function getFilename() {
    $filename_parts = array(
      $this->metadata['path_id'],
      $this->metadata['runtime'],
      $this->metadata['username'],
      $this->metadata['method'],
      $this->id,
    );

    return implode($filename_parts, self::$separator) . '.' . self::$suffix;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $output_dir = \Drupal::config('xhprof_sample.settings')->get('output_dir');
    file_prepare_directory($output_dir, FILE_CREATE_DIRECTORY);
    file_unmanaged_save_data($this->data, "{$output_dir}/{$this->getFilename()}");
  }
}
