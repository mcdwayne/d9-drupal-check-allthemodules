<?php

namespace Drupal\exif;

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\taxonomy\Entity\Term;

/**
 * Class ExifContent make link between drupal content and file content.
 *
 * @package Drupal\exif
 */
class ExifContent {

  /**
   * Store the path to local copies of files store by a remote wrapper.
   *
   * Used to clean up temporary file on object destruction.
   *
   * @var array
   */
  private $localCopiesOfRemoteFiles = [];

  /**
   * Allow to fill the title with exif data.
   *
   * Used in hook exif_entity_create.
   *
   * @param string $entityType
   *   The entity type name to be modified.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to look for metadata fields.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkTitle($entityType, FieldableEntityInterface $entity) {
    $bundles_to_check = $this->getBundleForExifData();
    if (in_array($entity->bundle(), $bundles_to_check)) {
      $exif = ExifFactory::getExifInterface();
      $ar_exif_fields = $this->filterFieldsOnSettings($entityType, $entity);
      $ar_exif_fields = $exif->getMetadataFields($ar_exif_fields);
      foreach ($ar_exif_fields as $drupal_field => $metadata_field_descriptor) {
        $field_name = $drupal_field;
        if ($field_name == 'title') {
          $field = $entity->get($field_name);
          if ($field->isEmpty()) {
            $field->appendItem("EXIF_FILLED");
          }
          break;
        }
      }
    }
  }

  /**
   * Check if this node type contains an image field.
   *
   * @return array
   *   List of bundle where the exif data could be updated.
   */
  private function getBundleForExifData() {
    $config = Drupal::config('exif.settings');
    $new_types = [];
    // Fill up array with checked nodetypes.
    foreach ($config->get('nodetypes', []) as $type) {
      if ($type != "0") {
        $new_types[] = $type;
      }
    }
    foreach ($config->get('mediatypes', []) as $type) {
      if ($type != "0") {
        $new_types[] = $type;
      }
    }
    foreach ($config->get('filetypes', []) as $type) {
      if ($type != "0") {
        $new_types[] = $type;
      }
    }
    return $new_types;
  }

