<?php

namespace Drupal\audio_embed_media\Plugin\MediaEntity\Type;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\audio_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Config;

/**
 * Provides media type plugin for audio embed field.
 *
 * @MediaType(
 *   id = "audio_embed_field",
 *   label = @Translation("Audio embed field"),
 *   description = @Translation("Enables audio_embed_field integration with media_entity.")
 * )
 */
class AudioEmbedField extends MediaTypeBase {

  /**
   * The name of the field on the media entity.
   */
  const AUDIO_EMBED_FIELD_DEFAULT_NAME = 'field_media_audio_embed_field';

  /**
   * The audio provider manager.
   *
   * @var \Drupal\audio_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The media settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mediaSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, Config $config, ProviderManagerInterface $provider_manager, Config $media_settings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config);
    $this->providerManager = $provider_manager;
    $this->mediaSettings = $media_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    $provider = $this->loadProvider($media);
    if (is_object($provider)) {
      $provider->downloadThumbnail();
      return $provider->getLocalThumbnailUri();
    }
    else {
      return $this->getDefaultThumbnail();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $options = [];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $form_state->getFormObject()->getEntity()->id()) as $field_name => $field) {
      if ($field->getType() == 'audio_embed_field') {
        $options[$field_name] = $field->getLabel();
      }
    }
    if (empty($options)) {
      $form['summary']['#markup'] = $this->t('An audio embed field will be created on this media bundle when you save this form. You can return to this configuration screen to alter the audio field used for this bundle, or you can use the one provided.');
    }
    if (!empty($options)) {
      $form['source_field'] = [
        '#type' => 'select',
        '#required' => TRUE,
        '#title' => t('Source Audio Field'),
        '#description' => t('The field on the media entity that contains the audio URL.'),
        '#default_value' => empty($this->configuration['source_field']) ? AudioEmbedField::AUDIO_EMBED_FIELD_DEFAULT_NAME : $this->configuration['source_field'],
        '#options' => $options,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    if (!$url = $this->getAudioUrl($media)) {
      return FALSE;
    }
    $provider = $this->providerManager->loadProviderFromInput($url);
    $definition = $this->providerManager->loadDefinitionFromInput($url);
    switch ($name) {
      case 'id':
        return $provider->getIdFromInput($url);

      case 'source':
        return $definition['id'];

      case 'source_name':
        return $definition['id'];

      case 'image_local':
      case 'image_local_uri':
        return $this->thumbnail($media);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'id' => $this->t('Audio ID.'),
      'source' => $this->t('Audio source machine name.'),
      'source_name' => $this->t('Audio source human name.'),
      'image_local' => $this->t('Copies thumbnail image to the local filesystem and returns the URI.'),
      'image_local_uri' => $this->t('Gets URI of the locally saved image.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MediaInterface $media) {
    return $this->loadProvider($media)->getName();
  }

  /**
   * Load an audio provider given a media entity.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   The media entity.
   *
   * @return \Drupal\audio_embed_field\ProviderPluginInterface
   *   The provider plugin.
   */
  protected function loadProvider(MediaInterface $media) {
    $audio_url = $this->getAudioUrl($media);
    return !empty($audio_url) ? $this->providerManager->loadProviderFromInput($audio_url) : FALSE;
  }

  /**
   * Get the audio URL from a media entity.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   The media entity.
   *
   * @return string|bool
   *   A audio URL or FALSE on failure.
   */
  protected function getAudioUrl(MediaInterface $media) {
    $field_name = empty($this->configuration['source_field']) ? AudioEmbedField::AUDIO_EMBED_FIELD_DEFAULT_NAME : $this->configuration['source_field'];
    if (isset($media->{$field_name})) {
      $audio_url = $media->{$field_name}->value;
    }

    return isset($audio_url) ? $audio_url : FALSE;
  }

  /**
   * The function that is invoked during the insert of media bundles.
   *
   * @param string $media_bundle_id
   *   The ID of the media bundle.
   */
  public static function createAudioEmbedField($media_bundle_id) {
    if (!FieldStorageConfig::loadByName('media', static::AUDIO_EMBED_FIELD_DEFAULT_NAME)) {
      FieldStorageConfig::create([
        'field_name' => static::AUDIO_EMBED_FIELD_DEFAULT_NAME,
        'entity_type' => 'media',
        'type' => 'audio_embed_field',
      ])->save();
    }

    FieldConfig::create([
      'entity_type' => 'media',
      'field_name' => static::AUDIO_EMBED_FIELD_DEFAULT_NAME,
      'label' => 'Audio URL',
      'required' => TRUE,
      'bundle' => $media_bundle_id,
    ])->save();

    // Make the field visible on the form display.
    $form_display = entity_get_form_display('media', $media_bundle_id, 'default');
    $form_display->setComponent(static::AUDIO_EMBED_FIELD_DEFAULT_NAME, [
      'type' => 'audio_embed_field_textfield',
    ])->save();

    // Make the field visible on the media entity itself.
    $display = entity_get_display('media', $media_bundle_id, 'default');
    $display->setComponent(static::AUDIO_EMBED_FIELD_DEFAULT_NAME, [
      'type' => 'audio_embed_field_audio',
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->mediaSettings->get('icon_base') . '/audio.png';
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
      $container->get('config.factory')->get('media_entity.settings'),
      $container->get('audio_embed_field.provider_manager'),
      $container->get('config.factory')->get('media_entity.settings')
    );
  }

}
