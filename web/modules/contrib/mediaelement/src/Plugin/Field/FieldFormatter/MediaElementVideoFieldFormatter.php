<?php

namespace Drupal\mediaelement\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\file\Plugin\Field\FieldFormatter\FileVideoFormatter;

/**
 * Plugin implementation of the 'mediaelement_file_video' formatter.
 *
 * @FieldFormatter(
 *   id = "mediaelement_file_video",
 *   label = @Translation("MediaElement.js Video"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class MediaElementVideoFieldFormatter extends FileVideoFormatter implements ContainerFactoryPluginInterface {

  // Include trait with global MediaElement formatter config items. Allow for
  // overriding of Trait methods.
  use MediaElementFieldFormatterTrait {
    defaultSettings as traitDefaultSettings;
    settingsForm as traitSettingsForm;
    settingsSummary as traitSettingsSummary;
    viewElements as traitViewElements;
  }

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity Field Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Image Style entity storage.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $imageStyleStorage;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityTypeManager $entity_type_manager,
    EntityFieldManager $entity_field_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->imageStyleStorage = $entity_type_manager->getStorage('image_style');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Returns array of image style information for settings form.
   *
   * @return array
   *   The image style data as [machine_name => Label].
   */
  protected function getImageStyleOptions() {
    $style_names = array_map(function ($style) {
      return $style->label();
    }, $this->imageStyleStorage->loadMultiple());

    return [
      'raw' => $this->t('Original Image'),
    ] + $style_names;
  }

  /**
   * Returns array of any image fields defined on the current entity type.
   *
   * @return array
   *   The image fields as [field_name => Label].
   */
  protected function getImageFieldOptions() {
    // Set the option for no poster image.
    $options = ['none' => $this->t('No Poster')];

    // Get all the image fields used on the site and filter for only ones used
    // on this entity type and bundle.
    $entity_id = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    $image_fields = $this->entityFieldManager->getFieldMapByFieldType('image');
    $entity_fields = $image_fields[$entity_id] ?? [];
    $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_id, $bundle);

    foreach ($entity_fields as $field_name => $field_info) {
      if (in_array($bundle, $field_info['bundles'])) {
        $options[$field_name] = $this->t('@field_label (@field_name)', [
          '@field_label' => $bundle_fields[$field_name]->getLabel(),
          '@field_name' => $field_name,
        ]);
      }
    }

    return $options;
  }

  /**
   * Returns the file path for the video's `poster` attribute, if set.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The current entity being rendered.
   *
   * @return string
   *   The path to the poster image.
   */
  protected function getPosterPath(EntityInterface $entity) {
    $image_field = $this->settings['poster_image_field'];
    $image_style = $this->settings['poster_image_style'];

    // @codingStandardsIgnoreLine
    if ($image_field == 'none') { return ''; }
    if ($entity->get($image_field)->isEmpty()) { return ''; }

    $image_uri = $entity->{$image_field}->entity->getFileUri();

    $image_url = $image_style == 'raw'
      ? file_create_url($image_uri)
      : $this->imageStyleStorage->load($image_style)->buildUrl($image_uri);

    return file_url_transform_relative($image_url);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return self::traitDefaultSettings() + [
      'poster_image_field' => 'none',
      'poster_image_style' => 'raw',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return $this->traitSettingsForm($form, $form_state) + [
      'poster_image_field' => [
        '#title' => $this->t('Poster Image Field'),
        '#description' => $this->t('Select an Image Field from this @entity_type type to use as the poster thumbnail.', [
          '@entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
        ]),
        '#type' => 'select',
        '#options' => $this->getImageFieldOptions(),
        '#default_value' => $this->settings['poster_image_field'],
      ],
      'poster_image_style' => [
        '#title' => $this->t('Poster Image Style'),
        '#type' => 'select',
        '#options' => $this->getImageStyleOptions(),
        '#default_value' => $this->settings['poster_image_style'],
        '#states' => [
          'invisible' => [
            ':input[name*="poster_image_field"]' => ['value' => 'none'],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = $this->traitSettingsSummary();

    $summary[] = $this->t('Poster Image Field: %field', [
      '%field' => $this->getImageFieldOptions()[$this->settings['poster_image_field']],
    ]);

    if ($this->settings['poster_image_field'] != 'none') {
      $summary[] = $this->t('Poster Image Style: %style', [
        '%style' => $this->getImageStyleOptions()[$this->settings['poster_image_style']],
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = $this->traitViewElements($items, $langcode);
    $poster_path = $this->getPosterPath($items->getEntity());

    if (!empty($poster_path)) {
      foreach ($elements as &$element) {
        $element['#attributes']->setAttribute('poster', $poster_path);
      }
    }

    return $elements;
  }

}
