<?php

namespace Drupal\bynder\Plugin\media\Source;

use Drupal\bynder\BynderApiInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides media source plugin for Bynder.
 *
 * @MediaSource(
 *   id = "bynder",
 *   label = @Translation("Bynder"),
 *   description = @Translation("Provides business logic and metadata for Bynder."),
 *   default_thumbnail_filename = "bynder_no_image.png",
 *   allowed_field_types = {"string", "string_long"}
 * )
 */
class Bynder extends MediaSourceBase {

  /**
   * Bynder api service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   *   Bynder api service.
   */
  protected $bynderApi;

  /**
   * Account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Statically cached API response for a given asset.
   *
   * @var array
   */
  protected $apiResponse;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\bynder\BynderApiInterface $bynder_api_service
   *   Bynder api service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   Account proxy.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, BynderApiInterface $bynder_api_service, AccountProxyInterface $account_proxy, UrlGeneratorInterface $url_generator, LoggerChannelFactoryInterface $logger, CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);

    $this->bynderApi = $bynder_api_service;
    $this->accountProxy = $account_proxy;
    $this->urlGenerator = $url_generator;
    $this->logger = $logger;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('bynder_api'),
      $container->get('current_user'),
      $container->get('url_generator'),
      $container->get('logger.factory'),
      $container->get('cache.data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $fields = [
      'uuid' => $this->t('ID'),
      'name' => $this->t('Name'),
      'description' => $this->t('Description'),
      'tags' => $this->t('Tags'),
      'type' => $this->t('Type'),
      'video_preview_urls' => $this->t('Video preview urls'),
      'thumbnail_urls' => $this->t('Thumbnail urls'),
      'width' => $this->t('Width'),
      'height' => $this->t('Height'),
      'created' => $this->t('Date created'),
      'modified' => $this->t('Data modified'),
      'propertyOptions' => $this->t('Meta-property option IDs'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    if (!$source_field = $this->configuration['source_field']) {
      return FALSE;
    }

    if (!$media_uuid = $media->{$source_field}->value) {
      return FALSE;
    }

    if ($name == 'uuid') {
      return $media_uuid;
    }

    if (!isset($this->apiResponse)) {
      try {
        /*
         * Entity browser widget might have stored media asset info that was
         * already fetched from the API so we can avoid another API request.
         *
         * @see \Drupal\bynder\Plugin\EntityBrowser\Widget\BynderSearch::submit()
         */
        if ($cache = $this->cache->get('bynder_item_' . $media_uuid)) {
          $this->apiResponse = $cache->data;
        }
        else {
          $this->apiResponse = $this->bynderApi->getMediaInfo($media_uuid);
        }
      }
      catch (GuzzleException $e) {
        $this->logger->get('bynder')->error('Unable to fetch info about the asset represented by media @name (@id) with message @message.', [
          '@name' => $media->label(),
          '@id' => $media->id(),
          '@message' => $e->getMessage(),
        ]);
        return FALSE;
      }
    }

    if (!empty($this->apiResponse)) {
      switch ($name) {
        case 'video_preview_urls':
          return isset($this->apiResponse['videoPreviewURLs']) ? $this->apiResponse['videoPreviewURLs'] : FALSE;

        case 'thumbnail_urls':
          return isset($this->apiResponse['thumbnails']) ? $this->apiResponse['thumbnails'] : FALSE;

        case 'thumbnail_uri':
          if (!empty($this->apiResponse['thumbnails']['webimage'])) {
            if ($file = system_retrieve_file($this->apiResponse['thumbnails']['webimage'], NULL, TRUE)) {
              return $file->getFileUri();
            }
          }
          return parent::getMetadata($media, 'thumbnail_uri');

        case 'created':
          return isset($this->apiResponse['dateCreated']) ? $this->apiResponse['dateCreated'] : FALSE;

        case 'modified':
          return isset($this->apiResponse['dateModified']) ? $this->apiResponse['dateModified'] : FALSE;

        case 'default_name':
          return isset($this->apiResponse['name']) ? $this->apiResponse['name'] : parent::getMetadata($media, 'default_name');

        default:
          return isset($this->apiResponse[$name]) ? $this->apiResponse[$name] : FALSE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Check the connection with bynder.
    try {
      $this->bynderApi->getBrands();
    }
    catch (\Exception $exception) {
      if ($this->accountProxy->hasPermission('administer bynder configuration')) {
        drupal_set_message($this->t('Connecting with Bynder failed. Check if the configuration is set properly <a href=":url">here</a>.', [
          ':url' => $this->urlGenerator->generateFromRoute('bynder.configuration_form'),
        ]), 'error');
      }
      else {
        drupal_set_message($this->t('Something went wrong with the Bynder connection. Please contact the site administrator.'), 'error');
      }
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Get the primary value stored in the source field.
   *
   * @todo This helper method was added to MediaSourceBase in 8.5.0 but we
   * replicate it here because we want to support 8.4.0 sites as well. This
   * method can be safely removed once there is no need to support 8.4 anymore,
   * and we ensure the core Media dependency is bumped to 8.5.0 at least.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   *
   * @return mixed
   *   The source value.
   *
   * @throws \RuntimeException
   *   If the source field for the media source is not defined.
   */
  public function getSourceFieldValue(MediaInterface $media) {
    $source_field = $this->configuration['source_field'];
    if (empty($source_field)) {
      throw new \RuntimeException('Source field for media source is not defined.');
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    $field_item = $media->get($source_field)->first();
    return $field_item->{$field_item->mainPropertyName()};
  }

}
