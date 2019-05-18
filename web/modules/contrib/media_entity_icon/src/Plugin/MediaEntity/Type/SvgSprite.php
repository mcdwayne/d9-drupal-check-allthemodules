<?php

namespace Drupal\media_entity_icon\Plugin\MediaEntity\Type;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;

use Drupal\media_entity_icon\SvgManagerInterface;
use Drupal\media_entity_icon\SvgTypeManagerInterface;

/**
 * Provides media type plugin for SvgIcon.
 *
 * @MediaType(
 *   id = "svg_sprite",
 *   label = @Translation("SVG sprite"),
 *   description = @Translation("Provides business logic for SVG sprites.")
 * )
 */
class SvgSprite extends MediaTypeBase {

  /**
   * Current user.
   *
   * @var \Drupal\media_entity_icon\SvgManagerInterface
   */
  protected $currentUser;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * SVG manager service.
   *
   * @var \Drupal\media_entity_icon\SvgManagerInterface
   */
  protected $svgManager;

  /**
   * SVG type manager service.
   *
   * @var \Drupal\media_entity_icon\SvgTypeManagerInterface
   */
  protected $svgTypeManager;

  /**
   * Statically store related icon ids.
   *
   * @var array
   */
  protected $cachedRelatedIcons = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    Config $config,
    AccountInterface $current_user,
    FileSystemInterface $file_system,
    SvgManagerInterface $svg_manager,
    SvgTypeManagerInterface $svg_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config);

    $this->currentUser = $current_user;
    $this->fileSystem = $file_system;
    $this->svgManager = $svg_manager;
    $this->svgTypeManager = $svg_type_manager;
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
      $container->get('current_user'),
      $container->get('file_system'),
      $container->get('media_entity_icon.manager.svg'),
      $container->get('media_entity_icon.manager.svg.type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $value = FALSE;

    switch ($name) {
      case 'uri':
      case 'path':
      case 'realpath':
        $svg_field = $this->configuration['svg_field'];
        if ($media->hasField($svg_field)) {
          /** @var \Drupal\file\Entity\File $svg_file */
          $svg_file = $media->get($svg_field)->entity;
          if (isset($svg_file)) {
            $svg_uri = $svg_file->getFileUri();
            switch ($name) {
              case 'path':
                $value = file_create_url($svg_uri);
                break;

              case 'realpath':
                $value = $this->fileSystem->realpath($svg_uri);
                break;

              default:
                $value = $svg_uri;

            }
          }
          else {
            $value = $media->get($svg_field)->value;
          }
        }
        break;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    return $this->config->get('icon_base') . '/svg-sprite.png';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = [
      'file' => 'file',
      'link' => 'link',
    ];
    $options = [];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (isset($allowed_field_types[$field->getType()]) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['svg_field'] = [
      '#type' => 'select',
      '#title' => $this->t('SVG sprite field'),
      '#description' => $this->t('File or link field on the media entity that stores the SVG sprite. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['svg_field']) ? NULL : $this->configuration['svg_field'],
      '#options' => $options,
    ];

    $media_bundle_names = $this->svgTypeManager->getIconBundleNames();
    $form['target_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Target SVG icon bundle'),
      '#description' => $this->t('Select the media bundle that will host the icons automatically created.'),
      '#options' => $media_bundle_names,
      '#default_value' => empty($this->configuration['target_bundle']) ? NULL : $this->configuration['target_bundle'],
    ];

    $form['autocreate_icons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create icons automatically'),
      '#description' => $this->t('Automatically create icon instances based the provided SVG sprite.'),
      '#default_value' => empty($this->configuration['autocreate_icons']) ? FALSE : $this->configuration['autocreate_icons'],
    ];
    $autocreate_active_condition = [
      ':input[name="type_configuration[svg_sprite][autocreate_icons]"]' => [
        'checked' => TRUE,
      ],
    ];

    $form['autocreate_triggers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Autocreate triggers'),
      '#description' => $this->t('Icons wil be created when those events happen.'),
      '#options' => [
        'insert' => $this->t('Create icons for a new sprite'),
        'update' => $this->t('Create missing icons on sprite update'),
      ],
      '#default_value' => empty($this->configuration['autocreate_triggers']) ? ['insert' => 'insert', 'update_add' => 'update_add'] : $this->configuration['autocreate_triggers'],
      '#states' => [
        'visible' => $autocreate_active_condition,
        'required' => $autocreate_active_condition,
      ],
    ];

    return $form;
  }

  /**
   * Get icons related to the media.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   Media entity.
   *
   * @return array
   *   Media entities fetched by icon ID.
   */
  public function getExistingIcons(MediaInterface $media) {
    if (isset($this->cachedRelatedIcons[$media->id()])) {
      return $this->cachedRelatedIcons[$media->id()];
    }

    $source_realpath = $this->getField($media, 'realpath');
    if (empty($source_realpath) || empty($this->configuration['target_bundle'])) {
      return [];
    }

    $icon_source_field = $this->svgTypeManager
      ->getIconBundleSourceField($this->configuration['target_bundle']);
    $found_entities = [];
    if (!empty($icon_source_field)) {
      $entities = $this->entityTypeManager
        ->getStorage('media')
        ->loadByProperties([
          'bundle' => $this->configuration['target_bundle'],
          $icon_source_field => $media->id(),
        ]);

      /** @var \Drupal\media_entity\MediaInterface $entity */
      foreach ($entities as $entity) {
        /** @var \Drupal\media_entity\MediaTypeInterface $media_type */
        $media_type = $entity->getType();
        $found_entities[$media_type->getField($entity, 'id')][] = $entity;
      }
    }

    // Static cache.
    $this->cachedRelatedIcons[$media->id()] = $found_entities;

    return $found_entities;
  }

  /**
   * Get all existing icons related to the media.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   Media entity.
   *
   * @return array
   *   Media entities fetched by icon ID.
   */
  public function getAllExistingIcons(MediaInterface $media) {
    if (isset($this->cachedRelatedIcons[$media->id()])) {
      return $this->cachedRelatedIcons[$media->id()];
    }

    $source_realpath = $this->getField($media, 'realpath');
    if (empty($source_realpath)) {
      return [];
    }

    // Prepare the matching parameters ...
    $matching_properties = [];
    $icon_bundle_configs = $this->svgTypeManager
      ->getIconBundleConfigs();
    foreach ($icon_bundle_configs as $icon_bundle_id => $icon_bundle_config) {
      if (!empty($icon_bundle_config['source_field'])) {
        $matching_properties[$icon_bundle_id]['bundle'] = $icon_bundle_id;
        $matching_properties[$icon_bundle_id][$icon_bundle_config['source_field']] = $media->id();
      }
    }

    // ... and load the existing icons.
    $found_entities = [];
    $media_storage = $this->entityTypeManager->getStorage('media');
    foreach ($matching_properties as $matching_property) {
      $entities = $media_storage->loadByProperties($matching_property);
      /** @var \Drupal\media_entity\MediaInterface $entity */
      foreach ($entities as $entity) {
        $media_type = $entity->getType();
        $found_entities[$media_type->getField($entity, 'id')] = $entity;
      }
    }

    // Static cache.
    $this->cachedRelatedIcons[$media->id()] = $found_entities;

    return $found_entities;
  }

  /**
   * Update icons related to this sprite.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   Media entity.
   * @param array $icon_ids
   *   Icon IDs.
   * @param bool $message_output
   *   Whether the function should use drupal_set_message directly or not.
   *
   * @return array|bool
   *   Array with success and error as keys or false.
   */
  public function updateIcons(MediaInterface $media, array $icon_ids = [], $message_output = TRUE) {
    if (!$this->configuration['target_bundle']) {
      return FALSE;
    }

    if (!$this->currentUser->hasPermission('create media') && !$this->currentUser->hasPermission('autocreate icons')) {
      if ($message_output) {
        drupal_set_message($this->t('You do not have the permission to create or autocreate icons.'), 'warning');
      }
      return FALSE;
    }

    $source_realpath = $this->getField($media, 'realpath');
    if (empty($source_realpath)) {
      return FALSE;
    }

    // Gather SVG icons.
    $icon_ids_extracted = $this->svgManager->extractIconIds($source_realpath);
    $icon_ids = !empty($icon_ids) ? array_intersect($icon_ids, $icon_ids_extracted) : $icon_ids_extracted;
    if (empty($icon_ids)) {
      return FALSE;
    }

    // Get target fields from bundle.
    $bundle_config = $this->svgTypeManager->getIconBundleConfig($this->configuration['target_bundle']);
    if (empty($bundle_config['source_field']) || empty($bundle_config['id_field'])) {
      return FALSE;
    }

    // Iterate through icons to create them.
    $messages = [
      'success' => [],
      'error' => [],
    ];
    $current_user_id = \Drupal::currentUser()->id();
    foreach ($icon_ids as $icon_id) {
      $values = [
        'targetEntityType' => 'media',
        'bundle' => $this->configuration['target_bundle'],
        'status' => TRUE,
        'name' => $icon_id,
        'uid' => $current_user_id,
      ];

      $values[$bundle_config['source_field']] = $media->id();
      $values[$bundle_config['id_field']] = $icon_id;

      try {
        $icon = Media::create($values);
        $icon->save();

        $messages['success'][$icon_id] = TRUE;
      }
      catch (\Exception $e) {
        $messages['error'][$icon_id] = $e->getMessage();
      }
    }

    if ($message_output) {
      if (!isset($messages['success'])) {
        drupal_set_message($this->t('An error occured during icons generation'), 'error');
      }
      else {
        foreach ($messages['success'] as $icon_id => $message) {
          drupal_set_message($this->t('@icon_id icon successfully created.', ['@icon_id' => ucfirst($icon_id)]));
        }
        foreach ($messages['error'] as $icon_id => $message) {
          drupal_set_message($this->t('@icon_id: @error_message.', [
            '@icon_id' => ucfirst($icon_id),
            '@error_message' => $message,
          ]), 'error');
        }
      }
    }

    return $messages;
  }

}
