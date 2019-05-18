<?php

namespace Drupal\Tests\tr_rulez\Unit\Integration;

use Drupal\Core\Extension\Discovery\RecursiveExtensionFilterIterator;

/**
 * @todo Delete this trait and all uses of it
 * after Rules 8.x-3.0-alpha5 has been released.
 */
trait ModulePathTrait {

  /**
   * Determines the path to a module's class files.
   *
   * Core modules and contributed modules are located in different places, and
   * the testbot (DrupalCI) does not use same directory structure as most live
   * Drupal sites, so we must discover the path instead of hardwiring it.
   *
   * This method discovers modules the same way as Drupal core, so it should
   * work for core and contributed modules in all environments.
   *
   * @see \Drupal\Core\Extension\ExtensionDiscovery
   */
  protected function constructModulePath($module) {
    // Use Unix paths regardless of platform, skip dot directories, follow
    // symlinks (to allow extensions to be linked from elsewhere), and return
    // the RecursiveDirectoryIterator instance to have access to getSubPath(),
    // since SplFileInfo does not support relative paths.
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::SKIP_DOTS;
    $flags |= \FilesystemIterator::FOLLOW_SYMLINKS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;
    $directory_iterator = new \RecursiveDirectoryIterator($this->root, $flags);

    // Filter the recursive scan to discover extensions only.
    // Important: Without a RecursiveFilterIterator, RecursiveDirectoryIterator
    // would recurse into the entire filesystem directory tree without any kind
    // of limitations.
    $filter = new RecursiveExtensionFilterIterator($directory_iterator);
    // Ensure we find testing modules too!
    $filter->acceptTests(TRUE);

    // The actual recursive filesystem scan is only invoked by instantiating the
    // RecursiveIteratorIterator.
    $iterator = new \RecursiveIteratorIterator($filter,
      \RecursiveIteratorIterator::LEAVES_ONLY,
      // Suppress filesystem errors in case a directory cannot be accessed.
      \RecursiveIteratorIterator::CATCH_GET_CHILD

    );

    $info_files = new \RegexIterator($iterator, "/^$module.info.yml$/");
    foreach ($info_files as $file) {
      // There should only be one match.
      return $file->getSubPath();
    }
  }

}
