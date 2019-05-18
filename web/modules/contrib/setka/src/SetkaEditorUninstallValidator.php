<?php

namespace Drupal\setka_editor;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Prevents uninstallation of modules providing active field storage.
 */
class SetkaEditorUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal entity display repository interface.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManagerInterface, EntityFieldManagerInterface $entityFieldManagerInterface, EntityDisplayRepositoryInterface $entityDisplayRepositoryInterface) {
    $this->entityTypeManager = $entityTypeManagerInterface;
    $this->entityDisplayRepository = $entityDisplayRepositoryInterface;
    $this->entityFieldManager = $entityFieldManagerInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    $widgetFields = [];
    $formatterFields = [];
    if ($module == 'setka_editor') {
      $fieldTypes = [
        'text_long' => 'setka_editor',
        'string_long' => 'setka_editor_string_textarea',
        'text_with_summary' => 'setka_editor_with_summary',
      ];
      foreach ($fieldTypes as $fieldType => $fieldWidget) {
        $fieldMap = $this->entityFieldManager->getFieldMapByFieldType($fieldType);
        foreach ($fieldMap as $entityType => $fieldsData) {
          foreach ($fieldsData as $fieldName => $fieldData) {
            foreach ($fieldData['bundles'] as $bundle) {
              $formModes = $this->entityDisplayRepository->getFormModeOptionsByBundle($entityType, $bundle);
              foreach ($formModes as $formMode => $formModeData) {
                /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $formDisplay */
                $formDisplay = $this->entityTypeManager
                  ->getStorage('entity_form_display')
                  ->load($entityType . '.' . $bundle . '.' . $formMode);
                $component = $formDisplay->getComponent($fieldName);
                if (!empty($component['type']) && $component['type'] == $fieldWidget) {
                  $widgetFields[$entityType][$bundle][] = $fieldName;
                }
              }
              $viewModes = $this->entityDisplayRepository->getViewModeOptionsByBundle($entityType, $bundle);
              foreach ($viewModes as $viewMode => $viewModeData) {
                /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $viewDisplay */
                $viewDisplay = $this->entityTypeManager
                  ->getStorage('entity_view_display')
                  ->load($entityType . '.' . $bundle . '.' . $viewMode);
                $component = $viewDisplay->getComponent($fieldName);
                if (!empty($component['type']) && $component['type'] == 'setka_editor') {
                  $formatterFields[$entityType][$bundle][$viewMode][] = $fieldName;
                }
              }
            }
          }
        }
      }
    }
    if (!empty($widgetFields)) {
      foreach ($widgetFields as $entityType => $entitiesData) {
        foreach ($entitiesData as $bundle => $fields) {
          $fieldsString = implode(', ', $fields);
          $reasons[] = $this->t('Setka Editor widget used by @entityType bundle "@bundle" fields: @fields',
            [
              '@bundle' => $bundle,
              '@entityType' => $entityType,
              '@fields' => $fieldsString,
            ]
          );
        }
      }
    }
    if (!empty($formatterFields)) {
      foreach ($formatterFields as $entityType => $entitiesData) {
        foreach ($entitiesData as $bundle => $viewModes) {
          foreach ($viewModes as $viewMode => $fields) {
            $fieldsString = implode(', ', $fields);
            $reasons[] = $this->t('Setka Editor formatter used by @entityType bundle "@bundle" view mode "@viewMode" fields: @fields',
              [
                '@bundle' => $bundle,
                '@entityType' => $entityType,
                '@viewMode' => $viewMode,
                '@fields' => $fieldsString,
              ]
            );
          }
        }
      }
    }
    return $reasons;
  }

}