  /**
   * Look for metadata fields in an entity type.
   *
   * @param string $entityType
   *   The entity type name to be modified.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to look for metadata fields.
   *
   * @return array
   *   The list of metadata fields found in the entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function filterFieldsOnSettings($entityType, FieldableEntityInterface $entity) {
    $result = [];
    foreach ($entity->getFieldDefinitions() as $fieldName => $fieldDefinition) {
      if ($fieldDefinition instanceof FieldConfigInterface || ($fieldDefinition instanceof BaseFieldDefinition and $fieldName === 'title')) {
        $settings = \Drupal::entityTypeManager()
          ->getStorage('entity_form_display')
          ->load($entityType . '.' . $entity->bundle() . '.default')
          ->getComponent($fieldName)['settings'];
        $exifField = NULL;
        $mediaField = NULL;
        $imageField = NULL;
        $exifFieldSeparator = NULL;
        if ($settings != NULL) {
          if (array_key_exists('exif_field', $settings)) {
            $exifField = $settings['exif_field'];
          }
          if (array_key_exists('exif_field_separator', $settings)) {
            $exifFieldSeparator = $settings['exif_field_separator'];
          }
          if (array_key_exists('image_field', $settings)) {
            $imageField = $settings['image_field'];
          }
          if (array_key_exists('media_generic', $settings)) {
            $mediaField = $settings['media_generic'];
          }
        }
        if (isset($exifField) && ((isset($imageField)) || (isset($mediaField)))) {
          $element = [];
          if ($exifField == 'naming_convention') {
            $name = substr($fieldName, 6);
          }
          else {
            $name = $exifField;
          }
          $element['metadata_field'] = $name;
          if (isset($exifFieldSeparator) && strlen($exifFieldSeparator) > 0) {
            $element['metadata_field_separator'] = $exifFieldSeparator;
          }
          if (!isset($imageField) && isset($mediaField)) {
            $element['image_field'] = $mediaField;
          }
          else {
            $element['image_field'] = $imageField;
          }
          $result[$fieldName] = $element;
        }
      }
    }
    return $result;
  }

  /**
   * Main entry of the module.
   *
   * @param string $entityType
   *   The entity type name to be modified.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to look for metadata fields.
   * @param bool $update
   *   Indicate an Update (against an Insert).
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function entity_insert_update($entityType, FieldableEntityInterface $entity, $update = TRUE) {
    $bundles_to_check = $this->getBundleForExifData();
    if (in_array($entity->bundle(), $bundles_to_check)) {
      $exif = ExifFactory::getExifInterface();
      $ar_exif_fields = $this->filterFieldsOnSettings($entityType, $entity);
      $ar_exif_fields = $exif->getMetadataFields($ar_exif_fields);
      if (!$update && isset($entity->original)) {
        $original = $entity->original;
        foreach ($ar_exif_fields as $drupal_field => $metadata_field_descriptor) {
          $field_name = $drupal_field;
          $field = $entity->get($field_name);
          $field->offsetSet(0, $original->get($field_name));
        }
      }
      else {
        $image_fields = $this->getImageFields($entity);
        $metadata_images_fields = $this->getImageFieldsMetadata($entity, $ar_exif_fields, $image_fields);
        foreach ($ar_exif_fields as $drupal_field => $metadata_field_descriptor) {
          $field_name = $drupal_field;
          $field = $entity->get($field_name);
          $key = $metadata_field_descriptor['metadata_field']['tag'];
          $section = $metadata_field_descriptor['metadata_field']['section'];
          if (array_key_exists($metadata_field_descriptor['image_field'], $metadata_images_fields)) {
            if ($key == "all") {
              $j = 0;
              foreach ($metadata_images_fields[$metadata_field_descriptor['image_field']] as $metadata_image_fields) {
                $html = '<table class="metadata-table"><tbody>';
                foreach ($metadata_image_fields as $currentSection => $currentValues) {
                  $html .= '<tr class="metadata-section"><td colspan=2>' . $currentSection . '</td></tr>';
                  foreach ($currentValues as $currentKey => $currentValue) {
                    $exif_value = $this->sanitizeValue($currentValue);
                    $html .= '<tr class="metadata-value"><td>' . $currentKey . '</td><td>' . $exif_value . '</td></tr>';
                  }
                }
                $html .= '</tbody><tfoot></tfoot></table>';
                $this->handleTextField($j, $field, $section, $key, [
                  "value" => $html,
                  'format' => 'full_html',
                ]);
                $j++;
              }
            }
            else {
              $values = [];
              foreach ($metadata_images_fields[$metadata_field_descriptor['image_field']] as $metadata_image_fields) {
                if (array_key_exists($section, $metadata_image_fields)
                  && array_key_exists($key, $metadata_image_fields[$section])
                ) {
                  $value = $metadata_image_fields[$section][$key];
                  if (is_string($value) && isset($metadata_field_descriptor['metadata_field_separator'])) {
                    if (is_string($metadata_field_descriptor['metadata_field_separator'])) {
                      $subValues = explode($metadata_field_descriptor['metadata_field_separator'], $value);
                      foreach ($subValues as $index => $subValue) {
                        $values[] = $subValue;
                      }
                    }
                    else {
                      $values[] = $value;
                    }
                  }
                  else {
                    if (is_array($value)) {
                      $values = array_merge($values, $value);
                    }
                    else {
                      $values[] = $value;
                    }
                  }
                }
              }
              $j = 0;
              foreach ($values as $innerkey => $value) {
                $this->handleField($j, $field, $section, $key, $value);
                $j++;
              }
            }
          }
        }
      }
    }
  }

  /**
   * Look for image fields in an entity type.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to look for image fields.
   *
   * @return array
   *   the list of image fields found in the entity
   */
  private function getImageFields(FieldableEntityInterface $entity) {
    $result = [];
    if ($entity->getEntityTypeId() == 'node' or $entity->getEntityTypeId() == 'media') {
      foreach ($entity->getFieldDefinitions() as $fieldName => $fieldDefinition) {
        if ($fieldDefinition->getType() == 'image') {
          $result[$fieldName] = $fieldDefinition;
        }
      }
    }
    if ($entity->getEntityTypeId() == 'file') {
      $result['file'] = $entity;
    }
    return $result;
  }

