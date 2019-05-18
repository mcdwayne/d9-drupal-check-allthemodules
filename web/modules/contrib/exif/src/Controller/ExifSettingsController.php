<?php

namespace Drupal\exif\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\exif\ExifFactory;
use Drupal\media\Entity\MediaType;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ExifSettingsController manage action of settings pages.
 *
 * @package Drupal\exif\Controller
 */
class ExifSettingsController extends ControllerBase {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a ExifSettingsController object.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_display.repository')
    );
  }

  /**
   * Button to go to help page.
   *
   * Use by routing.yml.
   */
  public function showGuide() {
    return [
      '#message' => "",
      '#taxonomy' => 'http://drupal.org/handbook/modules/taxonomy/',
      '#theme' => 'exif_helper_page',
      '#attached' => [
        'library' => [
          'exif/exif-admin',
        ],
      ],
    ];
  }

  /**
   * Create a vocabulary "photographies'metadata".
   *
   * Use by routing.yml.
   */
  public function createPhotographyVocabulary() {
    $values = [
      "name" => "photographs metadata",
      "vid" => "photographs_metadata",
      "description" => "information related to photographs",
    ];
    $voc = Vocabulary::load("photographs_metadata");
    if (!$voc) {
      Vocabulary::create($values)->save();
      $message = $this->t('The  vocabulary photography has been created');
    }
    else {
      $message = $this->t('The  vocabulary photography is already created. nothing to do');
    }
    drupal_set_message($message);
    $response = new RedirectResponse('/admin/config/media/exif/helper');
    $response->send();
    exit();
  }

  /**
   * Create an Photography node type with default exif fields.
   *
   * Default files are title, model, keywords.
   *
   * Default behavior but 'promoted to front'.
   *
   * Use by routing.yml
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createPhotographyNodeType() {
    $typeName = 'Photography';
    $entity_type = 'node';
    $machineName = strtolower($typeName);
    try {
      $storage = $this->entityTypeManager()->getStorage('node_type');
      $type_definition = $storage->load($machineName);
      if (!$type_definition) {
        $type_definition = $storage->create(
          [
            'name' => $typeName,
            'type' => $machineName,
            'description' => 'Use Photography for content where the photo is the main content. You still can have some other information related to the photo itself.',
          ]);
        $type_definition->save();
      }

      // Add default display.
      $values = [
        'targetEntityType' => $entity_type,
        'bundle' => $machineName,
        'mode' => 'default',
        'status' => TRUE,
      ];
      $this->entityTypeManager()
        ->getStorage('entity_view_display')
        ->create($values);

      // Add default form display.
      $values = [
        'targetEntityType' => $entity_type,
        'bundle' => $machineName,
        'mode' => 'default',
        'status' => TRUE,
      ];
      $this->entityTypeManager()
        ->getStorage('entity_form_display')
        ->create($values);

      // Then add fields.
      $this->addFields($entity_type, $type_definition);
      $message = $this->t('The %entitytype type %type has been fully created', [
        '%entitytype' => $entity_type,
        '%type' => $typeName,
      ]);

    } catch (FieldException $fe) {
      $message = $this->t('An unexpected error was thrown during creation : ') . $fe->getMessage();
    }
    drupal_set_message($message);
    $response = new RedirectResponse('/admin/config/media/exif/helper');
    $response->send();
    exit();
  }

  /**
   * Create a Photography node type with default exif field.
   *
   * Default values are title, model, keywords.
   * Default behavior but 'promoted to front'.
   *
   * used by routing.yml
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createPhotographyMediaType() {
    $typeName = 'Photography';
    $entity_type = 'media';
    $machineName = strtolower($typeName);

    try {
      // Check the module is installed.
      if (interface_exists('\Drupal\media\MediaInterface')) {
        if (!($type_definition = MediaType::load($machineName))) {
          $type_definition = MediaType::create([
            'id' => $machineName,
            'label' => $typeName,
            'description' => 'Use Photography for content where the photo is the main content. You still can have some other information related to the photo itself.',
            'source' => 'image',
            'source_configuration' => [
              'source_field' => 'field_image',
            ],
            'field_map' => [],
            'new_revision' => FALSE,
          ]);
          $type_definition->save();
        }

        // Add default display.
        $values = [
          'targetEntityType' => $entity_type,
          'bundle' => $machineName,
          'mode' => 'default',
          'status' => TRUE,
        ];
        $this->entityTypeManager()
          ->getStorage('entity_view_display')
          ->create($values);

        // Add default form display.
        $values = [
          'targetEntityType' => $entity_type,
          'bundle' => $machineName,
          'mode' => 'default',
          'status' => TRUE,
        ];
        $this->entityTypeManager()
          ->getStorage('entity_form_display')
          ->create($values);

        // Then add fields.
        $this->addFields($entity_type, $type_definition);
        $message = $this->t('The %entitytype type %type has been fully created', [
          '%entitytype' => $entity_type,
          '%type' => $typeName,
        ]);
      }
      else {
        $message = 'Nothing done. Media module is not present.';
      }
    } catch (FieldException $fe) {
      $message = $this->t('An unexpected error was thrown during creation : ') . $fe->getMessage();
    }
    drupal_set_message($message);
    $response = new RedirectResponse('/admin/config/media/exif/helper');
    $response->send();
    exit();
  }

  /**
   * Create a sample HTML Fragment.
   *
   * @return array
   *   HTML Fragment with a sample image and metadata.
   */
  public function showSample() {
    $sampleImageFilePath = drupal_get_path('module', 'exif') . '/sample.jpg';
    $exif = ExifFactory::getExifInterface();
    $fullmetadata = $exif->readMetadataTags($sampleImageFilePath);
    $html = '<table class="metadata-table"><tbody>';
    foreach ($fullmetadata as $currentSection => $currentValues) {
      $html .= '<tr class="metadata-section"><td colspan="2">' . $currentSection . '</td></tr>';
      foreach ($currentValues as $currentKey => $currentValue) {
        $exif_value = $this->sanitizeValue($currentValue);
        $html .= '<tr class="metadata-row ' . $currentKey . '"><td class="metadata-key">' . $currentKey . '</td><td class="metadata-value">' . $exif_value . '</td></tr>';
      }
    }
    $html .= '</tbody><tfoot></tfoot></table>';
    return [
      '#metadata' => $html,
      '#image_path' => '/' . $sampleImageFilePath,
      '#taxo' => '',
      '#permissionLink' => '',
      '#taxonomyFragment' => '',
      '#theme' => 'exif_sample',
      '#attached' => [
        'library' => [
          'exif/exif-sample',
        ],
      ],
    ];
  }

  /**
   * Add a field to a entity type.
   *
   * @param string $entity_type
   *   The entity type name to be modified.
   * @param \Drupal\Core\Entity\EntityInterface $type_definition
   *   The definition of type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addFields($entity_type, EntityInterface $type_definition) {
    // First, add image field.
    $this->addFieldToEntityType($entity_type, $type_definition, 'Photo', 'image', 'image', 'exif_readonly');
    $widget_settings = [
      'image_field' => 'field_image',
      'exif_field' => 'naming_convention',
    ];

    // Then add all extra fields (metadata)
    // Date type.
    $this->addFieldToEntityType($entity_type, $type_definition, 'Creation date', 'exif_datetime', 'datetime', 'exif_readonly', $widget_settings);
    // Text type.
    $this->addFieldToEntityType($entity_type, $type_definition, 'Photo Comment', 'exif_comments', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'Photo Description', 'exif_imagedescription', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'Photo Title', 'exif_title', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'Make', 'exif_make', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'Aperture', 'exif_aperturefnumber', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'Exposure', 'exif_exposuretime', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'ISO', 'exif_isospeedratings', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'Focal', 'exif_focallength', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'Flash', 'exif_flash', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'Exposure Program', 'exif_exposureprogram', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'Exposure Mode', 'exif_exposuremode', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'White Balance Mode', 'exif_whitebalance', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'scene Mode', 'exif_scenecapturetype', 'text', 'exif_readonly', $widget_settings);
    $this->addFieldToEntityType($entity_type, $type_definition, 'orientation', 'exif_orientation', 'text', 'exif_readonly', $widget_settings);
    // Terms Type (taxonomy).
    $this->addReferenceToEntityType($entity_type, $type_definition, 'Photographer', 'exif_author', 'taxonomy_term', 'photographs_metadata', 'exif_readonly', $widget_settings);
    $this->addReferenceToEntityType($entity_type, $type_definition, 'Camera', 'exif_model', 'taxonomy_term', 'photographs_metadata', 'exif_readonly', $widget_settings);
    $this->addReferenceToEntityType($entity_type, $type_definition, 'ISO', 'exif_isospeedratings', 'taxonomy_term', 'photographs_metadata', 'exif_readonly', $widget_settings);
    $widget_settings_for_tags = [
      'image_field' => 'field_image',
      'exif_field' => 'naming_convention',
      'exif_field_separator' => ';',
    ];
    $this->addReferenceToEntityType($entity_type, $type_definition, 'Tags', 'exif_keywords', 'taxonomy_term', 'photographs_metadata', 'exif_readonly', $widget_settings_for_tags);
  }

  /**
   * Add a Field to an Entity Type.
   *
   * @param string $entity_type
   *   The entity type name to be modified.
   * @param \Drupal\Core\Entity\EntityInterface $type
   *   The definition of type.
   * @param string $fieldLabel
   *   Field description (what is show in forms).
   * @param string $fieldName
   *   Field name (the real one used internally).
   * @param string $fieldType
   *   Name of the field type to be added.
   * @param string $fieldWidget
   *   Name of the widget to use.
   * @param array $widgetSettings
   *   Settings to set for the widget.
   * @param array $settings
   *   Specific setting for the field (optional).
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addFieldToEntityType($entity_type, EntityInterface $type, $fieldLabel, $fieldName, $fieldType, $fieldWidget, array $widgetSettings = [], array $settings = []) {
    $realFieldName = 'field_' . $fieldName;
    $storage = $this->getFieldStorageConfig();
    $field_storage = $storage->load($entity_type . '.' . $realFieldName);
    if (empty($field_storage)) {
      $field_storage_values = [
        'field_name' => $realFieldName,
        'field_label' => $fieldLabel,
        'entity_type' => $entity_type,
        'bundle' => $type->id(),
        'type' => $fieldType,
        'translatable' => FALSE,
      ];
      $storage->create($field_storage_values)->save();
    }
    $fieldSettings = ['display_summary' => TRUE];
    $this->entityAddExtraField($entity_type, $type, $realFieldName, $fieldLabel, $fieldSettings, $fieldWidget, $widgetSettings);
  }

  /**
   * Get storage for fields configuration.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFieldStorageConfig() {
    return $this->entityTypeManager()
      ->getStorage('field_storage_config');
  }

  /**
   * Get EntityStorage for Fields Configuration.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   storage of fields configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFieldConfig() {
    return $this->entityTypeManager()
      ->getStorage('field_config');
  }

  /**
   * Add a new field to the entity type.
   *
   * @param string $entity_type
   *   The entity type name to be modified.
   * @param \Drupal\Core\Entity\EntityInterface $type
   *   The definition of type.
   * @param string $fieldName
   *   Field name (the real one used internally).
   * @param string $fieldLabel
   *   Field description (what is show in forms).
   * @param array $fieldSettings
   *   Settings for the field.
   * @param string $fieldWidget
   *   Name of the widget to use.
   * @param array $widgetSettings
   *   Settings to set for the widget.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The Field Entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function entityAddExtraField($entity_type, EntityInterface $type, $fieldName, $fieldLabel, array $fieldSettings, $fieldWidget, array $widgetSettings) {
    $machineName = strtolower($fieldName);
    // Add or remove the body field, as needed.
    $storage = $this->getFieldStorageConfig();
    $field_storage = $storage->load($entity_type . '.' . $machineName);
    $field_config = $this->getFieldConfig();
    $field = $field_config->load($entity_type . '.' . $type->id() . '.' . $machineName);
    if (empty($field)) {
      $field = $field_config->create([
        'field_storage' => $field_storage,
        'bundle' => $type->id(),
        'label' => $fieldLabel,
        'settings' => $fieldSettings,
      ]);
      $field->save();
    }

    // Assign widget settings for the 'default' form mode.
    $this->entity_get_form_display($entity_type, $type->id(), 'default')
      ->setComponent($machineName, [
        'type' => $fieldWidget,
        'settings' => $widgetSettings,
      ])
      ->save();

    // Assign display settings for the 'default' and 'teaser' view modes.
    $this->entity_get_display($entity_type, $type->id(), 'default')
      ->setComponent($machineName, [
        'label' => 'hidden',
        'type' => 'text_default',
      ])
      ->save();

    // The teaser view mode is created by the Standard profile and therefore
    // might not exist.
    $view_modes = $this->entityDisplayRepository->getViewModes($entity_type);
    if (isset($view_modes['teaser'])) {
      $this->entity_get_display($entity_type, $type->id(), 'teaser')
        ->setComponent($machineName, [
          'label' => 'hidden',
          'type' => 'text_summary_or_trimmed',
        ])
        ->save();
    }
    return $field;
  }

  /**
   * Add a field that reference a vocabulary.
   *
   * @param string $entity_type
   *   The entity type name to be modified.
   * @param \Drupal\Core\Entity\EntityInterface $type
   *   The definition of type.
   * @param string $fieldLabel
   *   Field description (what is show in forms).
   * @param string $fieldName
   *   Field name (the real one used internally).
   * @param string $fieldType
   *   Name of the field type to be added.
   * @param string $fieldTypeBundle
   *   Name of the bundle to be used.
   * @param string $fieldWidget
   *   Name of the widget to use.
   * @param array $widgetSettings
   *   Settings to set for the widget.
   * @param array $settings
   *   Specific setting for the field (optional).
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addReferenceToEntityType($entity_type, EntityInterface $type, $fieldLabel, $fieldName, $fieldType, $fieldTypeBundle, $fieldWidget, array $widgetSettings = [], array $settings = []) {
    $realFieldName = 'field_' . $fieldName;
    $storage = $this->getFieldStorageConfig();
    $field_storage = $storage->load($entity_type . '.' . $realFieldName);
    if (empty($field_storage)) {
      $field_storage_values = [
        'field_name' => $realFieldName,
        'field_label' => $fieldLabel,
        'entity_type' => $entity_type,
        'bundle' => $type->id(),
        'type' => 'entity_reference',
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
        'translatable' => FALSE,
        'settings' => [
          'target_type' => $fieldType,
        ],
      ];
      $temp = $storage->create($field_storage_values);
      $temp->save();
    }
    $fieldSettings = [
      'handler' => 'default:' . $fieldType,
      'handler_settings' => [
        'target_bundles' => [$fieldTypeBundle => $fieldTypeBundle],
      ],
      'sort' => ['field' => '_none'],
      'auto_create' => FALSE,
      'auto_create_bundle' => '',
    ];
    $this->entityAddExtraField($entity_type, $type, $realFieldName, $fieldLabel, $fieldSettings, $fieldWidget, $widgetSettings);
  }

  /**
   * Escape a string by using HTML entities.
   *
   * @param string $exif_value
   *   UTF8 values to be escaped.
   *
   * @return string
   *   value with HTML Entities.
   */
  protected function sanitizeValue($exif_value) {
    if (!Unicode::validateUtf8($exif_value)) {
      $exif_value = Html::escape(utf8_encode($exif_value));
    }
    return $exif_value;
  }

  /**
   * Ensure field is visible in default form.
   *
   * @param string $field_name
   *   Name of the field to add to the view.
   * @param int $widget_id
   *   Widget used in the form or NULL if default.
   */
  protected function configureEntityFormDisplay($field_name, $widget_id = NULL) {
    // Make sure the field is displayed in the 'default' form mode (using
    // default widget and settings). It stays hidden for other form modes
    // until it is explicitly configured.
    $options = $widget_id ? ['type' => $widget_id] : [];
    $this->entity_get_form_display($this->entityTypeId, $this->bundle, 'default')
      ->setComponent($field_name, $options)
      ->save();
  }

  /**
   * Ensure field is visible in default view.
   *
   * @param string $field_name
   *   Name of the field to add to the view.
   * @param int $formatter_id
   *   Formatter associated to this view and this field or NULL if default.
   */
  protected function configureEntityViewDisplay($field_name, $formatter_id = NULL) {
    // Make sure the field is displayed in the 'default' view mode (using
    // default formatter and settings). It stays hidden for other view
    // modes until it is explicitly configured.
    $options = $formatter_id ? ['type' => $formatter_id] : [];
    $this->entity_get_display($this->entityTypeId, $this->bundle, 'default')
      ->setComponent($field_name, $options)
      ->save();
  }

  /**
   * Implements hook_entity_get_form_display().
   */
  public function entity_get_form_display($entity_type, $bundle, $form_mode) {
    // Try loading the entity from configuration.
    $entity_form_display = EntityFormDisplay::load($entity_type . '.' . $bundle . '.' . $form_mode);

    // If not found, create a fresh entity object. We do not preemptively create
    // new entity form display configuration entries for each existing entity
    // type and bundle whenever a new form mode becomes available.
    // Instead, configuration entries are only created when an entity form
    // display is explicitly configured and saved.
    if (!$entity_form_display) {
      $entity_form_display = EntityFormDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $form_mode,
        'status' => TRUE,
      ]);
    }

    return $entity_form_display;
  }

  /**
   * Implements hook_entity_get_display().
   */
  public function entity_get_display($entity_type, $bundle, $view_mode) {
    // Try loading the display from configuration.
    $display = EntityViewDisplay::load($entity_type . '.' . $bundle . '.' . $view_mode);

    // If not found, create a fresh display object. We do not preemptively
    // create new entity_view_display configuration entries for each existing
    // entity type and bundle whenever a new view mode becomes available.
    // Instead, configuration entries are only created when a display object
    // is explicitly configured and saved.
    if (!$display) {
      $display = EntityViewDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $view_mode,
        'status' => TRUE,
      ]);
    }

    return $display;
  }

}
