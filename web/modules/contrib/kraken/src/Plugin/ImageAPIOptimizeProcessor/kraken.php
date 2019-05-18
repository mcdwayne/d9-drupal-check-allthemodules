<?php

namespace Drupal\kraken\Plugin\ImageAPIOptimizeProcessor;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\imageapi_optimize\ConfigurableImageAPIOptimizeProcessorBase;
use Drupal\imageapi_optimize\ImageAPIOptimizeProcessorBase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Optimize images using the Kraken.io webservice.
 *
 * @ImageAPIOptimizeProcessor(
 *   id = "kraken",
 *   label = @Translation("Kraken.io"),
 *   description = @Translation("Optimize images using the Kraken.io webservice.")
 * )
 */
class kraken extends ConfigurableImageAPIOptimizeProcessorBase {

  /**
   * The file system service.
   *
   * @var \Drupal\core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ImageFactory $image_factory, FileSystemInterface $file_system, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $image_factory);

    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('imageapi_optimize'),
      $container->get('image.factory'),
      $container->get('file_system'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => NULL,
      'api_secret' => NULL,
      'lossy' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = array(
      '#title' => t('API Key'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->configuration['api_key'],
    );

    $form['api_secret'] = array(
      '#title' => t('API Secret'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->configuration['api_secret'],
    );

    $form['lossy'] = array(
      '#title' => t('Use lossy compression'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['lossy'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['api_key'] = $form_state->getValue('api_key');
    $this->configuration['api_secret'] = $form_state->getValue('api_secret');
    $this->configuration['lossy'] = $form_state->getValue('lossy');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $description = '';

    if (!class_exists('\Kraken')) {
      $description .= $this->t('<strong>Could not locate Kraken PHP library.</strong>');
    }
    else {
      if ($this->configuration['lossy']) {
        $description .= $this->t('Using lossy compression.');
      }
      else {
        $description .= $this->t('Using lossless compression.');
      }
    }

    $summary = array(
      '#markup' => $description,
    );
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function applyToImage($image_uri) {

    if (class_exists('\Kraken')) {
      if (!empty($this->configuration['api_key']) && !empty($this->configuration['api_secret'])) {
        $kraken = new \Kraken($this->configuration['api_key'], $this->configuration['api_secret']);
        $params = array(
          'file' => $this->fileSystem->realpath($image_uri),
          'wait' => TRUE,
          'lossy' => (bool) $this->configuration['lossy'],
        );

        // Send the request to Kraken.
        $data = $kraken->upload($params);

        if (!empty($data['success']) && !empty($data['kraked_url'])) {
          try {
            $krakedFile = $this->httpClient->get($data['kraked_url']);
            if ($krakedFile->getStatusCode() == 200) {
              file_unmanaged_save_data($krakedFile->getBody(), $image_uri, FILE_EXISTS_REPLACE);
              $this->logger->info('@file_name was successfully processed by Kraken.io.
        Original size: @original_size; Kraked size: @kraked_size; Total saved:
        @saved_bytes. All figures in bytes', array(
                  '@file_name' => $image_uri,
                  '@original_size' => $data['original_size'],
                  '@kraked_size' => $data['kraked_size'],
                  '@saved_bytes' => $data['saved_bytes'],
                )
              );
              return TRUE;
            }
          } catch (RequestException $e) {
            $this->logger->error('Failed to download optimized image using Kraken.io due to "%error".', array('%error' => $e->getMessage()));
          }
        }
        else {
          $this->logger->error('Kraken.io could not optimize the uploaded image.');
        }
      }
      else {
        $this->logger->error('Kraken API key or secret not set.');
      }
    }
    else {
      $this->logger->error('Could not locate Kraken PHP library.');
    }
    return FALSE;
  }
}
