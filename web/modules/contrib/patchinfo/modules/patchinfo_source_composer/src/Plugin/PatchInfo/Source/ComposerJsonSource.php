<?php

namespace Drupal\patchinfo_source_composer\Plugin\patchinfo\source;

use Drupal\Core\Extension\Extension;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\patchinfo\PatchInfoSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gathers patch information from composer.json files.
 *
 * This patch source will read patches from your composer.json files and any
 * external patch files specified in your composer.json files. For Drupal Core,
 * it will check for a composer.json in your Drupal root directory or in the
 * core folder.
 *
 * It is assumed, that 'cweagans/composer-patches' is used for patch management
 * with Composer.
 *
 * Presently, the source plugin will skip any patches for modules outside the
 * 'drupal/' namespace.
 *
 * @see https://github.com/cweagans/composer-patches
 *
 * @PatchInfoSource(
 *   id = "patchinfo_composer",
 *   label = @Translation("composer.json", context = "PatchInfoSource"),
 * )
 */
class ComposerJsonSource extends PatchInfoSourceBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a PatchInfoSourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $file_system);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPatches(array $info, Extension $file, $type) {
    $return = [];

    if (!in_array($type, ['module', 'theme'])) {
      return $return;
    }

    $patches = $this->parseComposerFile($file->getPath());
    $return = array_merge_recursive($return, $patches);

    if ($file->getName() == 'system') {
      // Check for patches in /composer.json, ../composer.json and
      // /core/composer.json.
      $core_base_path = str_replace('/modules/system', '', $file->getPath());

      $patches = $this->parseComposerFile($core_base_path);
      $return = array_merge_recursive($return, $patches);

      $patches = $this->parseComposerFile('.');
      $return = array_merge_recursive($return, $patches);

      $patches = $this->parseComposerFile('..');
      $return = array_merge_recursive($return, $patches);
    }

    return $return;
  }

  /**
   * Parses composer.json within a given path for patches.
   *
   * @param string $path
   *   A path from the local filesystem from where to fetch composer.json.
   *
   * @return array
   *   An array of patch information keyed by composer project name. The patch
   *   information is an array with an info key and a source key. The info key
   *   is a string with the url of the patch file followed by a space and the
   *   patch description. The source key is the path to the composer.json file
   *   that contained the patch information or link to the external patch file.
   */
  protected function parseComposerFile($path) {
    $return = [];

    $path = $this->fileSystem->realpath($path);

    $config = $this->getDecodedJson($path, 'composer.json');

    $patches = [];
    if (!empty($config['extra']['patches'])) {
      $patches = $config['extra']['patches'];
    }
    elseif (!empty($config['extra']['patches-file'])) {
      $patchfile = $this->getDecodedJson($path, $config['extra']['patches-file']);
      if (!empty($patchfile['patches'])) {
        $patches = $patchfile['patches'];
      }
    }

    foreach ($patches as $project => $project_patches) {
      if (strpos($project, 'drupal/') !== 0) {
        // Only handle Drupal projects.
        // @todo: Do we really want this? What about dependencies or custom
        // modules or contributed modules that are not hosted on drupal.org
        // (e.g. GitHub)? How do we handle this? Is there a best practice? And
        // how to find the correct Drupal module for those projects? Should we
        // just list anything, that we can't identify under Drupal Core or under
        // the name of the source module? Questions upon questions...
        continue;
      }

      // Generate Drupal project name from composer project name.
      $project = str_replace('drupal/', '', $project);
      if ($project == 'core' || $project == 'drupal') {
        $project = 'system';
      }

      foreach ($project_patches as $description => $url) {
        $info = $url . ' ' . $description;
        $return[$project][] = [
          'info' => trim($info),
          'source' => $path . '/composer.json',
        ];
      }
    }

    return $return;
  }

  /**
   * Gets decoded JSON from a JSON file.
   *
   * @param string $path
   *   Path to JSON file.
   * @param string $file
   *   JSON file name.
   *
   * @return mixed
   *   Parsed JSON.
   */
  protected function getDecodedJson($path, $file) {
    $return = [];

    // @todo: Do we need to treat $path and $file as potentially hostile?

    if (!file_exists($path . '/' . $file)) {
      return $return;
    }

    if (file_exists($path . '/' . $file) && !is_readable($path . '/' . $file)) {
      $this->loggerFactory->get('patchinfo_source_composer')->warning($this->t('Can not read @path/@file. Check your file permissions.', [
        '@path' => $path,
        '@file' => $file,
      ]));
      return $return;
    }

    $content = file_get_contents($path . '/' . $file);
    if ($content === FALSE) {
      $this->loggerFactory->get('patchinfo_source_composer')->warning($this->t('Can not get contents from @path/@file.', [
        '@path' => $path,
        '@file' => $file,
      ]));
      return $return;
    }

    $config = json_decode($content, TRUE);
    if ($config === NULL) {
      $this->loggerFactory->get('patchinfo_source_composer')->warning($this->t('Unable to parse @path/@file. Check your JSON syntax.', [
        '@path' => $path,
        '@file' => $file,
      ]));
      return $return;
    }

    return $config;
  }

}
