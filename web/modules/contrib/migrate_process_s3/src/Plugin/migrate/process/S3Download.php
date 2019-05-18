<?php

namespace Drupal\migrate_process_s3\Plugin\migrate\process;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Downloads a file from an S3 bucket given a path and credentials.
 *
 * This plugin works a lot like the file_copy plugin commonly used in file
 * migrations. Only instead of copying the file locally, it downloads it from
 * S3 as an unmanaged file.
 *
 * @MigrateProcessPlugin(
 *   id = "s3_download"
 * )
 *
 */
class S3Download extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Migrate Process S3 service.
   *
   * @var \Drupal\migrate_process_s3\MigrateProcessS3ServiceInterface
   */
  protected $migrate_process_s3;

  /**
   * S3Download constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $migrate_process_s3) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->migrate_process_s3 = $migrate_process_s3;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('migrate_process_s3')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $s3 = $this->buildClient();

    $path = $this->saveFile($s3, $value);

    return $path;
  }

  /**
   * Builds the S3 client from plugin config.
   *
   * @return \Aws\S3\S3Client
   *   The S3 client.
   */
  protected function buildClient() {
    // Set up some default client config.
    $client_conf = [
      'version' => empty($this->configuration['version']) ? 'latest' : $this->configuration['version'],
      'region'  => empty($this->configuration['region']) ? 'us-east-1' : $this->configuration['region'],
    ];

    // Use credentials if provided.
    if (!empty($this->configuration['access_key']) && !empty($this->configuration['secret_key'])) {
      $client_conf['credentials'] = [
        'key' => $this->configuration['access_key'],
        'secret' => $this->configuration['secret_key'],
      ];
    }
    elseif (empty($this->configuration['profile'])) {
      // Check if a AWS profile name was given to use from ~/.aws/credentials
      $client_conf['profile'] = $this->configuration['profile'];
    }

    // If credentials or the profile is not provided, the client will try to
    // load them from env or ~/.aws/credentials

    return new S3Client($client_conf);
  }

  /**
   * Downloads a file from S3 and saves it as a file entity.
   *
   * @param \Aws\S3\S3Client $s3
   *   The S3 client.
   * @param $value
   *   The S3 object path.
   *
   * @return stringe|FALSE
   *   A file path success, FALSE otherwise.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function saveFile(S3Client $s3, $value) {
    if (empty($this->configuration['bucket'])) {
      throw new MigrateException('Missing parameter: bucket');
    }

    if (empty($this->configuration['dest_dir'])) {
      throw new MigrateException('Missing parameter: dest_dir');
    }

    return $this->migrate_process_s3->downloadFile(
      $s3,
      $this->configuration['bucket'],
      $value,
      $this->configuration['dest_dir']
    );
  }

}
