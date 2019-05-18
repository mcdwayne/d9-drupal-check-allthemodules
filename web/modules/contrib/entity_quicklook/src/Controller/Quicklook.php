<?php

namespace Drupal\entity_quicklook\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
* Quicklook controller for Entity Quicklook.
 */
class Quicklook extends ControllerBase {

  /**
   * Render the entity with a specific view mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being rendered.
   * @param string $view_mode
   *   The view mode to use, with "default" being the default value.
   *
   * @return array
   *   The render array for the entity.
   */
  public function renderEntity(EntityInterface $entity, $view_mode = 'default') {
    $viewBuilder = $this->entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
    $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    return $viewBuilder->view($entity, $view_mode, $langcode);
  }

  /**
   * Title callback for the modal.
   *
   * Determine what to use for the modal title.
   *
   * @param string $parent_entity_type
   *   The parent entity type id.
   * @param EntityInterface $parent_entity
   *   The parent entity.
   * @param string $from_view
   *   The view mode from which the Quicklook formatter is being rendered.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode to use for rendering the entity.
   *
   * @return string
   *   The modal title.
   */
  public function modalTitle($parent_entity_type, EntityInterface $parent_entity, $from_view, EntityInterface $entity, $view_mode = 'default') {
    $entity_from_bundle = $parent_entity->bundle();
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $settings */
    $settings = $this->entityTypeManager()->getStorage('entity_view_display');
    $view_display = $settings->load($parent_entity_type . '.' . $entity_from_bundle . '.' . $from_view);
    if (empty($view_display)) {
      $view_display = $settings->load($parent_entity_type . '.' . $entity_from_bundle . '.default');
    }
    $components = $view_display->getComponents();

    $parent_fields = $parent_entity->getFieldDefinitions();
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $parent_field */
    foreach ($parent_fields as $parent_field) {
      $field_name = $parent_field->getName();

      if (array_key_exists($field_name, $components)) {
        if ($components[$field_name]['type'] == 'entity_quicklook_formatter') {
          $title = $components[$field_name]['settings']['modal_title'];
          if (!empty($title)) {
            return $title;
          }
        }
      }
    }

    // If the modal_title setting is empty, then use the entities label.
    $title = $entity->label();
    if (!empty($title)) {
      return $title;
    }
    else {
      $title = $this->t('Entity Quicklook Modal');
      return $title;
    }
  }

  /**
   * Checks access for a specific request.
   *
   * Only grant access if the from_view is the view for which the Quicklook
   * field has been configured to be displayed in. Also confirm that the
   * view_mode is the one that the reference entity was configured to be
   * displayed in.
   *
   * @param string $parent_entity_type
   *   The parent entity type id.
   * @param EntityInterface $parent_entity
   *   The id of the entity the request is coming from.
   * @param string $from_view
   *   The view mode from which the Quicklook formatter is being rendered.
   * @param string $view_mode
   *   The view mode for rendering the referenced entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access($parent_entity_type, EntityInterface $parent_entity, $from_view, $view_mode) {
    // First access check: Confirm that parent entity is not returning null.
    if (!empty($parent_entity)) {
      $entity_from_bundle = $parent_entity->bundle();
    }
    else {
      return AccessResult::forbidden();
    }

    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $settings */
    $settings = $this->entityTypeManager()->getStorage('entity_view_display');
    $view_display = $settings->load($parent_entity_type . '.' . $entity_from_bundle . '.' . $from_view);
    if (empty($view_display)) {
      $view_display = $settings->load($parent_entity_type . '.' . $entity_from_bundle . '.default');
    }
    $components = $view_display->getComponents();

    $parent_fields = $parent_entity->getFieldDefinitions();
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $parent_field */
    foreach ($parent_fields as $parent_field) {
      $field_name = $parent_field->getName();

      // Second access check: Confirm that the view mode the referenced entity
      // has been configured to be displayed with is the same view mode being
      // requested.
      if (array_key_exists($field_name, $components)) {
        if ($components[$field_name]['type'] == 'entity_quicklook_formatter' && $components[$field_name]['settings']['view_mode'] == $view_mode) {
          return AccessResult::allowed();
        }
      }
    }

    // If none of the entities fields are using the Quicklook field formatter
    // then we can conclude that this request is not valid.
    return AccessResult::forbidden();
  }

}
