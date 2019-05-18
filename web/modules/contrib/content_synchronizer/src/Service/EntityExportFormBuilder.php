<?php

namespace Drupal\content_synchronizer\Service;

use Drupal\content_synchronizer\Entity\ExportEntity;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * The entity export form builder.
 */
class EntityExportFormBuilder {

  const SERVICE_NAME = "content_synchronizer.entity_export_form_builder";
  const ARCHIVE_PARAMS = 'archive';

  /**
   * The current url.
   *
   * @var \Drupal\Core\Url
   */
  protected $currentUrl;

  /**
   * Add the export submform in the entity edition form, if the entity is exportable.
   */
  public function addExportFields(array &$form, FormStateInterface $formState) {
    if ($this->isEntityEditForm($form, $formState)) {
      $this->addExportFieldsToEntityForm($form, $formState);
    }
  }

  /**
   * Return true if the form needs to have an export field.
   *
   * @param array $form
   *   The form build array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The formState array.
   *
   * @return bool
   *   The result.
   */
  protected function isEntityEditForm(array &$form, FormStateInterface $formState) {
    /** @var \Drupal\Core\Entity\EntityForm $formObject */
    $formObject = $formState->getFormObject();

    if ($formObject instanceof EntityForm) {
      if (in_array($formObject->getOperation(), ['edit', 'default'])) {
        $entity = $formObject->getEntity();
        if (strpos(get_class($entity), 'content_synchronizer') === FALSE) {
          if ($objectId = $entity->id()) {
            return isset($objectId);
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Add exports fields to the entity form.
   *
   * @param array $form
   *   The form build array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  protected function addExportFieldsToEntityForm(array &$form, FormStateInterface $formState) {
    $entity = $formState->getFormObject()->getEntity();
    $isBundle = $entity instanceof ConfigEntityBundleBase;
    if ($entity instanceof ContentEntityBase || $isBundle) {
      $this->initExportForm($entity, $form, $formState, $isBundle);
    }
  }

  /**
   * Init the export form.
   */
  protected function initExportForm(EntityInterface $entity, array &$form, FormStateInterface $formState, $isBundle = FALSE) {
    /** @var ExportManager $exportManager */
    $exportManager = \Drupal::service(ExportManager::SERVICE_NAME);

    $form['content_synchronizer'] = [
      '#type'   => 'details',
      '#title'  => $isBundle ? t('Export all entities of @bundle bundle', ['@bundle' => $entity->label()]) : t('Export'),
      '#group'  => 'advanced',
      '#weight' => '100'
    ];

    // Init labels.
    $quickExportButton = $isBundle ? t('Export entities') : t('Export entity');
    $addToExportButton = $isBundle ? t('Or add the entities to an existing export') : t('Or add the entity to an existing export');

    $form['content_synchronizer']['quick_export'] = [
      '#markup' => '<a href="' . $this->getQuickExportUrl($entity) . '" class="button button--primary">' . $quickExportButton . '</a>',
    ];

    $exportsListOptions = $exportManager->getExportsListOptions();
    if (!empty($exportsListOptions)) {
      $form['content_synchronizer']['exports_list'] = [
        '#type'          => 'checkboxes',
        '#title'         => $addToExportButton,
        '#options'       => $exportsListOptions,
        '#default_value' => array_keys($exportManager->getEntitiesExport($entity)),
      ];

      $form['content_synchronizer']['add_to_export'] = [
        '#type'   => 'submit',
        '#value'  => t('Add to the choosen export'),
        '#submit' => [get_called_class() . '::onAddToExport'],
      ];
    }
  }

  /**
   * Get the batch URL.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   THe entity to download.
   *
   * @return string
   *   THe batch url.
   */
  protected function getQuickExportUrl(EntityInterface $entity) {
    $url = Url::fromRoute('content_synchronizer.quick_export');
    $parameters = [
      'destination'  => \Drupal::request()->getRequestUri(),
      'entityTypeId' => $entity->getEntityTypeId(),
      'entityId'     => $entity->id(),
    ];

    return $url->toString() . '?' . http_build_query($parameters);
  }

  /**
   * Add entity to an existing entity export.
   *
   * @param array $form
   *   The form build array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public static function onAddToExport(array &$form, FormStateInterface $formState) {
    $exportsList = ExportEntity::loadMultiple($formState->getValue('exports_list'));
    $entity = $formState->getFormObject()->getEntity();

    if ($entity instanceof ConfigEntityBundleBase) {
      if ($entitiesToExport = self::getEntitiesFromBundle($entity)) {
        /** @var \Drupal\content_synchronizer\Entity\ExportEntity $export */
        foreach (ExportEntity::loadMultiple() as $export) {
          foreach ($entitiesToExport as $entityToExport) {
            if (array_key_exists($export->id(), $exportsList)) {
              $export->addEntity($entityToExport);
            }
          }
        }
      }
    }
    else {
      /** @var \Drupal\content_synchronizer\Entity\ExportEntity $export */
      foreach (ExportEntity::loadMultiple() as $export) {
        if (array_key_exists($export->id(), $exportsList)) {
          $export->addEntity($entity);
        }
        else {
          $export->removeEntity($entity);
        }
      }
    }
  }

  /**
   * Get the list of entities from a bundle entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityBundleBase $entity
   *   The bundle entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|null
   *   The entities of the bundle.
   */
  public static function getEntitiesFromBundle(ConfigEntityBundleBase $entity) {
    $entityType = $entity->getEntityType()->getBundleOf();
    $bundleKey = \Drupal::entityTypeManager()
      ->getDefinitions()[$entityType]->getKeys()['bundle'];

    $query = \Drupal::entityQuery($entityType)
      ->condition($bundleKey, $entity->id());
    $entitiesIds = $query->execute();
    if (!empty($entitiesIds)) {
      return \Drupal::entityTypeManager()
        ->getStorage($entityType)
        ->loadMultiple($entitiesIds);
    }
    return NULL;
  }

}
