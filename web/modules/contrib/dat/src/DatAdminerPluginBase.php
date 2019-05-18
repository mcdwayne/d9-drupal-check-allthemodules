<?php

namespace Drupal\dat;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for DAT Adminer plugins.
 */
abstract class DatAdminerPluginBase extends PluginBase implements DatAdminerPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminerName() {
    return 'drupal_' . $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->pluginDefinition['group'];
  }

  /**
   * {@inheritdoc}
   */
  public function isAdminerAllowed() {
    return in_array('adminer', $this->pluginDefinition['allowed_types']);
  }

  /**
   * {@inheritdoc}
   */
  public function isEditorAllowed() {
    return in_array('editor', $this->pluginDefinition['allowed_types']);
  }

}
