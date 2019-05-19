<?php

namespace Drupal\taxonomy_scheduler\Service;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem;
use Psr\Log\LoggerInterface;

/**
 * Class TaxonomySchedulerFieldManager.
 */
class TaxonomySchedulerFieldManager {

  use StringTranslationTrait;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * FieldConfig.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $fieldConfig;

  /**
   * FieldStorageConfig.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $fieldStorageConfig;

  /**
   * EntityFormDisplay.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $entityFormDisplay;

  /**
   * TaxonomySchedulerFieldManager constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Entity\EntityStorageInterface $fieldStorageConfig
   *   The field storage config.
   * @param \Drupal\Core\Entity\EntityStorageInterface $fieldConfig
   *   The field config.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entityFormDisplay
   *   The entity form display.
   */
  public function __construct(
    LoggerInterface $logger,
    EntityStorageInterface $fieldStorageConfig,
    EntityStorageInterface $fieldConfig,
    EntityStorageInterface $entityFormDisplay
  ) {
    $this->logger = $logger;
    $this->fieldStorageConfig = $fieldStorageConfig;
    $this->fieldConfig = $fieldConfig;
    $this->entityFormDisplay = $entityFormDisplay;
  }

  /**
   * Adds the field storage configuration, if not exists.
   *
   * @param \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem $fieldStorageItem
   *   The field storage item.
   */
  private function addFieldStorageConfig(TaxonomyFieldStorageItem $fieldStorageItem): void {
    $fieldName = $fieldStorageItem->getFieldName();

    if ($this->fieldStorageConfigExists($fieldStorageItem)) {
      return;
    }

    $fieldStorageConfig = [
      'entity_type' => 'taxonomy_term',
      'field_name' => $fieldName,
      'type' => 'datetime',
      'settings' => [],
      'cardinality' => 1,
    ];

    try {
      $this->fieldStorageConfig->create($fieldStorageConfig)->save();
    }
    catch (EntityStorageException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Checks if the field storage already exists.
   *
   * @param \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem $fieldStorageItem
   *   The field storage item.
   *
   * @return bool
   *   TRUE if exists, FALSE otherwise.
   */
  private function fieldStorageConfigExists(TaxonomyFieldStorageItem $fieldStorageItem): bool {
    $fieldName = $fieldStorageItem->getFieldName();
    $existingField = $this->fieldStorageConfig->load('taxonomy_term.' . $fieldName);
    return ($existingField !== NULL);
  }

  /**
   * FieldConfigExists.
   *
   * @param \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem $fieldStorageItem
   *   The field storage item.
   * @param string $vocabulary
   *   The vocabulary id.
   *
   * @return bool
   *   TRUE if exists, else FALSE.
   */
  private function fieldConfigExists(TaxonomyFieldStorageItem $fieldStorageItem, string $vocabulary): bool {
    $fieldName = $fieldStorageItem->getFieldName();
    $existingField = $this->fieldConfig->load('taxonomy_term.' . $vocabulary . '.' . $fieldName);
    return ($existingField !== NULL);
  }

  /**
   * Adds the field configuration.
   *
   * @param \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem $fieldStorageItem
   *   The field storage item.
   * @param string $vocabulary
   *   The vocabulary id.
   */
  private function addFieldConfig(TaxonomyFieldStorageItem $fieldStorageItem, string $vocabulary): void {
    $fieldName = $fieldStorageItem->getFieldName();
    $fieldLabel = $fieldStorageItem->getFieldLabel();
    $fieldRequired = $fieldStorageItem->getFieldRequired();

    try {
      $fieldConfig = [
        'entity_type' => 'taxonomy_term',
        'field_name' => $fieldName,
        'bundle' => $vocabulary,
        'label' => $fieldLabel,
        'required' => $fieldRequired,
      ];

      $this->fieldConfig->create($fieldConfig)->save();
    }
    catch (EntityStorageException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Updates field configuration.
   *
   * @param \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem $fieldStorageItem
   *   The field storage item.
   */
  private function updateFieldConfig(TaxonomyFieldStorageItem $fieldStorageItem): void {
    $vocabularies = $fieldStorageItem->getVocabularies();
    $fieldName = $fieldStorageItem->getFieldName();

    foreach ($vocabularies as $vocabulary) {
      $fieldConfig = $this->fieldConfig->load('taxonomy_term.' . $vocabulary . '.' . $fieldName);

      if (!$fieldConfig instanceof FieldConfigInterface) {
        continue;
      }

      try {
        $fieldConfig->setLabel($fieldStorageItem->getFieldLabel());
        $fieldConfig->setRequired($fieldStorageItem->getFieldRequired());
        $fieldConfig->save();
      }
      catch (EntityStorageException $e) {
        $this->logger->error($e->getMessage());
      }
    }
  }

  /**
   * Adds a field (if not exists) and enables the form display.
   *
   * @param \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem $fieldStorageItem
   *   The field storage item.
   */
  public function addField(TaxonomyFieldStorageItem $fieldStorageItem): void {
    $vocabularies = $fieldStorageItem->getVocabularies();
    $this->addFieldStorageConfig($fieldStorageItem);

    foreach ($vocabularies as $vocabulary) {
      if ($this->fieldConfigExists($fieldStorageItem, $vocabulary)) {
        $this->updateFieldConfig($fieldStorageItem);
        continue;
      }

      $this->addFieldConfig($fieldStorageItem, $vocabulary);
    }
  }

  /**
   * Disables an existing field's form display.
   *
   * @param \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem $fieldStorageItem
   *   The field storage item.
   */
  public function disableField(TaxonomyFieldStorageItem $fieldStorageItem): void {
    $fieldName = $fieldStorageItem->getFieldName();
    $vocabularies = $fieldStorageItem->getVocabularies();

    foreach ($vocabularies as $vocabulary) {
      $this->removeFormDisplay($vocabulary, $fieldName);
    }
  }

  /**
   * Enables a field's form display.
   *
   * @param \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem $fieldStorageItem
   *   The field storage item.
   */
  public function enableField(TaxonomyFieldStorageItem $fieldStorageItem): void {
    $fieldName = $fieldStorageItem->getFieldName();
    $vocabularies = $fieldStorageItem->getVocabularies();

    foreach ($vocabularies as $vocabulary) {
      $this->setFormDisplay($vocabulary, $fieldName);
    }
  }

  /**
   * Sets the form display.
   *
   * @param string $bundle
   *   The bundle.
   * @param string $fieldName
   *   The field name.
   */
  private function setFormDisplay(string $bundle, string $fieldName): void {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $formDisplay */
    $formDisplay = $this->entityFormDisplay->load('taxonomy_term.' . $bundle . '.default');

    if (!$formDisplay) {
      try {
        $formDisplay = $this->entityFormDisplay->create([
          'targetEntityType' => 'taxonomy_term',
          'bundle' => $bundle,
          'mode' => 'default',
          'status' => TRUE,
        ]);
        $formDisplay->save();
      }
      catch (EntityStorageException $e) {
        $this->logger->error($e->getMessage());
      }
    }

    if (!$formDisplay instanceof EntityInterface) {
      return;
    }

    try {
      $formDisplay->setComponent($fieldName, ['type' => 'datetime_default'])
        ->save();
    }
    catch (EntityStorageException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Removes a form display for a field.
   *
   * @param string $bundle
   *   The bundle.
   * @param string $fieldName
   *   The name of the field.
   */
  private function removeFormDisplay(string $bundle, string $fieldName): void {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $formDisplay */
    $formDisplay = $this->entityFormDisplay->load('taxonomy_term.' . $bundle . '.default');

    if (!$formDisplay instanceof EntityFormDisplay) {
      return;
    }

    try {
      $formDisplay->removeComponent($fieldName)
        ->save();
    }
    catch (EntityStorageException $e) {
      $this->logger->error($e->getMessage());
    }
  }

}
