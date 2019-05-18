<?php

namespace Drupal\entity_reports;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class ReportGenerator contains commonly shared utilities.
 *
 * @package Drupal\entity_reports
 */
class ReportGenerator {

  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface */
  protected $entityFieldManager;

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
  protected $entityTypeManager;

  /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface */
  protected $entityTypeBundleInfo;

  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Extract field information about an entity.
   *
   * @param string $entity_type
   *   Entity type, i.e. node, taxonomy_term
   * @param string $bundle
   *   Entity bundle, i.e. page, article
   *
   * @return array
   *   Array with field information.
   */
  public function generateEntityFieldsReport($entity_type, $bundle) {
    $ret = [];
    $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type);
    $fields_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    foreach ($fields_definitions as $field_definition) {
      /** @var \Drupal\Field\FieldConfigInterface $field_definition */
      if (!empty($field_definition->getTargetBundle()) && !in_array($field_definition->getName(), array_keys($base_fields))) {
        $field_name = $field_definition->getName();
        $count = $field_definition->getFieldStorageDefinition()
          ->getCardinality();
        $cardinality_human = t('Unlimited values');
        $cardinality = $count;
        if ($count != '-1') {
          $cardinality_human = \Drupal::translation()
            ->formatPlural($count, 'One value', '@count values', ['@count' => $count]);
        }
        $field_type = $field_definition->getType();
        if ($field_definition->getType() == 'entity_reference') {
          $field_type .= ' (' . $field_definition->getSetting('target_type') . ')';
        }
        $required = $field_definition->isRequired();
        $required_human = ($required ? \Drupal::translation()->translate('True') : \Drupal::translation()->translate('False'));
        $translatable = $field_definition->isTranslatable();
        $translatable_human = ($translatable ? \Drupal::translation()->translate('True') : \Drupal::translation()->translate('False'));
        $target = $field_definition->getSetting('handler_settings');
        $target_bundles = [];
        if (!empty($target['target_bundles'])) {
          $target_bundles = self::getBundleNames($target['target_bundles'], $field_definition->getSetting('target_type'));
        }
        $ret[$field_name] = [
          'label' => $field_definition->getLabel(),
          'machine_name' => $field_definition->getName(),
          'description' => $field_definition->getDescription(),
          'type' => $field_type,
          'required' => $required,
          'required_human' => $required_human,
          'translatable' => $translatable,
          'translatable_human' => $translatable_human,
          'target' => implode(', ', $target_bundles),
          'cardinality' => $cardinality,
          'cardinality_human' => $cardinality_human,
        ];
      }
    }
    uasort($ret, function($a, $b) {
      return strcasecmp($a['label'], $b['label']);
    });
    return $ret;
  }

  /**
   * Generate a data array with content types field structure.
   * @return array
   *   Array with fields information.
   */
  public function generateContentTypesReport() {
    $ret = [];
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    /** @var \Drupal\Core\Entity\Entity $content_type */
    foreach ($content_types as $content_type) {
      $fields = $this->generateEntityFieldsReport('node', $content_type->id());
      $ret[$content_type->id()] = [
        'id' => $content_type->id(),
        'label' => $this->getBundleName($content_type->id(), 'node'),
        'fields' => $fields,
      ];
    }
    uasort($ret, function($a, $b) {
      return strcasecmp($a['label'], $b['label']);
    });
    return $ret;
  }

  /**
   * Generate a data array with taxonomy field structure and terms.
   *
   * @return array
   *   Array with fields and terms.
   */
  public function generateTaxonomyReport() {
    $ret = [];
    $vocabularies = Vocabulary::loadMultiple();
    /** @var Vocabulary $vocabulary */
    foreach ($vocabularies as $vocabulary) {
      $fields = $this->generateEntityFieldsReport('taxonomy_term', $vocabulary->id());
      // Populate with terms
      /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
      $items = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary->id());
      $terms = [];
      foreach ($items as $item) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($item->tid);
        /** @var \Drupal\taxonomy\Entity\Term $term */
        $terms[$term->id()] = [
          'id' => $term->id(),
          'name' => $term->getName(),
          'description' => new FormattableMarkup($term->getDescription(), []),
          'url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term->id()], ['absolute' => TRUE])->toString(),
        ];
      }
      $ret[$vocabulary->id()] = [
        'id' => $vocabulary->id(),
        'label' => $vocabulary->label(),
        'terms' => $terms,
        'fields' => $fields,
      ];
    }
    return $ret;
  }

  /**
   * Generate a data array with paragraph types field structure.
   * @return array
   *   Array with fields information.
   */
  public function generateParagraphTypesReport() {
    $ret = [];
    $paragraph_types = $this->entityTypeManager->getStorage('paragraphs_type')->loadMultiple();
    /** @var \Drupal\Core\Entity\Entity $paragraph_type */
    foreach ($paragraph_types as $paragraph_type) {
      $fields = $this->generateEntityFieldsReport('paragraph', $paragraph_type->id());
      $ret[$paragraph_type->id()] = [
        'id' => $paragraph_type->id(),
        'label' => $this->getBundleName($paragraph_type->id(), 'paragraph'),
        'fields' => $fields,
      ];
    }
    uasort($ret, function($a, $b) {
      return strcasecmp($a['label'], $b['label']);
    });
    return $ret;
  }

  /**
   * Extract bundle names for multiple machine names.
   *
   * @param array $machine_names
   *   Array of machine names.
   * @param string $entity_type
   *   Name of the entity type.
   *
   * @return array
   *   Array of human bundle names.
   */
  public function getBundleNames(array $machine_names, $entity_type) {
    $ret = [];
    foreach ($machine_names as $machine_name) {
      $ret[$machine_name] = $machine_name;
      if (($bundle_name = $this->getBundleName($machine_name, $entity_type)) && $bundle_name != $machine_name) {
        $ret[$machine_name] = sprintf('%s (%s)', $bundle_name, $machine_name);
      }
    }
    return $ret;
  }

  /**
   * Extract bundle name for a single machine name.
   *
   * @param string $machine_name
   *   Name of machine name.
   * @param string $entity_type
   *   Name of the entity type.
   *
   * @return mixed
   *   Human bundle name.
   */
  public function getBundleName($machine_name, $entity_type) {
    $all_bundles = drupal_static(__METHOD__);
    if (empty($all_bundles)) {
      $all_bundles = $this->entityTypeBundleInfo->getAllBundleInfo();
    }
    if (!empty($all_bundles[$entity_type][$machine_name]['label'])) {
      return $all_bundles[$entity_type][$machine_name]['label'];
    }
    return $machine_name;
  }

}
