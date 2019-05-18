<?php

namespace Drupal\nexx_integration\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media type plugin for Image.
 *
 * @MediaSource(
 *   id = "nexx_video",
 *   label = @Translation("Nexx video"),
 *   description = @Translation("Handles videos from nexxOmnia Video CMS."),
 *   default_thumbnail_filename = "nexxvideo.png",
 *   allowed_field_types = {
 *     "nexx_video_data"
 *   }
 * )
 */
class NexxVideo extends MediaSourceBase {
  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->logger = $logger;
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
      $container->get('logger.factory')->get('nexx_integration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'nexx_item_id' => 'Nexx item ID.',
      'hash' => 'Video hash',
      'subtitle' => 'The subtitle.',
      'teaser' => 'The teaser.',
      'uploaded' => 'Time of upload.',
      'copyright' => 'Copyright information.',
      'is_ssc' => 'Is SSC.',
      'encoded_ssc' => 'SSC is encoded.',
      'validfrom_ssc' => 'Valid from: SSC.',
      'validto_ssc' => 'Valid to: SSC.',
      'encoded_html5' => 'HTML5 is encoded.',
      'is_mobile' => 'Is Mobile.',
      'runtime' => 'Video runtime',
      'encoded_mobile' => 'Mobile is encoded.',
      'validfrom_mobile' => 'Valid from: mobile.',
      'validto_mobile' => 'Valid to: SSC.',
      'is_hyve' => 'Is HYVE.',
      'encoded_hyve' => 'HYVE is encoded.',
      'validfrom_hyve' => 'Valid from: hyve.',
      'validto_hyve' => 'Valid to: hyve.',
      'active' => 'Is active',
      'deleted' => 'Is deleted',
      'blocked' => 'Is blocked',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $video_field = $this->getVideoField($media);

    if (empty($video_field)) {
      return FALSE;
    }
    switch ($name) {
      case 'nexx_item_id':
        if (!empty($media->{$video_field}->item_id)) {
          return $media->{$video_field}->item_id;
        }
        break;

      case 'is_ssc':
        if (!empty($media->{$video_field}->isSSC)) {
          return $media->{$video_field}->isSSC;
        }
        break;

      case 'encoded_ssc':
        if (!empty($media->{$video_field}->encodedSSC)) {
          return $media->{$video_field}->encodedSSC;
        }
        break;

      case 'encoded_html5':
        if (!empty($media->{$video_field}->encodedHTML5)) {
          return $media->{$video_field}->encodedHTML5;
        }
        break;

      case 'is_mobile':
        if (!empty($media->{$video_field}->isMOBILE)) {
          return $media->{$video_field}->isMOBILE;
        }
        break;

      case 'encoded_mobile':
        if (!empty($media->{$video_field}->encodedMOBILE)) {
          return $media->{$video_field}->encodedMOBILE;
        }
        break;

      case 'is_hyve':
        if (!empty($media->{$video_field}->isHYVE)) {
          return $media->{$video_field}->isHYVE;
        }
        break;

      case 'encoded_hyve':
        if (!empty($media->{$video_field}->encodedHYVE)) {
          return $media->{$video_field}->encodedHYVE;
        }
        break;

      case 'deleted':
        if (!empty($media->{$video_field}->isDeleted)) {
          return $media->{$video_field}->isDeleted;
        }
        break;

      case 'blocked':
        if (!empty($media->{$video_field}->isBlocked)) {
          return $media->{$video_field}->isBlocked;
        }
        break;

      case 'thumbnail_uri':
        $teaser_field = $this->configuration['teaser_image_field'];
        // If a teaser file mapping is given, use this as thumbnail.
        if (!empty($teaser_field) && !empty($media->{$teaser_field})) {
          $teaser_image = $media->{$teaser_field}->first()->entity;
          /** @var \Drupal\media\MediaTypeInterface $media_type */
          $media_type = $this->entityTypeManager->getStorage('media_type')
            ->load($teaser_image->bundle());
          $source_field = $media_type->getSource()->getSourceFieldDefinition($media_type)->getName();
          if (!empty($source_field)) {
            /* @var \Drupal\file\Entity\File $uri */
            $uri = $teaser_image->{$source_field}->first()->entity->getFileUri();
            $this->logger->debug("field map: @field", ['@field' => print_r($teaser_field, TRUE)]);
            $this->logger->debug("thumbnail uri: @uri", ['@uri' => $uri]);
            if ($uri) {
              return $uri;
            }
          }
        }
        return parent::getMetadata($media, 'thumbnail_uri');

      default:
        if (!empty($media->{$video_field}->{$name})) {
          return $media->{$video_field}->{$name};
        }

    }

    return FALSE;
  }

