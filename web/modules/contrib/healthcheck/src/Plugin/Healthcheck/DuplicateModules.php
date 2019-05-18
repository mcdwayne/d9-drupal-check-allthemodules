<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use FilesystemIterator;

/**
 * @Healthcheck(
 *  id = "duplicate_modules",
 *  label = @Translation("Duplicate Modules"),
 *  description = "Checks for duplicate modules throughout the site.",
 *  tags = {
 *   "security",
 *   "site code",
 *  }
 * )
 */
class DuplicateModules extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The File System service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The Module Handler
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $file_system, $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->fileSystem = $file_system;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck.finding'),
      $container->get('file_system'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    $duplicates = $this->findDuplicates();
    $count = count($duplicates);

    if ($count > 0) {
      $findings[] = $this->actionRequested($this->getPluginId(), [
        'module_list' => implode(', ', array_keys($duplicates)),
        'count' => $count,
        'details' => $duplicates,
      ]);
    }
    else {
      $findings[] = $this->noActionRequired($this->getPluginId());
    }

    return $findings;
  }

  /**
   * @return array
   *   An array of duplicate module titles.
   */
  protected function findDuplicates() {
    $duplicates = $this->countDuplicates();

    // Get the active module, if any.
    foreach ($duplicates as $module_name => $info) {
      try {
        // Get the extension details.
        $extension = $this->moduleHandler->getModule($module_name);

        // Add the active path.
        $duplicates[$module_name]['active'] = $extension->getPath();
      }
      catch (\InvalidArgumentException $e) {
        // Eat the error intentionally.
        // We're scraping all possible modules, not just active ones.
      }
    }

    return $duplicates;
  }

  /**
   * @return array
   *   An array of duplicate module info files by count.
   */
  protected function countDuplicates() {
    $info_files = [];

    foreach ($this->buildIterator() as $key => $value) {
      // Get the module name from the file name.
      $module_name = str_replace('.info.yml', '', basename($key));

      // Increment the count.
      $info_files[$module_name]['count']++;

      // Add the directory to the list, removing DRUPAL_ROOT.
      $info_files[$module_name]['dirs'][] = str_replace(DRUPAL_ROOT . DIRECTORY_SEPARATOR, '', dirname($key));
    }

    return array_filter($info_files, function ($value) {
      return $value['count'] > 1;
    });
  }

  /**
   * Build an iterator over all module info files throughout Drupal.
   *
   * @return \RecursiveIteratorIterator
   *   An iterator over module info files throughout the site.
   */
  protected function buildIterator() {
    // Get the public files directory.
    $exclude = $this->fileSystem->realpath(file_default_scheme() . "://");

    // We always want UNIX paths, skip . and .., and follow symlinks.
    $flags = FilesystemIterator::UNIX_PATHS |
             FilesystemIterator::SKIP_DOTS |
             FilesystemIterator::KEY_AS_FILENAME |
             FilesystemIterator::FOLLOW_SYMLINKS;

    // Scan the entire drupal directory.
    $site = new \RecursiveDirectoryIterator(DRUPAL_ROOT, $flags);

    // Filter the results, excluding other files and directories as needed.
    $filter = new \RecursiveCallbackFilterIterator($site, function ($current, $key, $iterator) use ($exclude) {

      // There are some duplicate module titles in core, so we skip those.
      $safe_core_dups = [
        'field.info.yml',  // Dup from Drupal Console.
        'aaa_update_test.info.yml',
        'drupal_system_listing_compatible_test.info.yml',
      ];

      // Get the current item's name.
      $filename = $current->getFilename();

      if ($current->isDir()) {
        // Exclude the public files directory.
        return $current->getFilename() !== $exclude;
      }
      elseif (preg_match('/^.+\.info\.yml$/i', $filename) === 1) {
        // We only want *.info.yml files, excluding safe dups.
        return !in_array($filename, $safe_core_dups);
      }
    });

    // Finally, build the iterator.
    return new \RecursiveIteratorIterator($filter);
  }

}