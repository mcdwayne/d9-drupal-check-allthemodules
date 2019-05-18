<?php

namespace Drupal\filebrowser\Services;


use Drupal\filebrowser\Filebrowser;

class FilebrowserValidator {

  /**
   * FilebrowserValidator constructor.
   */
  public function __construct() {
  }

  /**
   * Helper function to match a pattern on the path
   * @param string $path to process
   * @param array $patterns to search (separated by cr)
   * @return TRUE if at least one pattern is found
   */
  public function matchPath($path, $patterns) {
    static $regexps = NULL;
    //var_dump($path);

    if (!isset($regexps[$patterns])) {
      $regexps[$patterns] = '/^(' . preg_replace([
          '/(\r\n?|\n)/',
          '/\\\\\*/'
        ], [
          '|',
          '.*'
        ], preg_quote($patterns, '/')) . ')$/i';
    }
    $result = preg_match($regexps[$patterns], $this->safeBaseName($path)) == 1;
    return $result;
  }

  public function whiteListed($file, $pattern) {
      return trim($pattern) == '' || $this->matchPath($file, $pattern);
  }

  public function blackListed($file, $pattern) {
    return trim($pattern) != '' && $this->matchPath($file, $pattern);
  }

  public function exploreSubdirs($path, $node) {
    if (is_dir($path)) {
      return $node->filebrowser->exploreSubdirs;
    }
    else {
      return true;
    }
  }

  public function safeBaseName($path) {
    $path = rtrim($path, '/');
    $path = explode('/', $path);
    return end($path);
  }

  public function safeDirName($path) {
    $path = rtrim($path, '/');
    $path = explode('/', $path);
    array_pop($path);
    $result = implode("/", $path);
    if ($result == '') {
      return '/';
    }
    return $result;
  }

  /**
   * @param string $folder_path
   * @return string tokenized folder path
   */
  public function getNodeRoot($folder_path) {
    // fixme: token support broken
    // token_replace is in D7 core *** if (module_exists("token")) {
    // $folder_path = \Drupal::token()->replace($path, ['type' => 'global', 'object' => NULL, 'leading' => '[',
    // $trailing = ']']);
    return $folder_path;
  }

  public function encodingToFs($encoding, $string) {
    return strcasecmp($encoding, 'UTF-8') == 0 ? $string : mb_convert_encoding($string, $encoding, "UTF-8");
  }
}