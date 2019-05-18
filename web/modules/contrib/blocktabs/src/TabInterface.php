<?php

namespace Drupal\blocktabs;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\blocktabs\BlocktabsInterface;

/**
 * Defines the interface for tab.
 *
 * @see \Drupal\blocktabs\Annotation\Tab
 * @see \Drupal\blocktabs\TabBase
 * @see \Drupal\blocktabs\ConfigurableTabInterface
 * @see \Drupal\blocktabs\ConfigurableTabBase
 * @see \Drupal\blocktabs\TabManager
 * @see plugin_api
 */
interface TabInterface extends PluginInspectionInterface, ConfigurablePluginInterface, ContextAwarePluginInterface {

  /**
   * Applies a tab to the blocktabs.
   *
   * @param \Drupal\blocktabs\BlocktabsInterface $blocktabs
   *   An blocktabs object.
   *
   * @return bool
   *   TRUE on success. FALSE if unable to add the tab to the blocktabs.
   */
  public function addTab(BlocktabsInterface $blocktabs);

  /**
   * Returns the extension the derivative would have have after adding this tab.
   *
   * @param string $extension
   *   The tab extension the derivative has before adding.
   *
   * @return string
   *   The tab extension after adding.
   */
  public function getDerivativeExtension($extension);

  /**
   * Returns a render array summarizing the configuration of the tab.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Returns the tab label.
   *
   * @return string
   *   The tab label.
   */
  public function label();

  /**
   * Returns the unique ID representing the tab.
   *
   * @return string
   *   The tab ID.
   */
  public function getUuid();

  /**
   * Returns the weight of the tab.
   *
   * @return int|string
   *   Either the integer weight of the tab, or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for this tab.
   *
   * @param int $weight
   *   The weight for this tab.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Returns the title of the tab.
   *
   * @return string
   *   Either the string of the tab.
   */
  public function getTitle();

  /**
   * Sets the title for this tab.
   *
   * @param int $title
   *   The title for this tab.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Returns the content of the tab.
   *
   * @return string
   *   The content of the tab.
   */
  public function getContent();

}
