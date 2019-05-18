<?php

namespace Drupal\ib_dam\Plugin\IbDam\AssetValidation;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\ib_dam\Asset\LocalAsset;
use Drupal\ib_dam\AssetValidation\AssetValidationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates a file based on passed validators.
 *
 * @IbDamAssetValidation(
 *   id = "file",
 *   label = @Translation("File validator")
 * )
 *
 * @package Drupal\ib_dam\Plugin\ibDam\AssetValidation
 */
class File extends AssetValidationBase {

  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TypedDataManagerInterface $typed_data_manager,
    FileSystemInterface $file_system
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $typed_data_manager);
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
      $container->get('typed_data_manager'),
      $container->get('file_system')
    );
  }

  /**
   * File extensions validator.
   *
   * @param \Drupal\ib_dam\Asset\LocalAsset $asset
   *   The asset object to validate.
   * @param array|string $extensions
   *   The list of allowed file extensions.
   *
   * @see file_validate_extensions()
   *
   * @return array
   *   An array with validation messages,
   *   that will return file_validate_extensions().
   */
  public function validateFileExtensions(LocalAsset $asset, $extensions) {
    if (is_array($extensions)) {
      $extensions = implode(' ', $extensions);
    }
    return file_validate_extensions($asset->localFile(), $extensions);
  }

  /**
   * File directory validator.
   *
   * @param \Drupal\ib_dam\Asset\LocalAsset $asset
   *   The asset object to validate.
   * @param string $file_dir
   *   The file directory to check.
   *
   * @return array
   *   An array with validation messages
   */
  public function validateFileDirectory(LocalAsset $asset, $file_dir) {
    $errors = [];
    $filename = $asset->localFile()->getFilename();
    $bad_dir = $this->t('This file can not be uploaded to the directory %dir.', ['%dir' => $file_dir]);

    $destination_scheme = $this->fileSystem->uriScheme($file_dir);
    if (!$this->fileSystem->validScheme($destination_scheme)) {
      $errors[] = $bad_dir;
      return $errors;
    }

    // Prepare the destination dir.
    if (!file_exists($file_dir)) {
      $this->fileSystem->mkdir($file_dir, NULL, TRUE);
    }

    // A file URI may already have a trailing slash or look like "public://".
    if (substr($file_dir, -1) != '/') {
      $file_dir .= '/';
    }
    $destination = file_destination($file_dir . $filename, FILE_EXISTS_RENAME);

    if (!$destination) {
      $errors[] = $bad_dir;
    }
    return $errors;
  }

}
