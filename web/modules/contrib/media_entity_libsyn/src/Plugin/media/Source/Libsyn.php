<?php

namespace Drupal\media_entity_libsyn\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DOMDocument;

/**
 * Provides media type plugin for Libsyn.
 *
 * @MediaSource(
 *   id = "libsyn",
 *   label = @Translation("Libsyn Podcast"),
 *   description = @Translation("Provides business logic and metadata for Libsyn."),
 *   allowed_field_types = {"link", "string", "string_long"},
 *   default_thumbnail_filename = "libsyn.png"
 * )
 */
class Libsyn extends MediaSourceBase {

  /**
   * Libsyn data.
   *
   * @var array
   */
  protected $libsyn;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->configFactory = $config_factory;
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
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'episode_id' => $this->t('The episode id'),
      'html' => $this->t('HTML embed code'),
      'thumbnail_uri' => t('URI of the thumbnail'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    if (($url = $this->getMediaUrl($media)) && ($data = $this->getData($url))) {
      switch ($name) {
        case 'html':
          return $data['html'];

        case 'thumbnail_uri':
          if (isset($data['thumbnail_url'])) {
            $destination = $this->configFactory->get('media_entity_libsyn.settings')->get('thumbnail_destination');
            $parsed_url = parse_url($data['thumbnail_url']);
            $local_uri = $destination . '/' . pathinfo($parsed_url['path'], PATHINFO_BASENAME);

            // Save the file if it does not exist.
            if (!file_exists($local_uri)) {
              file_prepare_directory($destination, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

              $image = file_get_contents($data['thumbnail_url']);
              file_unmanaged_save_data($image, $local_uri, FILE_EXISTS_REPLACE);

              return $local_uri;
            }
          }
          return parent::getMetadata($media, $name);

        case 'episode_id':
          // Extract the src attribute from the html code.
          preg_match('/src="([^"]+)"/', $data['html'], $src_matches);
          if (!count($src_matches)) {
            return;
          }

          // Extract the id from the src.
          preg_match('/\/episode\/id\/(\d*)/', urldecode($src_matches[1]), $matches);
          if (!count($matches)) {
            return;
          }

          return $matches[1];
      }
    }

    return parent::getMetadata($media, $name);
  }

  /**
   * Returns the episode id from the source_field.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   The media entity.
   *
   * @return string|bool
   *   The episode if from the source_field if found. False otherwise.
   */
  protected function getMediaUrl(MediaInterface $media) {
    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];

      if ($media->hasField($source_field)) {
        if (!empty($media->{$source_field}->first())) {
          $property_name = $media->{$source_field}->first()
            ->mainPropertyName();
          return $media->{$source_field}->{$property_name};
        }
      }
    }
    return FALSE;
  }

  /**
   * Returns oembed data for a Soundcloud url.
   *
   * @param string $url
   *   The Libsyn Url.
   *
   * @return array
   *   An array of embed data.
   */
  protected function getData($url) {
    $this->libsyn = &drupal_static(__FUNCTION__);

    if (!isset($this->libsyn)) {
      $response = $this->httpClient->get($url);
      $data = (string) $response->getBody();

      $dom = new DOMDocument();
      $dom->loadHTML($data, LIBXML_NOERROR);

      // Search for the embed.
      $nodes = $dom->getElementsByTagName('iframe');
      foreach ($nodes as $node) {
        $src = $node->getAttribute('src');
        if (strpos($src, 'player.libsyn.com') !== FALSE) {
          $this->libsyn['html'] = $dom->saveHTML($node);
        }
      }

      // Thumbnail.
      $nodes = $dom->getElementsByTagName('meta');
      foreach ($nodes as $node) {
        $property = $node->getAttribute('property');
        if ($property == 'og:image') {
          $this->libsyn['thumbnail_url'] = $node->getAttribute('content');
          // $this->libsyn['thumbnail_url'] = str_replace("http://", "https://", $this->libsyn['thumbnail_url']);
        }
      }
    }

    return $this->libsyn;
  }

}
