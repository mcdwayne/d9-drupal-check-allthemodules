<?php

namespace Drupal\widget_engine_domain_access;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\widget_engine\Entity\WidgetType;

/**
 * Dynamic permissions class for Widget Engine Domain Access.
 */
class WidgetEngineDomainAccessPermissions {

  use StringTranslationTrait;

  /**
   * Define permissions.
   */
  public function permissions() {
    $permissions = array(
      'save widgets on any domain' => [
        'title' => $this->t('Save widgets on any domain'),
      ],
      'save widgets on any assigned domain' => [
        'title' => $this->t('Save widgets on any assigned domain'),
      ],
      'create domain widgets' => [
        'title' => $this->t('Create any widgets on assigned domains'),
      ],
      'edit domain widgets' => [
        'title' => $this->t('Edit any widgets on assigned domains'),
      ],
      'delete domain widgets' => [
        'title' => $this->t('Delete any widgets on assigned domains'),
      ],
      'view unpublished domain widgets' => array(
        'title' => $this->t('View unpublished widgets on assigned domains'),
        'restrict access' => TRUE,
      ),
    );

    // Generate standard widget permissions for all applicable widget types.
    foreach (WidgetType::loadMultiple() as $type) {
      $permissions += $this->widgetPermissions($type);
    }

    return $permissions;
  }

  /**
   * Helper method to generate standard widget permission list for a given type.
   *
   * @param WidgetType $type
   *   The widget type object.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  private function widgetPermissions(WidgetType $type) {
    // Build standard list of widget permissions for this type.
    $id = $type->id();
    $perms = array(
      "create $id widget on assigned domains" => array(
        'title' => $this->t('%type_name: Create new widget on assigned domains', array('%type_name' => $type->label())),
      ),
      "update $id widget on assigned domains" => array(
        'title' => $this->t('%type_name: Edit any widget on assigned domains', array('%type_name' => $type->label())),
      ),
      "delete $id widget on assigned domains" => array(
        'title' => $this->t('%type_name: Delete any widget on assigned domains', array('%type_name' => $type->label())),
      ),
    );

    return $perms;
  }

}
