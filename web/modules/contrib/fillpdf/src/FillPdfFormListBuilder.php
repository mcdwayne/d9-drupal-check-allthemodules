<?php

namespace Drupal\fillpdf;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of FillPdf forms.
 *
 * This is a minimal listbuilder implementation only used as a provider for
 * default operations in Views. Additional operations may be injected using
 * hook_entity_operation(). Existing operations may be altered using
 * hook_entity_operation_alter().
 *
 * @see hook_entity_operation()
 * @see hook_entity_operation_alter()
 */
class FillPdfFormListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {

    $duplicate = [
        'title' => t('Duplicate'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('duplicate-form')),
    ];
    $export = [
        'title' => t('Export configuration'),
        'weight' => 20,
        'url' => $this->ensureDestination($entity->toUrl('export-form')),
    ];
    $import = [
        'title' => t('Import configuration'),
        'weight' => 30,
        'url' => $this->ensureDestination($entity->toUrl('import-form')),
        ];

    $operations = parent::getDefaultOperations($entity) + [
      'duplicate' => $duplicate,
      'export' => $export,
      'import' => $import,
    ];

    return $operations;
  }

}
