<?php

namespace Drupal\presshub;

use Drupal\Component\Plugin\PluginBase;
use Drupal\presshub\PresshubInterface;

class PresshubBase extends PluginBase implements PresshubInterface {

  /**
   * Get Presshub template name.
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * Entity types supported by the template.
   */
  public function getEntityTypes() {
    return !empty($this->pluginDefinition['entity_types']) ? $this->pluginDefinition['entity_types'] : [];
  }

  /**
   * Presshub publishable.
   * Condition that tells Drupal if article is ready for publishing.
   */
  public function isPublishable($entity) {
    return FALSE;
  }

  /**
   * Presshub Preview.
   * Condition that tells Presshub to pull previewable template.
   */
  public function isPreview($entity) {
    return FALSE;
  }

  /**
   * Presshub service parameters.
   */
  public function setServiceParams($entity) {
    return [];
  }

  /**
   * Build Presshub template.
   */
  public function template($entity) {
    return;
  }

}
