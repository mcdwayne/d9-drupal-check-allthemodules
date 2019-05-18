<?php

namespace Drupal\healthz\Plugin\HealthzCheck;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\healthz\Plugin\HealthzCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a check that the file system can be written to.
 *
 * @HealthzCheck(
 *   id = "file_system",
 *   title = @Translation("File system"),
 *   description = @Translation("Checks that the file system can be written to.")
 * )
 */
class FileSystem extends HealthzCheckBase implements ContainerFactoryPluginInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a FilterMediaMetadata object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function check() {
    $schemes = [
      'temp' => 'temporary://',
      'public' => 'public://',
    ];
    // We don't always have the private service.
    if (Settings::get('file_private_path')) {
      $schemes['private'] = 'private://';
    }
    $errors = [];
    foreach ($schemes as $name => $scheme) {
      $real_dir = $this->fileSystem->realpath($scheme);
      // If we don't get a result then the directory doesn't exist.
      // This could mean the the directory has been unmounted.
      if (empty($real_dir)) {
        $errors[] = $this->t('Could not find the directory: @dir', ['@dir' => $real_dir]);
        continue;
      }
      $file = sprintf('%s/healthz_%s.txt', $real_dir, Crypt::randomBytesBase64(6));
      // Attempt to write the file to disk.
      $fp = fopen($file, 'w');
      $success = fwrite($fp, 'healthz ' . $name);
      fclose($fp);
      if (!$success) {
        $errors[] = $this->t('Could not write to file: @file', ['@file' => $file]);
      }
      // Cleanup the file on disk if present.
      if (!unlink($file)) {
        $errors[] = $this->t('Could not delete file: @file', ['@file' => $file]);
      }
    }

    if (!empty($errors)) {
      foreach ($errors as $error) {
        $this->addError($error);
      }
      return FALSE;
    }

    return TRUE;
  }

}
