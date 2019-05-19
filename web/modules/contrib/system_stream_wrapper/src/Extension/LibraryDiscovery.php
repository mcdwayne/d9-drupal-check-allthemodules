<?php

namespace Drupal\system_stream_wrapper\Extension;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Discovers available libraries in the filesystem.
 */
class LibraryDiscovery extends ExtensionDiscovery {

  /**
   * The directory libraries are registered in.
   */
  const LIBRARIES_DIRECTORY = 'libraries';

  /**
   * The type of this extension.
   */
  const EXTENSION_TYPE = 'library';

  /**
   * We don't want to affect the static cache of the core ExtensionDiscovery
   * class, so we keep one separately.
   *
   * @var array
   */
  protected static $files = array();

  /**
   * {@inheritdoc}
   */
  protected function scanDirectory($dir, $include_tests) {
    $files = array();

    // In order to scan top-level directories, absolute directory paths have to
    // be used (which also improves performance, since any configured PHP
    // include_paths will not be consulted). Retain the relative originating
    // directory being scanned, so relative paths can be reconstructed below
    // (all paths are expected to be relative to $this->root).
    $dir_prefix = ($dir == '' ? '' : "$dir/") . self::LIBRARIES_DIRECTORY . '/';
    $absolute_dir = ($dir == '' ? $this->root : $this->root . "/$dir");
    $absolute_dir .= '/' . self::LIBRARIES_DIRECTORY;

    if (!is_dir($absolute_dir)) {
      return $files;
    }
    // Use Unix paths regardless of platform, skip dot directories, follow
    // symlinks (to allow extensions to be linked from elsewhere), and return
    // the RecursiveDirectoryIterator instance to have access to getSubPath(),
    // since SplFileInfo does not support relative paths.
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::SKIP_DOTS;
    $flags |= \FilesystemIterator::FOLLOW_SYMLINKS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;
    $directory_iterator = new \RecursiveDirectoryIterator($absolute_dir, $flags);

    /**
     * @var string $key
     * @var \RecursiveDirectoryIterator $fileinfo
     */
    foreach ($directory_iterator as $key => $fileinfo) {
      if ($this->fileCache && $cached_extension = $this->fileCache->get($fileinfo->getPathname())) {
        $files[$cached_extension->getType()][$key] = $cached_extension;
        continue;
      }

      if (!$fileinfo->isDir()) {
        continue;
      }

      $type = self::EXTENSION_TYPE;
      $name = $fileinfo->getBasename();
      $pathname = $dir_prefix . $fileinfo->getSubPathname();

      $extension = new Extension($this->root, $type, $pathname);

      // Track the originating directory for sorting purposes.
      $extension->subpath = self::LIBRARIES_DIRECTORY . '/' . $fileinfo->getFilename();
      $extension->origin = $dir;

      $files[$type][$key] = $extension;

      if ($this->fileCache) {
        $this->fileCache->set($fileinfo->getPathname(), $extension);
      }
    }

    return $files;
  }

}
