<?php

namespace Drupal\icecat\Controller;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\icecat\IcecatFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IcecatController.
 *
 * @package Drupal\icecat\Controller
 */
class IcecatController implements ContainerInjectionInterface {

  /**
   * The entity used in the controller.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * The entity mapping object.
   *
   * @var \Drupal\icecat\Entity\IcecatMapping
   */
  private $entityMapping;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * Contains the field info for the current entity.
   *
   * @var array
   */
  private $fieldInfo;

  /**
   * Initializes a IcecatController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityFieldManager $entityFieldManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Sets the entity to work with.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Checks if the entity has mapping.
   *
   * @return bool
   *   True if mapping available. False otherwise.
   */
  private function hasMapping() {
    $mapping_link_storage = $this->entityTypeManager->getStorage('icecat_mapping');
    $mappings = $mapping_link_storage->loadByProperties([
      'entity_type' => $this->entity->getEntityTypeId(),
      'entity_type_bundle' => $this->entity->bundle(),
    ]);
    if (!empty($mappings)) {
      $this->entityMapping = reset($mappings);
      $this->fieldInfo = $this->entityFieldManager->getFieldDefinitions($this->entityMapping->getMappingEntityType(), $this->entityMapping->getMappingEntityBundle());
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Gets the mapping links.
   *
   * @return \Drupal\icecat\Entity\IcecatMappingLinkInterface[]
   *   The mapping links.
   */
  private function getMappingLinks() {
    $mapping_link_storage = $this->entityTypeManager->getStorage('icecat_mapping_link');
    /** @var \Drupal\icecat\Entity\IcecatMappingLinkInterface[] $mapping_links */
    $mapping_links = $mapping_link_storage->loadByProperties([
      'mapping' => $this->entityMapping->id(),
    ]);
    return $mapping_links;
  }

  /**
   * Maps the data.
   */
  public function mapEntityData() {
    if ($this->hasMapping()) {
      $entity = $this->entity;
      if ($ean_code = $entity->get($this->entityMapping->getDataInputField())->getValue()) {
        // Initialize a new fetcher object.
        $fetcherSession = new IcecatFetcher($ean_code[0]['value']);
        // Only iterate if we have results..
        if ($result = $fetcherSession->getResult()) {
          // Map the data.
          foreach ($this->getMappingLinks() as $mapping) {
            if ($entity->get($mapping->getLocalField())) {
              switch ($mapping->getRemoteFieldType()) {
                // @todo: We can simplefy this?
                // @todo: Html decode encode/filter xss and security?
                case 'attribute':
                  $this->setSimpleField($mapping->getLocalField(), $result->getAttribute($mapping->getRemoteField()));
                  break;

                case 'other':
                  $this->setSimpleField($mapping->getLocalField(), $result->{$mapping->getRemoteField()}());
                  break;

                case 'images':
                  $this->setImageField($mapping->getLocalField(), $result->getImages());
                  break;

                default:
                  $this->setSimpleField($mapping->getLocalField(), $result->getSpec($mapping->getRemoteField()));
                  break;
              }
            }
          }
        }
      }
    }
  }

  /**
   * Sets the field to the remote value.
   *
   * @param string $field
   *   The local field identifier.
   * @param array $data_raw
   *   List of images to add.
   */
  private function setImageField($field, array $data_raw) {
    // Initialize data array.
    $data = [];

    // Check if we have the field.
    if (isset($this->fieldInfo[$field]) && $field_info = $this->fieldInfo[$field]) {
      $upload_directory = $field_info->getSetting('file_directory');
      $uri_sceme = $field_info->getSetting('uri_scheme');
      $cardinality = $field_info->getFieldStorageDefinition()->getCardinality();
      $user = \Drupal::currentUser();

      // Take just the allowed amount if not unlimited.
      if ($cardinality !== '-1') {
        $data_raw = array_slice($data_raw, 0, $cardinality);
      }

      // Build the upload location.
      $destination = trim($upload_directory, '/');
      $destination = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($destination, $data));
      $upload_location = $uri_sceme . '://' . $destination;

      // Save the images to our filesystem.
      foreach ($data_raw as $image) {
        $image_data = file_get_contents($image['high']);
        $filename = basename($image['high']);

        if ($file = file_save_data($image_data, $upload_location . '/' . $filename, FILE_EXISTS_RENAME)) {
          $data[] = [
            'target_id' => $file->id(),
            'display' => 1,
          ];
        }
      }

      if (!empty($data)) {
        $this->entity->set($field, $data);
      }
    }
  }

  /**
   * Sets the field to the remote value.
   *
   * @param string $field
   *   The local field identifier.
   * @param string $data_raw
   *   The data to add.
   */
  private function setSimpleField($field, $data_raw) {
    if (isset($this->fieldInfo[$field]) && $field_info = $this->fieldInfo[$field]) {
      // FullText.
      if (in_array($field_info->getType(), ['text_with_summary', 'text'])) {
        // Take the current value so that we can just change the content.
        $data = $this->entity->get($field)->getValue();
        $data[0]['value'] = _filter_autop($this->cleanupContent($data_raw));
      }
      else {
        // In other cases we do not want html at all.
        $data = strip_tags($data_raw);
      }

      $this->entity->set($field, $data);
    }
  }

  /**
   * Cleans incoming markup to drupal requirements.
   *
   * @param string $input
   *   The input to be cleaned.
   *
   * @return string
   *   The cleaned markup.
   */
  private function cleanupContent($input) {
    $replacement_html_bad = ['<b>', '</b>', '\n'];
    $replacement_html_good = ['<strong>', '</strong>', '<br />'];

    return str_replace($replacement_html_bad, $replacement_html_good, $input);
  }

}
