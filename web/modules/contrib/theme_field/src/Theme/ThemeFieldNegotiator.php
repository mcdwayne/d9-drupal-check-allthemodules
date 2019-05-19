<?php

namespace Drupal\theme_field\Theme;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Sets the active theme on pages that have a theme_field.
 *
 * @package Drupal\theme_field
 */
class ThemeFieldNegotiator implements ThemeNegotiatorInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;


  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, AdminContext $admin_context) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    global $theme;
    $applies = FALSE;

    // Don't apply the theme if the current route should use the admin theme,
    // typically configured for entity.type.edit_form routes, among others.
    if ($this->adminContext->isAdminRoute($route_match->getRouteObject())) {
      $applies = FALSE;
    }

    // We can only switch themes for routes that show an entity, eg /node/X or
    // /taxonomy/term/Y or /group/Z
    elseif ($entity = $this->getViewedEntity($route_match)) {

      // Check if there is a theme defined directly on the entity.
      if ($theme = $this->getThemeFromEntity($entity)) {
        $applies = TRUE;
      }

      // Otherwise, check if the entity has a reference to another entity
      // that has a theme defined.
      elseif ($theme = $this->getThemeFromEntityReference($entity)) {
        $applies = TRUE;
      }
    }

    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    global $theme;
    return $theme;
  }

  /**
   * Helper method loads the viewed entity from the current route.
   *
   * Adapted from https://www.drupal.org/node/2309797#comment-12752478
   *
   * @return an entity object, when it exists; otherwise null.
   */
  protected function getViewedEntity(RouteMatchInterface $route_match) {
    foreach ($route_match->getParameters() as $param) {
      if ($param instanceof EntityInterface) {
        $page_entity = $param;
        break;
      }
    }
    if (!isset($page_entity)) {
      // Some routes don't properly define entity parameters.
      // Thus, try to load them by its raw Id, if given.
      $types = $this->entityTypeManager->getDefinitions();
      foreach ($route_match->getParameters()->keys() as $param_key) {
        if (!isset($types[$param_key])) {
          continue;
        }
        if ($param = $route_match->getParameter($param_key)) {
          if (is_string($param) || is_numeric($param)) {
            try {
              $page_entity = $this->entityTypeManager->getStorage($param_key)->load($param);
            }
            catch (\Exception $e) {
            }
          }
          break;
        }
      }
    }
    if (!isset($page_entity) || !$page_entity->access('view')) {
      $page_entity = FALSE;
      return NULL;
    }
    return $page_entity;
  }

  /**
   * Helper method determines the theme field value of the specified entity.
   *
   * @return (string) machine name of the theme defined by the specified
   *   entity; null if there is no theme field on the entity.
   */
  protected function getThemeFromEntity(EntityInterface $entity) {
    $theme = NULL;
    $theme_fields = $this->entityFieldManager->getFieldMapByFieldType('theme_field_type');
    if (!empty($theme_fields)) {
      $type = $entity->getEntityTypeId();
      if (!empty($theme_fields[$type])) {
        foreach ($theme_fields[$type] as $field_name => $value) {
          if ($entity->hasField($field_name) && $theme_name = $entity->{$field_name}->getString()) {
            $theme = $theme_name;
          }
        }
      }
    }
    return $theme;
  }

  /**
   * Helper method determines the theme from the first encountered entity
   * reference field.
   *
   * @return (string) machine name of the theme defined by the specified
   *   entity; null if there is no theme field on the entity.
   */
  protected function getThemeFromEntityReference(EntityInterface $entity) {
    $theme = NULL;
    $bundle = $entity->bundle();
    $type = $entity->getEntityTypeId();

    // Gather a list of all entity reference fields.
    $map = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');

    // We only care about entity reference fields on the current entity.
    $reference_field_ids = [];
    if (!empty($map[$type])) {
      foreach ($map[$type] as $name => $data) {
        if (!empty($data['bundles'][$bundle])) {
          // TODO: Create a UI to let site builder specify which reference
          // fields may be used to determine the theme, for improved
          // performance and more dependable logic. The order in which we
          // process reference fields is currently arbitrary, based on the
          // order returned by EntityFieldManager::getFieldMapByFieldType()
          $reference_field_ids[] = "$type.$bundle.$name";
        }
      }
    }

    // Gather a list of all theme fields grouped by entity type.
    $theme_fields = $this->entityFieldManager->getFieldMapByFieldType('theme_field_type');

    // Determine if any of the reference fields on the current entity point
    // to other entity bundles that contain a theme field.
    foreach (FieldConfig::loadMultiple($reference_field_ids) as $field_config) {
      $field_name = $field_config->getName();
      $target_type = $field_config->getSetting('target_type');
      if (!empty($target_type) && !empty($theme_fields[$target_type])) {
        $handler_settings = $field_config->getSetting('handler_settings');
        foreach ($theme_fields[$target_type] as $theme_field_name => $theme_field_info) {
          foreach ($theme_field_info['bundles'] as $target_bundle) {
            if (isset($handler_settings['target_bundles'][$target_bundle])) {
              if ($referenced_entities = $entity->{$field_name}->referencedEntities()) {
                // If multiple entities are referenced, the first one with a
                // theme takes precedence.
                foreach($referenced_entities as $referenced_entity) {
                  if ($theme = $this->getThemeFromEntity($referenced_entity)) {
                    $applies = TRUE;
                    break 4;
                  }
                }
              }
            }
          }
        }
      }
    }

    return $theme;
  }
}
