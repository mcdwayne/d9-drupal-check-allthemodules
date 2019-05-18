<?php

namespace Drupal\gathercontent\Plugin\migrate\process;

use Cheppers\GatherContent\GatherContentClientInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Perform custom value transformation.
 *
 * @MigrateProcessPlugin(
 *   id = "gather_content_file"
 * )
 *
 * @code
 * file:
 *   plugin: gather_content_file
 *   source: file
 *   uri_scheme: string
 *   file_dir: string
 *   language: string
 * @endcode
 */
class GatherContentFile extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\gathercontent\DrupalGatherContentClient
   */
  protected $client;

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GatherContentClientInterface $client, FileSystem $fileSystem) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('gathercontent.client'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $language = $this->configuration['language'];
    $fileDir = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($this->configuration['file_dir'], []));
    $create_dir = $this->fileSystem->realpath($this->configuration['uri_scheme']) . '/' . $fileDir;
    file_prepare_directory($create_dir, FILE_CREATE_DIRECTORY);

    if (is_array($value)) {
      return $this->client->downloadFiles($value, $this->configuration['uri_scheme'] . $fileDir, $language);
    }

    $result = $this->client->downloadFiles([$value], $this->configuration['uri_scheme'] . $fileDir, $language);

    return $result[0];
  }

}
