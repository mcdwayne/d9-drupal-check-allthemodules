<?php

namespace Drupal\cck_select_other;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides methods for dealing with entity displays.
 *
 * This trait allows CCK Select Other to detect itself on an entity form display
 * and extract its *real* allowed values in order to do widget validation at the
 * field validation level.
 */
trait EntityDisplayTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Get the entity form displays for a field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition to use.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity manager for getting entities from storage.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|null
   *   An array of entity form displays.
   */
  protected function getFormDisplays(FieldDefinitionInterface $definition, EntityTypeManagerInterface $entityManager) {
    $params = ['targetEntityType' => $definition->getTargetEntityTypeId()];

    return $entityManager
      ->getStorage('entity_form_display')
      ->loadByProperties($params);
  }

  /**
   * Determine if a field has the select other widget configured.
   *
   * This is due to "loose coupling", which makes it difficult for a widget to
   * make constraints upon a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition to check.
   *
   * @return bool
   *   TRUE if the definition has a select other widget defined in one of its
   *   form displays.
   */
  public function hasSelectOtherWidget(FieldDefinitionInterface $definition) {
    $displays = $this->getFormDisplays($definition, $this->getEntityTypeManager());
    $field_name = $definition->getName();

    return array_reduce($displays, function (&$result, $display) use ($field_name) {
      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
      if (!$result && $display->getRenderer($field_name) !== NULL) {
        $widget_id = $display->getRenderer($field_name)->getPluginId();
        if ($widget_id === 'cck_select_other') {
          $result = TRUE;
        }
      }
      return $result;
    }, FALSE);
  }

  /**
   * Get the select other widget settings from the form display.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition to extract the widget from.
   *
   * @return array
   *   An array of settings.
   */
  public function getWidgetSettings(FieldDefinitionInterface $definition) {
    $displays = $this->getFormDisplays($definition, $this->getEntityTypeManager());
    $field_name = $definition->getName();
    $settings = [];

    $widget = array_reduce($displays, function (&$result, $display) use ($field_name) {
      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
      if (!$result) {
        $widget = $display->getRenderer($field_name);
        if ($widget && $widget->getPluginId() === 'cck_select_other') {
          $result = $widget;
        }
      }
      return $result;
    }, FALSE);

    if ($widget) {
      $settings = $widget->getSettings();
    }

    return $settings;
  }

  /**
   * Gets the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity_type.manager.
   */
  public function getEntityTypeManager() {
    if (!isset($this->entityTypeManager)) {
      return \Drupal::entityTypeManager();
    }

    return $this->entityTypeManager;
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

}
