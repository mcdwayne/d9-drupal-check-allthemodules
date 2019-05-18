<?php

namespace Drupal\imageapi_optimize_way2enjoy\Plugin\ImageAPIOptimizeProcessor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\imageapi_optimize\ConfigurableImageAPIOptimizeProcessorBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Uses the way2enjoy.com webservice to optimize an image.
 *
 * @ImageAPIOptimizeProcessor(
 *   id = "way2enjoy",
 *   label = @Translation("Way2enjoy.com"),
 *   description = @Translation("Uses the free way2enjoy.com service to optimize images.")
 * )
 */
final class Way2Enjoy extends ConfigurableImageAPIOptimizeProcessorBase {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ImageFactory $image_factory, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $image_factory);

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
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyToImage($image_uri) {
    // Need to send the file off to the remote service and await a response.
	$site_mail = \Drupal::config('system.site')->get('mail');
	global $base_url;
    $fields[] = [
      'name' => 'files',
      'contents' => fopen($image_uri, 'r'),
    ];
    if (!empty($this->configuration['quality'])) {
      $fields[] = [
        'name' => 'qlty',
        'contents' => $this->configuration['quality'],
		'admin_eml' => $site_mail,
      ];
    }
	$fields[] = [
        'name' => 'admin_eml',
        'contents' => $site_mail,
      ];
	  $fields[] = [
        'name' => 'web_url',
        'contents' => $base_url,
      ];
	  
	  
    try {
      $response = $this->httpClient->post('https://way2enjoy.com/ads/1/1111/apidrupalimgcompressor1.php', ['multipart' => $fields]);
      $body = $response->getBody();
      $json = json_decode($body);

      // If this has worked, we should get a dest entry in the JSON returned.
      if (isset($json->dest)) {
        // Now go fetch that, and save it locally.
        $smushedFile = $this->httpClient->get($json->dest);
        if ($smushedFile->getStatusCode() == 200) {
          file_unmanaged_save_data($smushedFile->getBody(), $image_uri, FILE_EXISTS_REPLACE);
          return TRUE;
        }
      }
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to download optimize image using Way2enjoy.com due to "%error".', ['%error' => $e->getMessage()]);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'quality' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['quality'] = [
      '#type' => 'number',
      '#title' => $this->t('JPEG image quality'),
      '#description' => $this->t('Optionally specify a quality setting when optimizing JPEG images.'),
      '#default_value' => $this->configuration['quality'],
      '#min' => 1,
      '#max' => 100,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['quality'] = $form_state->getValue('quality');
  }

}
