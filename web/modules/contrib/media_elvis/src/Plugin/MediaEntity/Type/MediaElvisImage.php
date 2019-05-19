<?php

namespace Drupal\media_elvis\Plugin\MediaEntity\Type;

use Drupal\Core\Config\Config;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides media type plugin for Elvis images.
 *
 * @MediaType(
 *   id = "media_elvis_image",
 *   label = @Translation("Media Elvis Image"),
 *   description = @Translation("Provides business logic and metadata for Elvis images.")
 * )
 */
class MediaElvisImage extends MediaTypeBase {

  /**
   * The image factory service..
   *
   * @var \Drupal\Core\Image\ImageFactory;
   */
  protected $imageFactory;

  /**
   * The exif data.
   *
   * @var array.
   */
  protected $exif;

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
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\Config\Config $config
   *   Media entity config object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ImageFactory $image_factory, Config $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config);
    $this->imageFactory = $image_factory;
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
      $container->get('image.factory'),
      $container->get('config.factory')->get('media_entity.settings')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo
   *   Some of the fields that are available in the documentation don't show up
   *   in the actual data we get from upstream.
   */
  public function providedFields() {
    $fields = [
      'id' => $this->t('Remote ID'),
      'title' => $this->t('Title'),
      'name' => $this->t('Name'),
      'filename' => $this->t('Filename'),
      'assetPath' => $this->t('Asset path'),
      'tags' => $this->t('Tags'),
      'description' => $this->t('Description'),
      'copyrightOwner' => $this->t('Copyright owner'),
      // @todo
      //   This fields are for originals, but we can set which we download. Same
      //   goes for other fields, like fileSize, dimensions etc.
      'width' => $this->t('Width'),
      'height' => $this->t('Height'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $value = FALSE;
    $field_map = $media->bundle->entity->field_map;

    // This method might get called after entity creation too. So if we have
    // original data (which is provided by MediaElvis EB widget) we use it,
    // otherwise we get it from media entity.
    if (isset($media->original_data) && $original_data = $media->original_data) {
      switch ($name) {
        case 'id':
          $value = (isset($original_data->id)) ? $original_data->id : FALSE;
          break;

        case 'tags':
          if (isset($original_data->metadata->tags)) {
            /** @var TermStorageInterface $taxonomy_term_storage */
            $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
            $value = [];

            $handler_settings = $media->getFieldDefinition($field_map[$name])->getSetting('handler_settings');
            $file_tags_vocabulary = count($handler_settings['target_bundles']) > 1 ? $handler_settings['auto_create_bundle'] : reset($handler_settings['target_bundles']);

            foreach ($original_data->metadata->tags as $upstream_tag) {
              $term_candidates = $term_storage->loadByProperties([
                'vid' => $file_tags_vocabulary,
                'name'  => $upstream_tag,
              ]);

              if (empty($term_candidates)) {
                $term = $term_storage->create([
                  'vid' => $file_tags_vocabulary,
                  'name' => $upstream_tag,
                ]);
                $term->save();
              }
              else {
                $term = array_shift($term_candidates);
              }

              $value[] = $term->id();
            }
          }

          break;

        default:
          $value = isset($original_data->metadata->{$name}) ? $original_data->metadata->{$name} : FALSE;
      }
    }
    else {
      $media_values = $media->toArray();

      if (isset($media_values[$field_map[$name]])) {
        $value = $media_values[$field_map[$name]];
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $form_state->getFormObject()->getEntity();
    $options = [];
    $allowed_field_types = ['file', 'image'];

    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with source information'),
      '#description' => $this->t('Field on media entity that stores Image file. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/image.png';
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    $source_field = $this->configuration['source_field'];

    /** @var \Drupal\file\FileInterface $file */
    if ($file = $media->{$source_field}->entity) {
      return $file->getFileUri();
    }

    return $this->getDefaultThumbnail();
  }


  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MediaInterface $media) {
    // The default name will be the filename of the source_field, if present,
    // or the parent's defaultName implementation if it was not possible to
    // retrieve the filename.
    $source_field = $this->configuration['source_field'];

    /** @var \Drupal\file\FileInterface $file */
    if (!empty($source_field) && ($file = $media->{$source_field}->entity)) {
      return $file->getFilename();
    }

    return parent::getDefaultName($media);
  }

}