  /**
   * List fields that contains exif metadata.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param $ar_exif_fields
   * @param $image_fields
   *
   * @return array|bool
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function getImageFieldsMetadata(FieldableEntityInterface $entity, &$ar_exif_fields, $image_fields) {
    $result = [];
    if (empty($ar_exif_fields)) {
      return TRUE;
    }
    if (empty($image_fields)) {
      return FALSE;
    }

    foreach ($ar_exif_fields as $drupal_field => $metadata_settings) {
      $field_image_name = $metadata_settings['image_field'];
      if (empty($image_fields[$field_image_name])) {
        $result[$field_image_name] = [];
      }
      else {
        $images_descriptor = $this->getFileUriAndLanguage($entity, $field_image_name);
        if ($images_descriptor == FALSE) {
          $fullmetadata = [];
        }
        else {
          foreach ($images_descriptor as $index => $image_descriptor) {
            $fullmetadata[$index] = $this->getDataFromFileUri($image_descriptor['uri']);
          }
        }
        $result[$field_image_name] = $fullmetadata;
        $ar_exif_fields[$drupal_field]['language'] = $image_descriptor['language'];
      }
    }
    return $result;
  }

  /**
   * Retrieve the URI and Language of an image.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to look for.
   * @param string $field_image_name
   *   The field name containing the image.
   *
   * @return array|bool
   *   Array with uri and language for each images in
   *   or FALSE if the entity type is not known.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function getFileUriAndLanguage(FieldableEntityInterface $entity, $field_image_name) {
    $result = FALSE;
    if ($entity->getEntityTypeId() == 'node' || $entity->getEntityTypeId() == 'media') {
      $image_field_instance = $entity->get($field_image_name);
      if ($image_field_instance instanceof FileFieldItemList) {
        $nbImages = count($image_field_instance->getValue());
        $result = [];
        for ($i = 0; $i < $nbImages; $i++) {
          $result[$i] = [];
          $tmp = $image_field_instance->get($i)->entity;
          $result[$i]['uri'] = $tmp->uri[0];
          $result[$i]['language'] = $tmp->language();
        }
      }
    }
    else {
      if ($entity->getEntityTypeId() == 'file') {
        $result = [];
        $result[0] = [];
        $result[0]['uri'] = $entity->uri;
        $result[0]['language'] = $entity->language();
      }
    }
    return $result;
  }

  /**
   * Retrieve all metadata values from an image.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\UriItem $file_uri
   *   The File URI to look at.
   *
   * @return array
   *   A map of metadata values by key.
   */
  private function getDataFromFileUri(UriItem $file_uri) {
    $uri = $file_uri->getValue()['value'];

    /** @var \Drupal\Core\File\FileSystem $file_system */
    $file_system = \Drupal::service('file_system');
    $scheme = $file_system->uriScheme($uri);

    // If the file isn't stored locally make a temporary copy to read the
    // metadata from. We just assume that the temporary files are always local,
    // hard to figure out how to handle this otherwise.
    if (!isset(\Drupal::service('stream_wrapper_manager')
        ->getWrappers(StreamWrapperInterface::LOCAL)[$scheme])) {
      // Local stream.
      $cache_key = md5($uri);
      if (empty($this->localCopiesOfRemoteFiles[$cache_key])) {
        // Create unique local file.
        if (!($this->localCopiesOfRemoteFiles[$cache_key] = file_unmanaged_copy($uri, 'temporary://exif_' . $cache_key . '_' . basename($uri), FILE_EXISTS_REPLACE))) {
          // Log error if creating a copy fails - but return an empty array to
          // avoid type collision.
          \Drupal::logger('exif')
            ->notice('Unable to create local temporary copy of remote file for exif extraction! File %file.',
              [
                '%file' => $uri,
              ]);
          return [];
        }
      }
      $uri = $this->localCopiesOfRemoteFiles[$cache_key];
    }
    // Read the metadata.
    $exif = ExifFactory::getExifInterface();
    $fullmetadata = $exif->readMetadataTags($file_system->realpath($uri));
    return $fullmetadata;
  }

  /**
   * Ensure no HTML or Javascript will be interpreted in the rendering process.
   *
   * @param string $exif_value
   *   The value retrieve from the image.
   *
   * @return string
   *   The value sanitized.
   */
  private function sanitizeValue($exif_value) {
    if (!Unicode::validateUtf8($exif_value)) {
      $exif_value = Html::escape(utf8_encode($exif_value));
    }
    return $exif_value;
  }

  /**
   * Handle text field.
   *
   * @param int $index
   *   The index to set the new value.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to update.
   * @param string $exif_section
   *   The exif section where value has been retrieved.
   * @param string $exif_name
   *   The exif label where value has been retrieved.
   * @param string $exif_sanitized_value
   *   The exif value to update.
   */
  private function handleTextField($index, FieldItemListInterface &$field, $exif_section, $exif_name, $exif_sanitized_value) {
    $field->offsetSet($index, $exif_sanitized_value);
  }