  /**
   * Retrieve video field name.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media object for which the field should be retrieved.
   *
   * @return string
   *   The fieldname of the video field;
   *
   * @throws \Exception
   */
  public function getVideoField(MediaInterface $media) {
    $fieldDefinitions = $media->getFieldDefinitions();
    foreach ($fieldDefinitions as $field_name => $fieldDefinition) {
      if ($fieldDefinition->getType() === 'nexx_video_data') {
        $videoField = $field_name;
        break;
      }
    }

    if (empty($videoField)) {
      throw new \Exception('No video data field defined');
    }

    return $videoField;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\media\MediaTypeInterface $type */
    $type = $form_state->getFormObject()->getEntity();

    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description field mapping'),
      '#options' => $this->getTextfields($type->id()),
      '#empty_option' => $this->t('Select field'),
      '#default_value' => empty($this->configuration['description_field']) ? NULL : $this->configuration['description_field'],
      '#description' => $this->t('The field where descriptions are stored. You can create a bundle without selecting a value for this dropdown initially. This dropdown will be populated after adding text fields to the bundle.'),
    ];
    $form['channel_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Channel taxonomy field mapping'),
      '#options' => $this->getMediaEntityReferenceFields($type->id(), ['taxonomy_term']),
      '#empty_option' => $this->t('Select field'),
      '#default_value' => empty($this->configuration['channel_field']) ? NULL : $this->configuration['channel_field'],
      '#description' => $this->t('The taxonomy which is used for videos. You can create a bundle without selecting a value for this dropdown initially. This dropdown will be populated after adding taxonomy term entity references to the bundle.'),
    ];
    $form['actor_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Actor taxonomy field mapping'),
      '#options' => $this->getMediaEntityReferenceFields($type->id(), ['taxonomy_term']),
      '#empty_option' => $this->t('Select field'),
      '#default_value' => empty($this->configuration['actor_field']) ? NULL : $this->configuration['actor_field'],
      '#description' => $this->t('The taxonomy which is used for actors. You can create a bundle without selecting a value for this dropdown initially. This dropdown will be populated after adding taxonomy term entity references to the bundle.'),
    ];

    $form['tag_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Tag taxonomy field mapping'),
      '#options' => $this->getMediaEntityReferenceFields($type->id(), ['taxonomy_term']),
      '#empty_option' => $this->t('Select field'),
      '#default_value' => empty($this->configuration['tag_field']) ? NULL : $this->configuration['tag_field'],
      '#description' => $this->t('The taxonomy which is used for tags. You can create a bundle without selecting a value for this dropdown initially. This dropdown will be populated after adding taxonomy term entity references to the bundle.'),
    ];

    $form['teaser_image_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Teaser image field mapping'),
      '#options' => $this->getMediaEntityReferenceFields($type->id(), ['media']),
      '#empty_option' => $this->t('Select field'),
      '#default_value' => empty($this->configuration['teaser_image_field']) ? NULL : $this->configuration['teaser_image_field'],
      '#description' => $this->t('The field which is used for the teaser image. You can create a bundle without selecting a value for this dropdown initially. This dropdown will be populated after adding media fields to the bundle.'),
    ];
    return $form;
  }

  /**
   * Builds a list of references for a media entity.
   *
   * @param int $bundle_id
   *   Entity type to get references for.
   * @param array $target_types
   *   Target types filter.
   *
   * @return array
   *   An array of field labels, keyed by field name.
   */
  protected function getMediaEntityReferenceFields($bundle_id, array $target_types) {
    $bundle_options = [];

    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle_id) as $field_id => $field_info) {
      // Filter entity_references which are not base fields.
      if ($field_info->getType() === 'entity_reference' && !$field_info->getFieldStorageDefinition()
        ->isBaseField() && in_array($field_info->getSettings()['target_type'], $target_types)
      ) {
        $bundle_options[$field_id] = $field_info->getLabel();
      }
    }
    natsort($bundle_options);
    return $bundle_options;
  }

  /**
   * Builds a list of text fields of a media entity.
   *
   * @param int $bundle_id
   *   Entity type to get references for.
   *
   * @return array
   *   An array of field labels, keyed by field name.
   */
  protected function getTextfields($bundle_id) {
    $bundle_options = [];

    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle_id) as $field_id => $field_info) {
      // Filter long text fields which are not base fields.
      $types = ['text_long', 'text_with_summary', 'string_long'];

      if (in_array($field_info->getType(), $types) && !$field_info->getFieldStorageDefinition()
        ->isBaseField()
      ) {
        $bundle_options[$field_id] = $field_info->getLabel();
      }

    }
    natsort($bundle_options);
    return $bundle_options;
  }

}
