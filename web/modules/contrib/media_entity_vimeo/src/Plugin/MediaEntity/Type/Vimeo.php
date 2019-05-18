<?php

/**
 * @file
 */
namespace Drupal\media_entity_vimeo\Plugin\MediaEntity\Type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use Drupal\Core\Url;
use Drupal\media_entity_vimeo\VimeoEmbedFetcher;

/**
 * Provides media type plugin for Vimeo.
 *
 * @MediaType(
 *   id = "vimeo",
 *   label = @Translation("Vimeo"),
 *   description = @Translation("Provides business logic and metadata for vimeo.")
 * )
 */
class Vimeo extends MediaTypeBase {

  /**
   * @var array
   */
  protected $vimeo;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The vimeo fetcher.
   *
   * @var \Drupal\media_entity_vimeo\Plugin\MediaEntity\Type\VimeoEmbedFetcher
   */
  protected $fetcher;

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $configFactory, Client $httpClient, VimeoEmbedFetcher $fetcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $configFactory->get('media_entity.settings'));
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
    $this->fetcher = $fetcher;
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
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('media_entity_vimeo.vimeo_embed_fetcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'video_id' => $this->t('Video ID'),
      'title' => $this->t('Title of Video'),
      'author_name' => t('Author Name'),
      'author_url' => t('Author URL'),
      'thumbnail_uri' => t('URI of the thumbnail'),
      'description' => t('Description of Video'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'vimeo_source_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['string', 'link'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['vimeo_source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with source information'),
      '#description' => $this->t('Field on media entity that stores URL. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['vimeo_source_field']) ? NULL : $this->configuration['vimeo_source_field'],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = [
    '@vimeo\.com/(\d+)@i',
    '@vimeo\.com/video/(\d+)@i',
    '@vimeo\.com/groups/.+/videos/(\d+)@i',
    '@vimeo\.com/channels/.+/(\d+)@i',
    '@vimeo\.com/album/.+/video/(\d+)@i',
  ];

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   Media object.
   *
   * @return array|bool
   *   Array of preg matches or FALSE if no match.
   *
   * @see preg_match()
   */
  protected function getMediaUrl(MediaInterface $media) {
    $matches = [];

    if (isset($this->configuration['vimeo_source_field'])) {
      $source_field = $this->configuration['vimeo_source_field'];

      if ($media->hasField($source_field)) {

        $property_name = $media->{$source_field}->first()->mainPropertyName();
        foreach (static::$validationRegexp as $exp) {
          if (preg_match($exp, $media->{$source_field}->{$property_name}, $matches)) {
            return $matches;
          }
        }

      }

    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getField(MediaInterface $media, $name) {

    $source_field = $this->configuration['vimeo_source_field'];
    $property_name = $media->{$source_field}->first()->mainPropertyName();
    $vimeo_source_url = $media->{$source_field}->{$property_name};
    $data = $this->fetcher->fetchVimeoEmbed($vimeo_source_url);

    if ($data) {

      switch ($name) {
        case 'thumbnail_uri':
          if (isset($data['thumbnail_url'])) {
            $destination = $this->configFactory->get('media_entity_vimeo.settings')->get('thumbnail_destination');
            $local_uri = $destination . '/' . $data['video_id'] . '.' . pathinfo(parse_url($data['thumbnail_url'], PHP_URL_PATH), PATHINFO_EXTENSION);

            if (!file_exists($local_uri)) {
              file_prepare_directory($destination, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
              $image = file_get_contents($data['thumbnail_url']);
              $result = file_unmanaged_save_data($image, $local_uri, FILE_EXISTS_REPLACE);
              return $local_uri;
            }
            return $local_uri;

          }
          return FALSE;

        case 'video_id':
          if (isset($data['video_id'])) {
            return $data['video_id'];
          }
          return FALSE;

        case 'title':
          if (isset($data['title'])) {
            return $data['title'];
          }
          return FALSE;

        case 'author_name':
          if (isset($data['author_name'])) {
            return $data['author_name'];
          }
          return FALSE;

        case 'author_url':
          if (isset($data['author_url'])) {
            return $data['author_url'];
          }
          return FALSE;

        case 'description':
          if (isset($data['description'])) {
            return $data['description'];
          }
          return FALSE;

      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {

    if ($thumbnail_image = $this->getField($media, 'thumbnail_uri')) {
      return $thumbnail_image;
    }

    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function oEmbed($vimeo_url) {

    $this->vimeo = &drupal_static(__FUNCTION__);
    if (!isset($this->vimeo)) {

      $url = Url::fromUri('http://vimeo.com/api/oembed.json', ['query' => ['url' => $vimeo_url]])->toString();
      $response = $this->httpClient->request('GET', $url, ['headers' => ['Accept' => 'application/json']]);

      if ($response->getStatusCode() == 200) {
        $this->vimeo = json_decode($response->getBody(), TRUE);
        return $this->vimeo;
      }

    }
    return FALSE;
  }

}
