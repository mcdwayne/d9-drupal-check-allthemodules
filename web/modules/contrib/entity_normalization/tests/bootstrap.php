<?php

/**
 * @file
 * Autoloading file for PHPUnit tests.
 *
 * Code based on core/tests/bootstrap.php.
 */

define("DRUPAL_ROOT", realpath(__DIR__ . "/../vendor/drupal/core"));
$loader = require realpath(__DIR__ . "/../vendor/autoload.php");

// Add our modules and referenced d8modules to the loader.
$ourModules = [
  'Drupal\\entity_normalization\\' => __DIR__ . '/../src',
  'Drupal\\entity_normalization_normalizers\\' => __DIR__ . '/../entity_normalization_normalizers/src',
  'Drupal\\Tests\\entity_normalization\\' => __DIR__ . '/../tests/src',
  'Drupal\\Tests\\entity_normalization_normalizers\\' => __DIR__ . '/../entity_normalization_normalizers/tests/src',
];
foreach ($ourModules as $ns => $dir) {
  $loader->addPsr4($ns, $dir);
}

// Start with classes in known locations.
$loader->add('Drupal\\Tests', DRUPAL_ROOT . '/tests');
$loader->add('Drupal\\KernelTests', DRUPAL_ROOT . '/tests');
$loader->add('Drupal\\FunctionalTests', DRUPAL_ROOT . '/tests');
$loader->add('Drupal\\FunctionalJavascriptTests', DRUPAL_ROOT . '/tests');

// Scan for arbitrary extension namespaces from core and contrib.
$extension_roots = drupal_phpunit_contrib_extension_directory_roots();

$dirs = array_map('drupal_phpunit_find_extension_directories', $extension_roots);
$dirs = array_reduce($dirs, 'array_merge', []);

// Get valid namespace names.
$namespaces = drupal_phpunit_get_extension_namespaces($dirs);

// Add them all to the loader.
foreach ($namespaces as $prefix => $paths) {
  $loader->addPsr4($prefix, $paths);
}

return $loader;

/**
 * Returns directories under which contributed extensions may exist.
 *
 * @param string $root
 *   (optional) Path to the root of the Drupal installation.
 *
 * @return array
 *   An array of directories under which contributed extensions may exist.
 */
function drupal_phpunit_contrib_extension_directory_roots($root = NULL) {
  $paths = [
    DRUPAL_ROOT . '/modules',
    DRUPAL_ROOT . '/profiles',
  ];
  return array_filter($paths);
}

/**
 * Finds all valid extension directories recursively within a given directory.
 *
 * @param string $scan_directory
 *   The directory that should be recursively scanned.
 *
 * @return array
 *   An associative array of extension directories found within the scanned
 *   directory, keyed by extension name.
 */
function drupal_phpunit_find_extension_directories($scan_directory) {
  $extensions = [];
  $dirs = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($scan_directory, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS));
  foreach ($dirs as $dir) {
    if (strpos($dir->getPathname(), '.info.yml') !== FALSE) {
      // Cut off ".info.yml" from the filename for use as the extension name. We
      // use getRealPath() so that we can scan extensions represented by
      // directory aliases.
      $extensions[substr($dir->getFilename(), 0, -9)] = $dir->getPathInfo()
        ->getRealPath();
    }
  }
  return $extensions;
}

/**
 * Registers the namespace for each extension directory with the autoloader.
 *
 * @param array $dirs
 *   An associative array of extension directories, keyed by extension name.
 *
 * @return array
 *   An associative array of extension directories, keyed by their namespace.
 */
function drupal_phpunit_get_extension_namespaces(array $dirs) {
  $suite_names = ['Unit', 'Kernel', 'Functional', 'FunctionalJavascript'];
  $namespaces = [];
  foreach ($dirs as $extension => $dir) {
    if (is_dir($dir . '/src')) {
      // Register the PSR-4 directory for module-provided classes.
      $namespaces['Drupal\\' . $extension . '\\'][] = $dir . '/src';
    }
    $test_dir = $dir . '/tests/src';
    if (is_dir($test_dir)) {
      foreach ($suite_names as $suite_name) {
        $suite_dir = $test_dir . '/' . $suite_name;
        if (is_dir($suite_dir)) {
          // Register the PSR-4 directory for PHPUnit-based suites.
          $namespaces['Drupal\\Tests\\' . $extension . '\\' . $suite_name . '\\'][] = $suite_dir;
        }
      }
      // Extensions can have a \Drupal\extension\Traits namespace for
      // cross-suite trait code.
      $trait_dir = $test_dir . '/Traits';
      if (is_dir($trait_dir)) {
        $namespaces['Drupal\\Tests\\' . $extension . '\\Traits\\'][] = $trait_dir;
      }
    }
  }
  return $namespaces;
}
