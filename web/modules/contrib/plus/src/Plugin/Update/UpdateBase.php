<?php
/**
 * @file
 * Contains \Drupal\plus\Plugin\Update\UpdateBase.
 */

namespace Drupal\plus\Plugin\Update;

use Drupal\plus\Plugin\Theme\ThemeInterface;
use Drupal\plus\Plus;
use Drupal\plus\Plugin\ThemePluginBase;

/**
 * Base class for an update.
 *
 * @ingroup plugins_update
 */
class UpdateBase extends ThemePluginBase implements UpdateInterface {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return isset($this->pluginDefinition['description']) ? $this->pluginDefinition['description'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return !empty($this->pluginDefinition['label']) ? $this->pluginDefinition['label'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return isset($this->pluginDefinition['provider']) ? $this->pluginDefinition['provider'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    return (int) $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverity() {
    return isset($this->pluginDefinition['severity']) ? $this->pluginDefinition['severity'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return Plus::getTheme($this->pluginDefinition['provider']);
  }

  /**
   * {@inheritdoc}
   */
  public function isPrivate() {
    return !empty($this->pluginDefinition['private']);
  }

  /**
   * {@inheritdoc}
   */
  public function process(ThemeInterface $theme, array &$context) {}

  /*************************
   * Deprecated methods.
   *************************/

  /**
   * {@inheritdoc}
   *
   * @deprecated 8.x-3.0-rc2, will be removed before 8.x-3.0 is released.
   *
   * @see \Drupal\plus\Plugin\Update\UpdateBase::getSeverity
   */
  public function getLevel() {
    return $this->getSeverity();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated 8.x-3.0-rc2, will be removed before 8.x-3.0 is released.
   *
   * @see \Drupal\plus\Plugin\Update\UpdateBase::getLabel
   */
  public function getTitle() {
    return $this->getLabel();
  }

}