  /**
   * Handle field by delegating to specific type handler.
   *
   * @param int $index
   *   The index to set the new value.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to update.
   * @param string $exif_section
   *   The exif section where value has been retrieved.
   * @param string $exif_name
   *   The exif label where value has been retrieved.
   * @param string $exif_value
   *   The exif value to update.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function handleField($index, FieldItemListInterface &$field, $exif_section, $exif_name, $exif_value) {
    $value = $this->sanitizeValue($exif_value);
    $field_typename = $field->getFieldDefinition()->getType();
    if (in_array($field_typename, [
      'text',
      'text_long',
      'text_with_summary',
      'string',
      'string_long',
    ])) {
      $this->handleTextField($index, $field, $exif_section, $exif_name, $value);
    }
    else {
      if ($field_typename == 'entity_reference' &&
        $field->getFieldDefinition()
          ->getFieldStorageDefinition()
          ->getSetting('target_type') == 'taxonomy_term'
      ) {
        $this->handleTaxonomyField($index, $field, $exif_section, $exif_name, $value);
      }
      else {
        if ($field_typename == 'datetime' || $field_typename == 'date') {
          $this->handleDateField($index, $field, $exif_section, $exif_name, $value);
        }
      }
    }
  }

  /**
   * Handle taxonomy field.
   *
   * @param int $index
   *   The index to set the new value.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to update.
   * @param string $exif_section
   *   The exif section where value has been retrieved.
   * @param string $exif_name
   *   The exif label where value has been retrieved.
   * @param string $exif_value
   *   The exif value to update.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function handleTaxonomyField($index, FieldItemListInterface &$field, $exif_section, $exif_name, $exif_value) {
    // Look for the term.
    if (!Unicode::validateUtf8($exif_value)) {
      $exif_value = Html::escape(utf8_encode($exif_value));
    }
    $config = Drupal::config('exif.settings');
    $chosen_vocabulary = array_keys($field->getSettings('vocabulary')['handler_settings']['target_bundles'])[0];
    if (isset($chosen_vocabulary)) {
      $terms = taxonomy_term_load_multiple_by_name($exif_value, $chosen_vocabulary);
      if (is_array($terms) && count($terms) > 0) {
        $term = array_shift($terms);
      }
      else {
        // If not exist, create it and also parents if needed.
        $terms = taxonomy_term_load_multiple_by_name($exif_name, $chosen_vocabulary);
        if (is_array($terms) && count($terms) > 0) {
          $parent_term = array_shift($terms);
        }
        else {
          $terms = taxonomy_term_load_multiple_by_name($exif_section, $chosen_vocabulary);
          if (is_array($terms) && count($terms) > 0) {
            $section_term = array_shift($terms);
          }
          else {
            $section_term = $this->createTerm($chosen_vocabulary, $exif_section);
          }
          $parent_term = $this->createTerm($chosen_vocabulary, $exif_name, $section_term->id());
        }
        $term = $this->createTerm($chosen_vocabulary, $exif_value, $parent_term->id());
      }
      $field->offsetSet($index, $term->id());
    }
  }

  /**
   * Create a taxonomy term.
   *
   * @param int $vid
   *   Vocabulary Id.
   * @param string $name
   *   Term name.
   * @param int $parent_term_id
   *   Parent Term Id (or default, 0 if none).
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\taxonomy\Entity\Term
   *   The created Term.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createTerm($vid, $name, $parent_term_id = 0) {
    $values = [
      'vid' => $vid,
      'name' => $name,
      'parent' => $parent_term_id,
    ];
    $term = Term::create($values);
    $term->save();
    return $term;
  }

  /**
   * Handle date field.
   *
   * @param int $index
   *   The index to set the new value.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to update.
   * @param string $exif_section
   *   The exif section where value has been retrieved.
   * @param string $exif_name
   *   The exif label where value has been retrieved.
   * @param string $exif_sanitized_value
   *   The exif value to update.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function handleDateField($index, FieldItemListInterface &$field, $exif_section, $exif_name, $exif_sanitized_value) {

    if ($exif_name == 'filedatetime') {
      $format = 'atom';
    }
    else {
      $format = 'exif';
    }
    $dateFormatStorage = Drupal::getContainer()
      ->get('entity.manager')
      ->getStorage('date_format');
    if ($dateFormatStorage instanceof EntityStorageInterface) {
      // Load format for parsing information from image.
      $dateFormat = $dateFormatStorage->load($format);
      if ($dateFormat instanceof DateFormat) {
        // Exif internal format do not handle timezone :(
        // Using website timezone instead or default storage if none is defined.
        // TODO : drupal_get_user_timezone();
        // Parse string to date following chosen format.
        $date_datetime = DrupalDateTime::createFromFormat($dateFormat->getPattern(), $exif_sanitized_value);
        // Load storage format.
        $storage_format = $field->getFieldDefinition()
          ->getSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE ? DATETIME_DATE_STORAGE_FORMAT : DATETIME_DATETIME_STORAGE_FORMAT;
        // Format date to string for storage.
        $value = $date_datetime->format($storage_format);
        // Store value.
        $field->offsetSet($index, $value);
      }
    }
  }

  /**
   * Cleanup of artifacts from processing files.
   */
  public function __destruct() {
    // Get rid of temporary files created for this instance.
    foreach ($this->localCopiesOfRemoteFiles as $uri) {
      \Drupal::service('file_system')->unlink($uri);
    }
  }

}
