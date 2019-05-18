<?php

namespace Drupal\search_api_elasticsearch_attachments\Plugin\search_api\processor;

use Drupal\file\Entity\File;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Bytes;

/**
 * Provides file fields processor for Elasticsearch Attachments.
 *
 * @SearchApiProcessor(
 *   id = "elasticsearch_attachments",
 *   label = @Translation("Elasticsearch attachments"),
 *   description = @Translation("Adds the Elasticsearch attachments content to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *     "pre_index_save" = 0,
 *     "preprocess_index" = -20,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class ElasticsearchAttachments extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The target field name.
   *
   * @var string
   */
  protected $targetFieldId = 'es_attachment';

  /**
   * The target field type.
   *
   * @var string
   */
  protected $targetFieldType = 'object';

  /**
   * List of supported field types.
   *
   * @var array
   */
  protected static $supportedNodeFieldTypes = [
    'file',
    'image',
  ];

  /**
   * The available boost factors.
   *
   * @var array
   */
  protected static $boostFactors = [
    '0.0' => '0.0',
    '0.1' => '0.1',
    '0.2' => '0.2',
    '0.3' => '0.3',
    '0.5' => '0.5',
    '0.8' => '0.8',
    '1.0' => '1.0',
    '2.0' => '2.0',
    '3.0' => '3.0',
    '5.0' => '5.0',
    '8.0' => '8.0',
    '13.0' => '13.0',
    '21.0' => '21.0',
  ];

  /**
   * The mime type guesser service.
   *
   * @var \Drupal\Core\File\MimeType\MimeTypeGuesser
   */
  protected $mimeTypeGuesser;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Key value service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MimeTypeGuesserInterface $mime_type_guesser, ConfigFactoryInterface $config_factory, KeyValueFactoryInterface $key_value) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->configFactory = $config_factory;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration,
                      $plugin_id,
                      $plugin_definition,
                      $container->get('file.mime_type.guesser'),
                      $container->get('config.factory'),
                      $container->get('keyvalue')
                     );
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        // TODO Come up with better label.
        'label' => $this->t('Search API Elasticsearch attachments'),
        // TODO Come up with better description.
        'description' => $this->t('Search API Elasticsearch attachments.'),
        'type' => $this->targetFieldType,
        'processor_id' => $this->getPluginId(),
        // This will be a hidden field,
        // not something a user can add/remove manually.
        'hidden' => TRUE,
      ];

      $properties[$this->targetFieldId] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * Index files.
   *
   * Checks whether we can index the file,
   * and, if so, indexes it.
   */
  private function indexFile(ItemInterface $item, File $file) {
    if ($this->isFileIndexable($file)) {
      $extraction = $this->extractOrGetFromCache($file);
      $targetField = $item->getFields()[$this->targetFieldId];
      $fileData = [
        'filename' => $file->get('filename')->value,
        'data' => $extraction,
      ];
      $targetField->addValue($fileData);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity_type = $item->getDatasource()->getEntityTypeId();
    if (in_array($entity_type, static::$supportedNodeFieldTypes)) {
      $this->indexFile($item, $item->getOriginalObject()->getValue());
    }
    elseif ($entity_type == 'node') {
      $entity = $item->getOriginalObject()->getValue();
      foreach ($entity->getFields() as $field) {
        $data_definition = $field->getDataDefinition();
        if (get_class($data_definition) !== 'Drupal\field\Entity\FieldConfig') {
          continue;
        }
        $storage = $data_definition->getFieldStorageDefinition();
        // Check the field for type and process indexation.
        if (
          in_array($storage->getType(), static::$supportedNodeFieldTypes)
          && !empty($files = $field->referencedEntities())
        ) {
          foreach ($files as $file) {
            $this->indexFile($item, $file);
          }
          continue;
        }
        // Check the entity reference fields and extract attachments data.
        if ($storage instanceof FieldStorageConfigInterface && $storage->getType() !== 'entity_reference') {
          continue;
        }
        if (!isset($storage->getSettings()['target_type']) || $storage->getSettings()['target_type'] !== 'media') {
          continue;
        }

        if ($field->referencedEntities()) {
          foreach ($field->referencedEntities() as $field_value) {
            foreach ($field_value->getFields() as $child_field) {
              $data_definition = $child_field->getDataDefinition();
              if (get_class($data_definition) !== 'Drupal\field\Entity\FieldConfig') {
                continue;
              }
              $storage = $data_definition->getFieldStorageDefinition();
              if (
                in_array($storage->getType(), static::$supportedNodeFieldTypes)
                && !empty($files = $child_field->referencedEntities())
              ) {
                foreach ($files as $file) {
                  $this->indexFile($item, $file);
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    // Automatically add field to index if processor is enabled.
    $field = $this->ensureField(NULL, $this->targetFieldId, $this->targetFieldType);
    // Hide the field.
    $field->setHidden();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (isset($this->configuration['excluded_extensions'])) {
      $defaultExcludedExtensions = $this->configuration['excluded_extensions'];
    }
    else {
      $defaultExcludedExtensions = $this->defaultExcludedExtensions();
    }

    $form['boost'] = [
      '#type' => 'select',
      '#title' => $this->t('Default boost for attachments'),
      '#options' => static::$boostFactors,
      '#default_value' => sprintf('%.1f', isset($this->configuration['boost']) ? $this->configuration['boost'] : 1.0),
    ];

    $form['excluded_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Excluded file extensions'),
      '#default_value' => $defaultExcludedExtensions,
      '#size' => 80,
      '#maxlength' => 255,
      '#description' => $this->t('File extensions that are excluded from indexing. Separate extensions with a space and do not include the leading dot.<br />Example: "aif art avi bmp gif ico mov oga ogv png psd ra ram rgb flv"<br />Extensions are internally mapped to a MIME type, so it is not necessary to put variations that map to the same type (e.g. tif is sufficient for tif and tiff)'),
    ];

    $form['max_filesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum upload size'),
      '#default_value' => isset($this->configuration['max_filesize']) ? $this->configuration['max_filesize'] : '0',
      '#description' => $this->t('Enter a value like "10 KB", "10 MB" or "10 GB" in order to restrict the max file size of files that should be indexed.<br /> Enter "0" for no limit restriction.'),
      '#size' => 10,
    ];

    $form['excluded_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude private files'),
      '#default_value' => isset($this->configuration['excluded_private']) ? $this->configuration['excluded_private'] : TRUE,
      '#description' => $this->t('Check this box if you want to exclude private files from being indexed.'),
    ];

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @see \Drupal\Core\Plugin\PluginFormInterface::validateConfigurationForm()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $maxFilesize = trim($form_state->getValue('max_filesize'));
    if ($maxFilesize != '0') {
      $sizeInfo = explode(' ', $maxFilesize);
      if (count($sizeInfo) != 2) {
        $error = TRUE;
      }
      else {
        $startsInteger = is_int((int) $sizeInfo[0]);
        $unitExpected = in_array($sizeInfo[1], ['KB', 'MB', 'GB']);
        $error = !$startsInteger || !$unitExpected;
      }
      if ($error) {
        $form_state->setErrorByName('max_filesize', $this->t('The max filesize option must contain a valid value. You may either enter "0" (for no restriction) or a string like "10 KB, "10 MB" or "10 GB".'));
      }
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @see \Drupal\Core\Plugin\PluginFormInterface::submitConfigurationForm()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $excludedExtensions = $form_state->getValue('excluded_extensions');
    $excludedExtensionsArray = explode(' ', $excludedExtensions);
    $excludedMimesArray = $this->getExcludedMimes($excludedExtensionsArray);
    $excludedMimesString = implode(' ', $excludedMimesArray);

    $this->setConfiguration($form_state->getValues() + ['excluded_mimes' => $excludedMimesString]);
  }

  /**
   * Default excluded extensions.
   *
   * See: http://cgit.drupalcode.org/search_api_attachments/tree/src/Plugin/search_api/processor/FilesExtrator.php?h=8.x-1.x#n484.
   *
   * @return string
   *   string of file extensions separated by a space.
   */
  public function defaultExcludedExtensions() {
    return 'aif art avi bmp gif ico mov oga ogv png psd ra ram rgb flv jpg jpeg';
  }

  /**
   * Get a corresponding array of excluded mime types.
   *
   * Obtained from a space separated string of file extensions.
   * see: http://cgit.drupalcode.org/search_api_attachments/tree/src/Plugin/search_api/processor/FilesExtrator.php?h=8.x-1.x#n501
   *
   * @param string $extensions
   *   If it's not null, the return will correspond to the extensions.
   *   If it is null,the return will correspond to the default excluded
   *   extensions.
   *
   * @return array
   *   Array or mimes.
   */
  public function getExcludedMimes($extensions = NULL) {
    if (!$extensions && isset($this->configuration['excluded_mimes'])) {
      $excludedMimesString = $this->configuration['excluded_mimes'];
      $excludedMimes = explode(' ', $excludedMimesString);
    }
    else {
      if (!$extensions) {
        $extensions = explode(' ', $this->defaultExcludedExtensions());
      }
      $excludedMimes = [];
      foreach ($extensions as $extension) {
        $excludedMimes[] = $this->mimeTypeGuesser->guess('dummy.' . $extension);
      }
    }
    // Ensure we get an array of unique mime values because many extension can
    // map the the same mime type.
    $excludedMimes = array_combine($excludedMimes, $excludedMimes);
    return array_keys($excludedMimes);
  }

  /**
   * Exclude private files from being indexed.
   *
   * Only happens if the module is configured to do so(default behaviour).
   *
   * @param object $file
   *   File object.
   *
   * @return bool
   *   TRUE if we should prevent current file from being indexed.
   */
  public function isPrivateFileAllowed($file) {
    // Know if private files are allowed to be indexed.
    $privateAllowed = FALSE;
    if (isset($this->configuration['excluded_private'])) {
      $privateAllowed = !(bool) $this->configuration['excluded_private'];
    }
    // Know if current file is private.
    $uri = $file->getFileUri();
    $fileIsPrivate = FALSE;
    if (substr($uri, 0, 10) == 'private://') {
      $fileIsPrivate = TRUE;
    }

    if (!$fileIsPrivate) {
      return TRUE;
    }
    else {
      return $privateAllowed;
    }
  }

  /**
   * Check if the file is allowed to be indexed.
   *
   * @param object $file
   *   A file object.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isFileIndexable($file) {
    // File should exist in disc.
    $indexable = file_exists($file->getFileUri());
    if (!$indexable) {
      return FALSE;
    }

    // File should have a mime type that is allowed.
    $indexable = $indexable && !in_array($file->getMimeType(), $this->getExcludedMimes());
    if (!$indexable) {
      return FALSE;
    }

    // File permanent.
    $indexable = $indexable && $file->isPermanent();
    if (!$indexable) {
      return FALSE;
    }

    // File shouldn't exceed configured file size.
    $indexable = $indexable && $this->isFileSizeAllowed($file);
    if (!$indexable) {
      return FALSE;
    }

    // Whether a private file can be indexed or not.
    $indexable = $indexable && $this->isPrivateFileAllowed($file);
    if (!$indexable) {
      return FALSE;
    }

    return $indexable;
  }

  /**
   * Extract file data or get it from cache if available and cache it.
   *
   * @return string
   *   $extractedData
   */
  public function extractOrGetFromCache($file) {
    $collection = 'search_api_elasticsearch_attachments';
    $key = $collection . ':' . $file->id();

    // Check Cache.
    if ($cache = $this->keyValue->get($collection)->get($key)) {
      // Return Cache.
      $extractedData = $cache;
    }
    else {
      // Extract.
      $extractedData = $this->extract($file);
      // Set cache.
      // @TODO: Large files cause innodb_log_file_size fatal error.
      // $this->keyValue->get($collection)->set($key, $extractedData).
    }

    return $extractedData;
  }

  /**
   * Extract file data and encode it.
   *
   * @param object $file
   *   File to be indexed.
   *
   * @return string
   *   base64 encoded file contents.
   */
  private function extract($file) {
    $path = $file->getFileUri();
    // If path is not set, do nothing.
    if (!isset($path) && empty($path)) {
      // TODO Handle this exception better.
      return '';
    }

    // Load and Encode the file contents.
    $data = file_get_contents($path);
    // TODO Crypt::hashBase64 doesnt seem to work with Elasticsearch.
    // Reverting back to base64_encode().
    $base64 = base64_encode($data);

    // Return the base64 encoded file.
    // TODO Check performance impact with larger files.
    return $base64;
  }

  /**
   * Exclude files that exceed configured max size.
   *
   * @param object $file
   *   File object.
   *
   * @return bool
   *   TRUE if the file size does not exceed configured max size.
   */
  public function isFileSizeAllowed($file) {
    if (isset($this->configuration['max_filesize'])) {
      $configuredSize = $this->configuration['max_filesize'];
      if ($configuredSize == '0') {
        return TRUE;
      }
      else {
        $fileSizeBytes = $file->getSize();
        $configuredSizeBytes = Bytes::toInt($configuredSize);
        if ($fileSizeBytes > $configuredSizeBytes) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
