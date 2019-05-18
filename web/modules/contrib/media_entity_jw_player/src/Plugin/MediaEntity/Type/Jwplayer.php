<?php

namespace Drupal\media_entity_jw_player\Plugin\MediaEntity\Type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;

/**
 * Provides media type plugin for Jwplayer.
 *
 * @MediaType(
 *   id = "jwplayer",
 *   label = @Translation("Jw Player2"),
 *   description = @Translation("Provides business logic and metadata for Jwplayer.")
 * )
 */
class Jwplayer extends MediaTypeBase {

  /**
   * @var array
   */
  protected $jwplayer;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $configFactory, Client $httpClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $configFactory->get('media_entity.settings'));
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
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
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'video_id' => $this->t('Video ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'tjw_source_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['string', 'string_long', 'link'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['tjw_source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with source information'),
      '#description' => $this->t('Field on media entity that stores Jwplayer embed code or URL. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['tjw_source_field']) ? NULL : $this->configuration['tjw_source_field'],
      '#options' => $options,
    ];

    return $form;
  }

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

    if (isset($this->configuration['tjw_source_field'])) {
      $source_field = $this->configuration['tjw_source_field'];
      if ($media->hasField($source_field)) {

        $property_name = $media->{$source_field}->first()->mainPropertyName();
        $videoId = $media->{$source_field}->{$property_name};
        return $videoId;
      }
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getField(MediaInterface $media, $name) {

    if (($videoId = $this->getMediaUrl($media))) {

      switch ($name) {
        case 'thumbnail_uri':
          if ($videoId) {
            $destination = $this->configFactory->get('media_entity_jw_player.settings')->get('thumbnail_destination');
            $local_uri = $destination . '/' . $videoId . '.jpg';
            $image_url = 'https://content.jwplatform.com/thumbs/' . $videoId . '-720.jpg';

            if (!file_exists($local_uri)) {
              file_prepare_directory($destination, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
              $image = file_get_contents($image_url);
              $result = file_unmanaged_save_data($image, $local_uri, FILE_EXISTS_REPLACE);
              return $local_uri;
            }
            else {
              return $local_uri;
            }
          }
          return FALSE;

        case 'video_id':
          return $videoId;
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

}
