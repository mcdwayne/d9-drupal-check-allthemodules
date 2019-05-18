<?php

namespace Drupal\media_entity_imgur\Plugin\MediaEntity\Type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\media_entity\MediaTypeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media_entity_imgur\ImgurApi;

/**
 * Provides media type plugin for Imgur.
 *
 * @MediaType(
 *   id = "imgur",
 *   label = @Translation("Imgur"),
 *   description = @Translation("Provides business logic and metadata for Imgur.")
 * )
 */
class Imgur extends MediaTypeBase {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config_factory->get('media_entity.settings'));
    $this->configFactory = $config_factory;
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'use_imgur_api' => FALSE,
    ];
  }

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = array(
    // TODO Allow embedding bu URL.
    // '@((http|https):){0,1}//(www\.){0,1}imgur\.com/gallery/(?<shortcode>[a-z0-9_-]+)@i' => 'shortcode',
    '@(?P<shortcode><blockquote class=\"imgur-embed-pub\" lang=\"en\" data-id=\"(.*)\"><a href=\"//imgur.com/(?P<imgurid>.*)\">(.*)</a></blockquote><script async src=\"//s.imgur.com/min/embed.js\" charset=\"utf-8\"></script>)@i' => 'shortcode',
  );

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    $fields = array(
      'shortcode' => $this->t('Imgur shortcode'),
    );

    if ($this->configuration['use_imgur_api']) {
      $fields += array(
        'id' => $this->t('Media ID'),
        'type' => $this->t('Media type: image or album'),
        'thumbnail' => $this->t('Link to the thumbnail'),
        'thumbnail_local' => $this->t("Copies thumbnail locally and return it's URI"),
        'thumbnail_local_uri' => $this->t('Returns local URI of the thumbnail'),
        'caption' => $this->t('Caption'),
        'tags' => $this->t('Tags'),
        'username' => $this->t('Author of the post'),
      );
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $matches = $this->matchRegexp($media);

    if (!$matches['shortcode']) {
      return FALSE;
    }

    if ($name == 'shortcode') {
      return $matches['shortcode'];
    }

    // If we have auth settings return the other fields.
    if ($this->configuration['use_imgur_api'] && $imgur = $this->fetchImgur($matches['imgurid'])) {
      switch ($name) {
        case 'id':
          if (isset($imgur->id)) {
            return $imgur->id;
          }
          return FALSE;

          case 'username':
            if (isset($imgur->id)) {
              return $imgur->account_url;
            }
            return FALSE;

        case 'type':
          if (isset($imgur->lookup_type)) {
            return $imgur->lookup_type;
          }
          return FALSE;

        case 'thumbnail':
          if (isset($imgur->thumbnail_custom)) {
            return $imgur->thumbnail_custom;
          }
          return FALSE;

          case 'thumbnail_local':
             $directory = $this->configFactory->get('media_entity_imgur.settings')->get('local_images');
            if (!file_exists($directory)) {
              file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
            }

            $local_uri = $this->getField($media, 'thumbnail_local_uri');
            if ($local_uri) {
              if (file_exists($local_uri)) {
                return $local_uri;
              }
              else {
                $image_url = $this->getField($media, 'thumbnail');
                $image_data = file_get_contents($image_url);
                if ($image_data) {
                  return file_unmanaged_save_data($image_data, $local_uri, FILE_EXISTS_REPLACE);
                }
              }
            }
            return FALSE;

        case 'thumbnail_local_uri':
          if (isset($imgur->thumbnail_custom)) {
            $file_info = pathinfo($imgur->thumbnail_custom);
            return $this->configFactory->get('media_entity_imgur.settings')->get('local_images') . '/' . $file_info['filename'] . '.' . $file_info['extension'];
          }
          return FALSE;

        case 'caption':
          if (isset($imgur->title)) {
            return $imgur->title;
          }
          return FALSE;

        case 'tags':
          if (isset($imgur->section)) {
            //return implode(" ", $imgur->tags);
            return $imgur->section;
          }
          return FALSE;
      }
    }

    return FALSE;
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

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with source information'),
      '#description' => $this->t('Field on media entity that stores Imgur embed code or URL. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];

    $form['use_imgur_api'] = [
      '#type' => 'select',
      '#title' => $this->t('Use Imgur api to fetch photos.'),
      '#description' => $this->t("In order to use Imgur's api you have to create a developer account and an application. For more information consult the readme file."),
      '#default_value' => empty($this->configuration['use_imgur_api']) ? 0 : $this->configuration['use_imgur_api'],
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
    ];

    // @todo Evaluate if this should be a site-wide configuration.
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => empty($this->configuration['client_id']) ? NULL : $this->configuration['client_id'],
      '#states' => [
        'visible' => [
          ':input[name="type_configuration[imgur][use_imgur_api]"]' => ['value' => '1'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function attachConstraints(MediaInterface $media) {
    parent::attachConstraints($media);

    if (isset($this->configuration['source_field'])) {
      $source_field_name = $this->configuration['source_field'];
      if ($media->hasField($source_field_name)) {
        foreach ($media->get($source_field_name) as &$embed_code) {
          /** @var \Drupal\Core\TypedData\DataDefinitionInterface $typed_data */
          $typed_data = $embed_code->getDataDefinition();
          $typed_data->addConstraint('ImgurEmbedCode');
        }
      }
    }
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param MediaInterface $media
   *   Media object.
   *
   * @return array|bool
   *   Array of preg matches or FALSE if no match.
   *
   * @see preg_match()
   */
  protected function matchRegexp(MediaInterface $media) {
    $matches = array();
    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];
      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        foreach (static::$validationRegexp as $pattern => $key) {
          if (preg_match($pattern, $media->{$source_field}->{$property_name}, $matches)) {
            return $matches;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Get a single imgur photo.
   *
   * @param string $shortcode
   *   The imgur shortcode.
   */
  protected function fetchImgur($imgurid) {
    $imgur = &drupal_static(__FUNCTION__);

    if (!isset($imgur)) {
      if (!isset($this->configuration['client_id'])) {
        drupal_set_message($this->t('The client ID is not available. Consult the README.md for installation instructions.'), 'error');
        return;
      }
      if (empty($this->configuration['client_id'])) {
        drupal_set_message($this->t('The client ID is missing. Please add it in your Imgur settings.'), 'error');
        return;
      }

      $imgur_object = new ImgurApi($this->configuration['client_id']);
      $result = $imgur_object->albumOrImage($imgurid);

      if ($result) {
        return $result;
      }
      else {
        throw new MediaTypeException(NULL, 'The media could not be retrieved.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/imgur.png';
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    if ($local_image = $this->getField($media, 'thumbnail_local')) {
      return $local_image;
    }

    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MediaInterface $media) {
    // Try to get some fields that need the API, if not available, just use the
    // shortcode as default name.

    $username = $this->getField($media, 'username');
    $id = $this->getField($media, 'id');
    if ($username && $id) {
      return $username . ' - ' . $id;
    }
    else {
      $code = $this->getField($media, 'shortcode');
      if (!empty($code)) {
        return $code;
      }
    }

    return parent::getDefaultName($media);
  }

}
